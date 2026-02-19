use serde::{Deserialize, Serialize};
use validator::Validate;
use uuid::Uuid;
use chrono::{DateTime, Utc};

#[derive(Debug, Deserialize, Validate)]
pub struct LoginRequest {
    #[validate(email)]
    pub email: String,
    #[validate(length(min = 6))]
    pub password: String,
}

#[derive(Debug, Deserialize, Validate)]
pub struct RegisterRequest {
    #[validate(length(min = 3, max = 50))]
    pub username: String,
    #[validate(email)]
    pub email: String,
    #[validate(length(min = 6))]
    pub password: String,
}

#[derive(Debug, Serialize)]
pub struct AuthResponse {
    pub token: String,
    pub user: UserInfo,
}

#[derive(Debug, Serialize, Deserialize, Clone)]
pub struct UserInfo {
    pub id: Uuid,
    pub username: String,
    pub email: String,
    pub role: String,
}

#[derive(Debug, Deserialize, Validate)]
pub struct CreateVmRequest {
    #[validate(length(min = 1))]
    pub name: String,
    #[validate(range(min = 1, max = 32))]
    pub cpu_cores: i32,
    #[validate(range(min = 512))]
    pub memory_mb: i32,
    #[validate(range(min = 10))]
    pub disk_gb: i32,
    pub node: String,
}

#[derive(Debug, Deserialize)]
pub struct VmActionRequest {
    pub action: VmAction,
}

#[derive(Debug, Deserialize)]
#[serde(rename_all = "lowercase")]
pub enum VmAction {
    Start,
    Stop,
    Restart,
    Pause,
    Unpause,
}

#[derive(Debug, Deserialize)]
pub struct CreateSnapshotRequest {
    pub name: String,
    pub description: Option<String>,
}

#[derive(Debug, Deserialize, Validate)]
pub struct CreateFirewallRuleRequest {
    pub direction: String,
    pub action: String,
    pub protocol: String,
    pub port: Option<i32>,
    pub source: Option<String>,
    pub destination: Option<String>,
}

#[derive(Debug, Deserialize)]
pub struct UpdateVmRequest {
    pub name: Option<String>,
    pub cpu_cores: Option<i32>,
    pub memory_mb: Option<i32>,
}

#[derive(Debug, Serialize)]
pub struct ApiResponse<T> {
    pub success: bool,
    pub data: T,
    pub message: Option<String>,
}

#[derive(Debug, Serialize)]
pub struct PaginatedResponse<T> {
    pub items: Vec<T>,
    pub total: i64,
    pub page: i64,
    pub per_page: i64,
}
