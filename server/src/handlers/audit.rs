use axum::{extract::Query, Json};

use crate::db::DbPool;
use pve_shared::{
    dtos::ApiResponse,
    error::Result,
    models::AuditLog,
};
use serde::Deserialize;

#[derive(Debug, Deserialize)]
pub struct AuditQuery {
    pub limit: Option<i64>,
    pub offset: Option<i64>,
}

pub async fn list_audit_logs(
    State(pool): State<DbPool>,
    Query(params): Query<AuditQuery>,
    Extension(user): Extension<pve_shared::dtos::UserInfo>,
) -> Result<Json<ApiResponse<Vec<AuditLog>>>> {
    let limit = params.limit.unwrap_or(50);
    let offset = params.offset.unwrap_or(0);

    let logs = if user.role == "admin" {
        crate::services::AuditService::list_logs(&pool, None, limit, offset).await?
    } else {
        crate::services::AuditService::list_logs(&pool, Some(user.id), limit, offset).await?
    };

    Ok(Json(ApiResponse {
        success: true,
        data: logs,
        message: None,
    }))
}
