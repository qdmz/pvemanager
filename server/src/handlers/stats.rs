use axum::{
    extract::State,
    Json,
};
use chrono::Utc;
use pve_shared::{
    dtos::ApiResponse,
    error::Result,
    models::SystemStats,
};

pub async fn get_system_stats(
    State(_pool): axum::extract::State<crate::db::DbPool>,
) -> Result<Json<ApiResponse<SystemStats>>> {
    // 模拟数据，实际应该从 PVE API 获取
    let stats = SystemStats {
        cpu_usage: 35.5,
        memory_usage: 62.3,
        disk_usage: 48.7,
        network_rx: 1024.5,
        network_tx: 512.3,
        timestamp: Utc::now(),
    };

    Ok(Json(ApiResponse {
        success: true,
        data: stats,
        message: None,
    }))
}
