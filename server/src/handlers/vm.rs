use axum::{
    extract::{State, Path, Extension},
    Json,
    http::StatusCode,
};
use uuid::Uuid;
use validator::Validate;

use crate::db::DbPool;
use crate::services::{VmService, AuditService};
use pve_shared::{
    dtos::{CreateVmRequest, VmActionRequest, UpdateVmRequest, CreateSnapshotRequest, ApiResponse},
    error::{AppError, Result},
    models::{VirtualMachine, VmSnapshot},
};

pub async fn list_vms(
    State(pool): State<DbPool>,
    Extension(user): Extension<pve_shared::dtos::UserInfo>,
) -> Result<Json<ApiResponse<Vec<VirtualMachine>>>> {
    let vms = if user.role == "admin" {
        VmService::list_vms(&pool, None).await?
    } else {
        VmService::list_vms(&pool, Some(user.id)).await?
    };

    Ok(Json(ApiResponse {
        success: true,
        data: vms,
        message: None,
    }))
}

pub async fn get_vm(
    State(pool): State<DbPool>,
    Path(id): Path<Uuid>,
    Extension(user): Extension<pve_shared::dtos::UserInfo>,
) -> Result<Json<ApiResponse<VirtualMachine>>> {
    let vm = VmService::get_vm(&pool, id).await?;

    // 权限检查
    if user.role != "admin" && vm.owner_id != user.id {
        return Err(AppError::Forbidden("Access denied".to_string()));
    }

    Ok(Json(ApiResponse {
        success: true,
        data: vm,
        message: None,
    }))
}

pub async fn create_vm(
    State(pool): State<DbPool>,
    Extension(user): Extension<pve_shared::dtos::UserInfo>,
    Json(req): Json<CreateVmRequest>,
) -> Result<Json<ApiResponse<VirtualMachine>>> {
    req.validate()?;

    let vm = VmService::create_vm(
        &pool,
        req.name,
        req.cpu_cores,
        req.memory_mb,
        req.disk_gb,
        req.node,
        user.id,
    )
    .await?;

    // 记录审计日志
    AuditService::log_action(
        &pool,
        user.id,
        "create_vm".to_string(),
        "virtual_machine".to_string(),
        Some(vm.id.to_string()),
        Some(serde_json::json!({ "vm_name": vm.name })),
        None,
    )
    .await?;

    Ok(Json(ApiResponse {
        success: true,
        data: vm,
        message: Some("Virtual machine created successfully".to_string()),
    }))
}

pub async fn update_vm(
    State(pool): State<DbPool>,
    Path(id): Path<Uuid>,
    Extension(user): Extension<pve_shared::dtos::UserInfo>,
    Json(req): Json<UpdateVmRequest>,
) -> Result<Json<ApiResponse<VirtualMachine>>> {
    let mut vm = VmService::get_vm(&pool, id).await?;

    // 权限检查
    if user.role != "admin" && vm.owner_id != user.id {
        return Err(AppError::Forbidden("Access denied".to_string()));
    }

    // 更新字段
    if let Some(name) = req.name {
        vm.name = name;
    }
    if let Some(cpu) = req.cpu_cores {
        vm.cpu_cores = cpu;
    }
    if let Some(mem) = req.memory_mb {
        vm.memory_mb = mem;
    }

    // 更新数据库
    sqlx::query(
        "UPDATE virtual_machines SET name = $1, cpu_cores = $2, memory_mb = $3, updated_at = $4 WHERE id = $5"
    )
    .bind(&vm.name)
    .bind(vm.cpu_cores)
    .bind(vm.memory_mb)
    .bind(chrono::Utc::now())
    .bind(id)
    .execute(&pool)
    .await?;

    Ok(Json(ApiResponse {
        success: true,
        data: vm,
        message: Some("Virtual machine updated successfully".to_string()),
    }))
}

pub async fn delete_vm(
    State(pool): State<DbPool>,
    Path(id): Path<Uuid>,
    Extension(user): Extension<pve_shared::dtos::UserInfo>,
) -> Result<Json<ApiResponse<serde_json::Value>>> {
    let vm = VmService::get_vm(&pool, id).await?;

    // 权限检查
    if user.role != "admin" && vm.owner_id != user.id {
        return Err(AppError::Forbidden("Access denied".to_string()));
    }

    VmService::delete_vm(&pool, id).await?;

    Ok(Json(ApiResponse {
        success: true,
        data: serde_json::json!({"deleted": true}),
        message: Some("Virtual machine deleted successfully".to_string()),
    }))
}

pub async fn vm_action(
    State(pool): State<DbPool>,
    Path(id): Path<Uuid>,
    Extension(user): Extension<pve_shared::dtos::UserInfo>,
    Json(req): Json<VmActionRequest>,
) -> Result<Json<ApiResponse<serde_json::Value>>> {
    let vm = VmService::get_vm(&pool, id).await?;

    // 权限检查
    if user.role != "admin" && vm.owner_id != user.id {
        return Err(AppError::Forbidden("Access denied".to_string()));
    }

    let action_str = format!("{:?}", req.action).to_lowercase();
    let new_status = match req.action {
        pve_shared::dtos::VmAction::Start => "running",
        pve_shared::dtos::VmAction::Stop => "stopped",
        pve_shared::dtos::VmAction::Pause => "paused",
        pve_shared::dtos::VmAction::Unpause => "running",
        _ => "stopped",
    };

    // 更新状态
    VmService::update_vm_status(&pool, id, new_status).await?;

    Ok(Json(ApiResponse {
        success: true,
        data: serde_json::json!({"action": action_str, "status": new_status}),
        message: Some(format!("VM {} successfully", action_str)),
    }))
}

pub async fn get_vm_snapshots(
    State(pool): State<DbPool>,
    Path(id): Path<Uuid>,
    Extension(user): Extension<pve_shared::dtos::UserInfo>,
) -> Result<Json<ApiResponse<Vec<VmSnapshot>>>> {
    let vm = VmService::get_vm(&pool, id).await?;

    // 权限检查
    if user.role != "admin" && vm.owner_id != user.id {
        return Err(AppError::Forbidden("Access denied".to_string()));
    }

    let snapshots = sqlx::query_as::<_, VmSnapshot>(
        "SELECT * FROM vm_snapshots WHERE vm_id = $1 ORDER BY created_at DESC",
    )
    .bind(id)
    .fetch_all(&pool)
    .await?;

    Ok(Json(ApiResponse {
        success: true,
        data: snapshots,
        message: None,
    }))
}

pub async fn create_snapshot(
    State(pool): State<DbPool>,
    Path(id): Path<Uuid>,
    Extension(user): Extension<pve_shared::dtos::UserInfo>,
    Json(req): Json<CreateSnapshotRequest>,
) -> Result<Json<ApiResponse<VmSnapshot>>> {
    let vm = VmService::get_vm(&pool, id).await?;

    // 权限检查
    if user.role != "admin" && vm.owner_id != user.id {
        return Err(AppError::Forbidden("Access denied".to_string()));
    }

    let snapshot = sqlx::query_as::<_, VmSnapshot>(
        r#"
        INSERT INTO vm_snapshots (vm_id, name, description, created_by)
        VALUES ($1, $2, $3, $4)
        RETURNING *
        "#,
    )
    .bind(id)
    .bind(&req.name)
    .bind(&req.description)
    .bind(user.id)
    .fetch_one(&pool)
    .await?;

    Ok(Json(ApiResponse {
        success: true,
        data: snapshot,
        message: Some("Snapshot created successfully".to_string()),
    }))
}

pub async fn delete_snapshot(
    State(pool): State<DbPool>,
    Path((id, snapshot_id)): Path<(Uuid, Uuid)>,
    Extension(user): Extension<pve_shared::dtos::UserInfo>,
) -> Result<Json<ApiResponse<serde_json::Value>>> {
    let vm = VmService::get_vm(&pool, id).await?;

    // 权限检查
    if user.role != "admin" && vm.owner_id != user.id {
        return Err(AppError::Forbidden("Access denied".to_string()));
    }

    sqlx::query("DELETE FROM vm_snapshots WHERE id = $1")
        .bind(snapshot_id)
        .execute(&pool)
        .await?;

    Ok(Json(ApiResponse {
        success: true,
        data: serde_json::json!({"deleted": true}),
        message: Some("Snapshot deleted successfully".to_string()),
    }))
}
