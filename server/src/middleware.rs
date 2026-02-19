use axum::{
    extract::{Request, State},
    http::StatusCode,
    middleware::Next,
    response::Response,
    Json,
};
use jsonwebtoken::{decode, Validation, DecodingKey};
use serde_json::json;

use crate::config::Config;
use pve_shared::dtos::UserInfo;

pub struct AppState {
    pub config: Config,
}

pub async fn auth_middleware(
    State(config): State<Config>,
    mut request: Request,
    next: Next,
) -> Result<Response, StatusCode> {
    // 从 Authorization header 获取 token
    let auth_header = request
        .headers()
        .get("Authorization")
        .and_then(|h| h.to_str().ok());

    let token = match auth_header {
        Some(header) if header.starts_with("Bearer ") => {
            Some(&header[7..])
        }
        _ => None,
    };

    let token = token.ok_or(StatusCode::UNAUTHORIZED)?;

    // 验证 token
    let token_data = decode::<Claims>(
        token,
        &DecodingKey::from_secret(config.jwt_secret.as_ref()),
        &Validation::default(),
    )
    .map_err(|_| StatusCode::UNAUTHORIZED)?;

    // 将用户信息添加到请求扩展中
    let user_info = UserInfo {
        id: token_data.claims.sub,
        username: token_data.claims.username,
        email: token_data.claims.email,
        role: token_data.claims.role,
    };
    request.extensions_mut().insert(user_info);

    Ok(next.run(request).await)
}

#[derive(serde::Deserialize)]
pub struct Claims {
    pub sub: uuid::Uuid,
    pub username: String,
    pub email: String,
    pub role: String,
    pub exp: i64,
}
