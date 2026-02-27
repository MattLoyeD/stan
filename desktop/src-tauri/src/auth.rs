use std::time::Duration;

use crate::sidecar;

pub async fn read_token(port: u16) -> Result<String, String> {
    let token_path = sidecar::stan_home()
        .join("storage")
        .join("app")
        .join(".stan_token");

    if let Ok(token) = std::fs::read_to_string(&token_path) {
        let token = token.trim().to_string();

        if !token.is_empty() {
            return Ok(token);
        }
    }

    fetch_token_from_api(port).await
}

async fn fetch_token_from_api(port: u16) -> Result<String, String> {
    let url = format!("http://127.0.0.1:{port}/api/auth/auto-token");
    let client = reqwest::Client::builder()
        .timeout(Duration::from_secs(5))
        .build()
        .unwrap();

    let res = client
        .get(&url)
        .send()
        .await
        .map_err(|e| format!("Failed to fetch token: {e}"))?;

    if !res.status().is_success() {
        return Err(format!("Token endpoint returned {}", res.status()));
    }

    let body: serde_json::Value = res
        .json()
        .await
        .map_err(|e| format!("Failed to parse token response: {e}"))?;

    body["token"]
        .as_str()
        .map(|s| s.to_string())
        .ok_or_else(|| "Token not found in response".to_string())
}

pub fn build_app_url(port: u16, token: &str) -> String {
    let encoded_token = url::form_urlencoded::Serializer::new(String::new())
        .append_pair("_stan_token", token)
        .finish();

    format!("http://127.0.0.1:{port}?{encoded_token}")
}
