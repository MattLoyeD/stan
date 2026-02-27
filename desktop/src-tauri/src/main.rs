// Prevents additional console window on Windows in release
#![cfg_attr(not(debug_assertions), windows_subsystem = "windows")]

mod auth;
mod sidecar;
mod tray;

use sidecar::{SharedSidecar, SidecarState};
use std::sync::Arc;
use tauri::Manager;
use tokio::sync::Mutex;

fn main() {
    let sidecar_state: SharedSidecar = Arc::new(Mutex::new(SidecarState::new()));

    tauri::Builder::default()
        .plugin(tauri_plugin_shell::init())
        .plugin(tauri_plugin_process::init())
        .plugin(tauri_plugin_single_instance::init(|app, _args, _cwd| {
            if let Some(window) = app.get_webview_window("main") {
                let _ = window.show();
                let _ = window.set_focus();
            }
        }))
        .manage(sidecar_state.clone())
        .setup(move |app| {
            let handle = app.handle().clone();
            let state = sidecar_state.clone();

            tray::setup_tray(&handle)?;

            tauri::async_runtime::spawn(async move {
                match sidecar::start_sidecar(&handle, state.clone()).await {
                    Ok(port) => {
                        match auth::read_token(port).await {
                            Ok(token) => {
                                let url = auth::build_app_url(port, &token);

                                if let Some(window) = handle.get_webview_window("main") {
                                    let _ = window.navigate(url.parse().unwrap());
                                }
                            }
                            Err(e) => {
                                eprintln!("[stan] Failed to read auth token: {e}");
                                // Navigate to sidecar without token — user will see login
                                let url = format!("http://127.0.0.1:{port}");
                                if let Some(window) = handle.get_webview_window("main") {
                                    let _ = window.navigate(url.parse().unwrap());
                                }
                            }
                        }
                    }
                    Err(e) => {
                        eprintln!("[stan] Failed to start sidecar: {e}");
                    }
                }

                install_cli_shim(&handle).await;
            });

            Ok(())
        })
        .on_window_event(|window, event| {
            if let tauri::WindowEvent::CloseRequested { api, .. } = event {
                api.prevent_close();
                let _ = window.hide();
            }
        })
        .build(tauri::generate_context!())
        .expect("error while building tauri application")
        .run(move |app, event| {
            if let tauri::RunEvent::Exit = event {
                let state: tauri::State<SharedSidecar> = app.state();
                tauri::async_runtime::block_on(sidecar::shutdown_sidecar(state.inner().clone()));
            }
        });
}

async fn install_cli_shim(app: &tauri::AppHandle) {
    let sidecar_path = match app
        .path()
        .resource_dir()
        .map(|d| d.join("binaries").join("stan"))
    {
        Ok(p) if p.exists() => p,
        _ => return,
    };

    let home = sidecar::stan_home();

    #[cfg(unix)]
    {
        let bin_dir = dirs::home_dir()
            .unwrap_or_default()
            .join(".local")
            .join("bin");

        std::fs::create_dir_all(&bin_dir).ok();

        let shim_path = bin_dir.join("stan");

        if shim_path.exists() {
            return;
        }

        let content = format!(
            "#!/bin/sh\nexport STAN_HOME=\"{}\"\nexec \"{}\" php-cli artisan \"$@\"\n",
            home.display(),
            sidecar_path.display(),
        );

        if std::fs::write(&shim_path, content).is_ok() {
            use std::os::unix::fs::PermissionsExt;
            let _ = std::fs::set_permissions(&shim_path, std::fs::Permissions::from_mode(0o755));
        }
    }

    #[cfg(windows)]
    {
        let bin_dir = dirs::data_local_dir()
            .unwrap_or_default()
            .join("Stan");

        std::fs::create_dir_all(&bin_dir).ok();

        let shim_path = bin_dir.join("stan.cmd");

        if shim_path.exists() {
            return;
        }

        let content = format!(
            "@echo off\r\nset STAN_HOME={}\r\n\"{}\" php-cli artisan %*\r\n",
            home.display(),
            sidecar_path.display(),
        );

        std::fs::write(&shim_path, content).ok();

        // Add to user PATH via registry
        if let Ok(output) = std::process::Command::new("reg")
            .args([
                "query",
                "HKCU\\Environment",
                "/v",
                "Path",
            ])
            .output()
        {
            let current_path = String::from_utf8_lossy(&output.stdout);
            let bin_str = bin_dir.to_string_lossy();

            if !current_path.contains(&*bin_str) {
                // Read current user PATH
                let user_path = current_path
                    .lines()
                    .find(|l| l.contains("Path") || l.contains("PATH"))
                    .and_then(|l| l.split("REG_EXPAND_SZ").nth(1).or(l.split("REG_SZ").nth(1)))
                    .map(|s| s.trim().to_string())
                    .unwrap_or_default();

                let new_path = if user_path.is_empty() {
                    bin_str.to_string()
                } else {
                    format!("{user_path};{bin_str}")
                };

                std::process::Command::new("reg")
                    .args([
                        "add",
                        "HKCU\\Environment",
                        "/v",
                        "Path",
                        "/t",
                        "REG_EXPAND_SZ",
                        "/d",
                        &new_path,
                        "/f",
                    ])
                    .output()
                    .ok();
            }
        }
    }
}
