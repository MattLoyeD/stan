use std::sync::Arc;
use std::time::Duration;
use tauri::AppHandle;
use tauri_plugin_shell::ShellExt;
use tauri_plugin_shell::process::{CommandChild, CommandEvent};
use tokio::sync::Mutex;
use tokio::time::sleep;

pub struct SidecarState {
    pub port: u16,
    pub child: Option<CommandChild>,
}

impl SidecarState {
    pub fn new() -> Self {
        Self {
            port: 0,
            child: None,
        }
    }
}

pub type SharedSidecar = Arc<Mutex<SidecarState>>;

pub fn find_available_port() -> u16 {
    let listener = std::net::TcpListener::bind("127.0.0.1:0").expect("Failed to bind to port 0");
    listener.local_addr().unwrap().port()
}

pub fn stan_home() -> std::path::PathBuf {
    dirs::data_dir()
        .unwrap_or_else(|| std::path::PathBuf::from("."))
        .join("stan")
}

pub async fn start_sidecar(app: &AppHandle, sidecar: SharedSidecar) -> Result<u16, String> {
    let port = find_available_port();
    let home = stan_home();

    std::fs::create_dir_all(&home).ok();

    let (mut rx, child) = app
        .shell()
        .sidecar("stan")
        .map_err(|e| format!("Failed to create sidecar command: {e}"))?
        .args([
            "php-cli",
            "artisan",
            "stan:start",
            &format!("--port={port}"),
            "--no-browser",
        ])
        .envs([
            ("STAN_HOME", home.to_string_lossy().to_string()),
            ("STAN_EMBEDDED", "true".to_string()),
            ("STAN_PORT", port.to_string()),
        ])
        .spawn()
        .map_err(|e| format!("Failed to spawn sidecar: {e}"))?;

    {
        let mut state = sidecar.lock().await;
        state.port = port;
        state.child = Some(child);
    }

    // Log sidecar output in background
    tauri::async_runtime::spawn(async move {
        while let Some(event) = rx.recv().await {
            match event {
                CommandEvent::Stdout(line) => {
                    println!("[stan] {}", String::from_utf8_lossy(&line));
                }
                CommandEvent::Stderr(line) => {
                    eprintln!("[stan] {}", String::from_utf8_lossy(&line));
                }
                CommandEvent::Terminated(payload) => {
                    println!("[stan] process terminated: {payload:?}");
                    break;
                }
                _ => {}
            }
        }
    });

    wait_for_ready(port).await?;

    Ok(port)
}

async fn wait_for_ready(port: u16) -> Result<(), String> {
    let url = format!("http://127.0.0.1:{port}/up");
    let client = reqwest::Client::builder()
        .timeout(Duration::from_secs(2))
        .build()
        .unwrap();

    for _ in 0..150 {
        if let Ok(res) = client.get(&url).send().await {
            if res.status().is_success() {
                return Ok(());
            }
        }
        sleep(Duration::from_millis(200)).await;
    }

    Err("Sidecar failed to start within 30 seconds".to_string())
}

pub async fn shutdown_sidecar(sidecar: SharedSidecar) {
    let mut state = sidecar.lock().await;

    if let Some(child) = state.child.take() {
        let _ = child.kill();
    }
}
