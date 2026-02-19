use axum::{extract::Path, http::StatusCode, Json};

use crate::db::DbPool;
use pve_shared::{
    dtos::{ApiResponse, CreateFirewallRuleRequest},
    error::Result,
    models::FirewallRule,
};
use uuid::Uuid;

pub async fn list_firewall_rules(
    State(pool): State<DbPool>,
    Path(id): Path<Uuid>,
    Extension(user): Extension<pve_shared::dtos::UserInfo>,
) -> Result<Json<ApiResponse<Vec<FirewallRule>>>> {
    // 简化处理，实际需要检查权限
    let rules = sqlx::query_as::<_, FirewallRule>(
        "SELECT * FROM firewall_rules WHERE vm_id = $1 ORDER BY created_at DESC"
    )
    .bind(id)
    .fetch_all(&pool)
    .await?;

    Ok(Json(ApiResponse {
        success: true,
        data: rules,
        message: None,
    }))
}

pub async fn create_firewall_rule(
    State(pool): State<DbPool>,
    Path(id): Path<Uuid>,
    Extension(user): Extension<pve_shared::dtos::UserInfo>,
    Json(req): Json<CreateFirewallRuleRequest>,
) -> Result<Json<ApiResponse<FirewallRule>>> {
    let rule = sqlx::query_as::<_, FirewallRule>(
        r#"
        INSERT INTO firewall_rules (vm_id, direction, action, protocol, port, source, destination, enabled)
        VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
        RETURNING *
        "#
    )
    .bind(id)
    .bind(&req.direction)
    .bind(&req.action)
    .bind(&req.protocol)
    .bind(req.port)
    .bind(&req.source)
    .bind(&req.destination)
    .bind(true)
    .fetch_one(&pool)
    .await?;

    Ok(Json(ApiResponse {
        success: true,
        data: rule,
        message: Some("Firewall rule created successfully".to_string()),
    }))
}

pub async fn update_firewall_rule(
    State(pool): State<DbPool>,
    Path(id): Path<Uuid>,
    Extension(user): Extension<pve_shared::dtos::UserInfo>,
    Json(req): Json<CreateFirewallRuleRequest>,
) -> Result<Json<ApiResponse<FirewallRule>>> {
    let rule = sqlx::query_as::<_, FirewallRule>(
        r#"
        UPDATE firewall_rules
        SET direction = $1, action = $2, protocol = $3, port = $4, source = $5, destination = $6
        WHERE id = $7
        RETURNING *
        "#
    )
    .bind(&req.direction)
    .bind(&req.action)
    .bind(&req.protocol)
    .bind(req.port)
    .bind(&req.source)
    .bind(&req.destination)
    .bind(id)
    .fetch_one(&pool)
    .await?;

    Ok(Json(ApiResponse {
        success: true,
        data: rule,
        message: Some("Firewall rule updated successfully".to_string()),
    }))
}

pub async fn delete_firewall_rule(
    State(pool): State<DbPool>,
    Path(id): Path<Uuid>,
    Extension(user): Extension<pve_shared::dtos::UserInfo>,
) -> Result<Json<ApiResponse<serde_json::Value>>> {
    sqlx::query("DELETE FROM firewall_rules WHERE id = $1")
        .bind(id)
        .execute(&pool)
        .await?;

    Ok(Json(ApiResponse {
        success: true,
        data: serde_json::json!({"deleted": true}),
        message: Some("Firewall rule deleted successfully".to_string()),
    }))
}
