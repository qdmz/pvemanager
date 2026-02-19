use axum::{
    extract::{State},
    http::StatusCode,
    Json,
};
use chrono::{Duration, Utc};
use jsonwebtoken::{encode, EncodingKey, Header};
use uuid::Uuid;
use validator::Validate;

use crate::config::Config;
use crate::db::DbPool;
use crate::services::AuthService;
use pve_shared::{
    dtos::{AuthResponse, LoginRequest, RegisterRequest, UserInfo},
    error::{AppError, Result},
};

pub async fn login(
    State(config): State<Config>,
    State(pool): State<DbPool>,
    Json(req): Json<LoginRequest>,
) -> Result<Json<AuthResponse>> {
    req.validate()?;

    let user = AuthService::verify_user(&pool, &req.email, &req.password).await?;

    // 生成 JWT token
    let now = Utc::now();
    let exp = now + Duration::hours(24);

    let claims = serde_json::json!({
        "sub": user.id,
        "username": user.username,
        "email": user.email,
        "role": user.role as i16, // 转换为数字以兼容 serde
        "iat": now.timestamp(),
        "exp": exp.timestamp(),
    });

    let token = encode(
        &Header::default(),
        &claims,
        &EncodingKey::from_secret(config.jwt_secret.as_ref()),
    )
    .map_err(|e| AppError::Jwt(e.to_string()))?;

    let user_info = UserInfo {
        id: user.id,
        username: user.username,
        email: user.email,
        role: format!("{:?}", user.role).to_lowercase(),
    };

    Ok(Json(AuthResponse { token, user: user_info }))
}

pub async fn register(
    State(pool): State<DbPool>,
    Json(req): Json<RegisterRequest>,
) -> Result<Json<AuthResponse>> {
    req.validate()?;

    let user = AuthService::create_user(&pool, req.username, req.email, req.password).await?;

    let user_info = UserInfo {
        id: user.id,
        username: user.username,
        email: user.email,
        role: format!("{:?}", user.role).to_lowercase(),
    };

    // 注册成功后需要登录获取 token
    // 这里简化处理，直接返回用户信息
    Ok(Json(AuthResponse {
        token: "registration-success".to_string(),
        user: user_info,
    }))
}
