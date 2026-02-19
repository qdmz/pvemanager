use thiserror::Error;

#[derive(Error, Debug)]
pub enum AppError {
    #[error("Database error: {0}")]
    Database(#[from] sqlx::Error),

    #[error("Authentication failed")]
    Unauthorized,

    #[error("Forbidden: {0}")]
    Forbidden(String),

    #[error("Not found: {0}")]
    NotFound(String),

    #[error("Validation error: {0}")]
    Validation(String),

    #[error("PVE API error: {0}")]
    PveApi(String),

    #[error("Internal server error: {0}")]
    Internal(String),

    #[error("JWT error: {0}")]
    Jwt(String),
}

pub type Result<T> = std::result::Result<T, AppError>;

impl axum::response::IntoResponse for AppError {
    fn into_response(self) -> axum::response::Response {
        let (status, message) = match &self {
            AppError::Database(_) | AppError::Internal(_) => (
                axum::http::StatusCode::INTERNAL_SERVER_ERROR,
                self.to_string(),
            ),
            AppError::Unauthorized => (axum::http::StatusCode::UNAUTHORIZED, self.to_string()),
            AppError::Forbidden(_) => (axum::http::StatusCode::FORBIDDEN, self.to_string()),
            AppError::NotFound(_) => (axum::http::StatusCode::NOT_FOUND, self.to_string()),
            AppError::Validation(_) => (axum::http::StatusCode::BAD_REQUEST, self.to_string()),
            AppError::PveApi(_) => (axum::http::StatusCode::BAD_GATEWAY, self.to_string()),
            AppError::Jwt(_) => (axum::http::StatusCode::UNAUTHORIZED, self.to_string()),
        };

        let body = serde_json::json!({
            "error": message,
            "code": status.as_u16()
        });

        (status, axum::Json(body)).into_response()
    }
}
