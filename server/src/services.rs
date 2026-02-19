use bcrypt::{hash, verify, DEFAULT_COST};
use chrono::Utc;
use crate::db::DbPool;
use pve_shared::{
    error::{AppError, Result},
    models::{AuditLog, FirewallRule, User, UserRole, VirtualMachine, VmSnapshot},
};
use sqlx::Postgres;
use uuid::Uuid;

pub struct AuthService;
pub struct VmService;
pub struct AuditService;

impl AuthService {
    pub async fn create_user(
        pool: &DbPool,
        username: String,
        email: String,
        password: String,
    ) -> Result<User> {
        let password_hash = hash(&password, DEFAULT_COST)
            .map_err(|e| AppError::Internal(e.to_string()))?;

        let user = sqlx::query_as::<Postgres, User>(
            r#"
            INSERT INTO users (username, email, password_hash, role, is_active)
            VALUES ($1, $2, $3, $4, $5)
            RETURNING *
            "#
        )
        .bind(username)
        .bind(email)
        .bind(password_hash)
        .bind(UserRole::User)
        .bind(true)
        .fetch_one(pool)
        .await?;

        Ok(user)
    }

    pub async fn verify_user(
        pool: &DbPool,
        email: &str,
        password: &str,
    ) -> Result<User> {
        let user = sqlx::query_as::<Postgres, User>(
            "SELECT * FROM users WHERE email = $1 AND is_active = true"
        )
        .bind(email)
        .fetch_optional(pool)
        .await?
        .ok_or_else(|| AppError::Unauthorized)?;

        let is_valid = verify(password, &user.password_hash)
            .map_err(|e| AppError::Internal(e.to_string()))?;

        if !is_valid {
            return Err(AppError::Unauthorized);
        }

        Ok(user)
    }

    pub async fn get_user_by_id(pool: &DbPool, user_id: Uuid) -> Result<User> {
        let user = sqlx::query_as::<Postgres, User>(
            "SELECT * FROM users WHERE id = $1"
        )
        .bind(user_id)
        .fetch_optional(pool)
        .await?
        .ok_or_else(|| AppError::NotFound("User not found".to_string()))?;

        Ok(user)
    }
}

impl VmService {
    pub async fn list_vms(pool: &DbPool, owner_id: Option<Uuid>) -> Result<Vec<VirtualMachine>> {
        let vms = if let Some(owner) = owner_id {
            sqlx::query_as::<Postgres, VirtualMachine>(
                "SELECT * FROM virtual_machines WHERE owner_id = $1 ORDER BY created_at DESC"
            )
            .bind(owner)
            .fetch_all(pool)
            .await?
        } else {
            sqlx::query_as::<Postgres, VirtualMachine>(
                "SELECT * FROM virtual_machines ORDER BY created_at DESC"
            )
            .fetch_all(pool)
            .await?
        };

        Ok(vms)
    }

    pub async fn get_vm(pool: &DbPool, vm_id: Uuid) -> Result<VirtualMachine> {
        let vm = sqlx::query_as::<Postgres, VirtualMachine>(
            "SELECT * FROM virtual_machines WHERE id = $1"
        )
        .bind(vm_id)
        .fetch_optional(pool)
        .await?
        .ok_or_else(|| AppError::NotFound("Virtual machine not found".to_string()))?;

        Ok(vm)
    }

    pub async fn create_vm(
        pool: &DbPool,
        name: String,
        cpu_cores: i32,
        memory_mb: i32,
        disk_gb: i32,
        node: String,
        owner_id: Uuid,
    ) -> Result<VirtualMachine> {
        let vm = sqlx::query_as::<Postgres, VirtualMachine>(
            r#"
            INSERT INTO virtual_machines (vmid, name, status, cpu_cores, memory_mb, disk_gb, node, owner_id)
            VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
            RETURNING *
            "#
        )
        .bind(100) // 简化版，实际应该自动生成 vmid
        .bind(name)
        .bind("stopped") // 使用字符串，将在数据库中转换为 enum
        .bind(cpu_cores)
        .bind(memory_mb)
        .bind(disk_gb)
        .bind(node)
        .bind(owner_id)
        .fetch_one(pool)
        .await?;

        Ok(vm)
    }

    pub async fn update_vm_status(pool: &DbPool, vm_id: Uuid, status: &str) -> Result<()> {
        sqlx::query("UPDATE virtual_machines SET status = $1, updated_at = $2 WHERE id = $3")
            .bind(status)
            .bind(Utc::now())
            .bind(vm_id)
            .execute(pool)
            .await?;

        Ok(())
    }

    pub async fn delete_vm(pool: &DbPool, vm_id: Uuid) -> Result<()> {
        sqlx::query("DELETE FROM virtual_machines WHERE id = $1")
            .bind(vm_id)
            .execute(pool)
            .await?;

        Ok(())
    }
}

impl AuditService {
    pub async fn log_action(
        pool: &DbPool,
        user_id: Uuid,
        action: String,
        resource_type: String,
        resource_id: Option<String>,
        details: Option<serde_json::Value>,
        ip_address: Option<String>,
    ) -> Result<()> {
        sqlx::query(
            r#"
            INSERT INTO audit_logs (user_id, action, resource_type, resource_id, details, ip_address)
            VALUES ($1, $2, $3, $4, $5, $6)
            "#
        )
        .bind(user_id)
        .bind(action)
        .bind(resource_type)
        .bind(resource_id)
        .bind(details)
        .bind(ip_address)
        .execute(pool)
        .await?;

        Ok(())
    }

    pub async fn list_logs(
        pool: &DbPool,
        user_id: Option<Uuid>,
        limit: i64,
        offset: i64,
    ) -> Result<Vec<AuditLog>> {
        let logs = if let Some(user) = user_id {
            sqlx::query_as::<Postgres, AuditLog>(
                r#"
                SELECT * FROM audit_logs
                WHERE user_id = $1
                ORDER BY created_at DESC
                LIMIT $2 OFFSET $3
                "#,
            )
            .bind(user)
            .bind(limit)
            .bind(offset)
            .fetch_all(pool)
            .await?
        } else {
            sqlx::query_as::<Postgres, AuditLog>(
                r#"
                SELECT * FROM audit_logs
                ORDER BY created_at DESC
                LIMIT $1 OFFSET $2
                "#,
            )
            .bind(limit)
            .bind(offset)
            .fetch_all(pool)
            .await?
        };

        Ok(logs)
    }
}
