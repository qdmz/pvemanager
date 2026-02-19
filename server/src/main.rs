mod config;
mod db;
mod handlers;
mod middleware;
mod pve_client;
mod services;

use axum::{routing::{delete, get, post, put}, Router};
use tower::ServiceBuilder;
use tower_http::cors::CorsLayer;
use tracing_subscriber::{layer::SubscriberExt, util::SubscriberInitExt};

use config::Config;
use handlers::{
    audit::list_audit_logs,
    auth::{login, register},
    firewall::{
        create_firewall_rule, delete_firewall_rule, list_firewall_rules, update_firewall_rule,
    },
    stats::get_system_stats,
    vm::{
        create_snapshot, create_vm, delete_snapshot, delete_vm, get_vm, get_vm_snapshots, list_vms,
        update_vm, vm_action,
    },
};

#[tokio::main]
async fn main() -> anyhow::Result<()> {
    // 初始化日志
    tracing_subscriber::registry()
        .with(
            tracing_subscriber::EnvFilter::try_from_default_env()
                .unwrap_or_else(|_| "pve_server=debug,tower_http=debug,axum=trace".into()),
        )
        .with(tracing_subscriber::fmt::layer())
        .init();

    let config = Config::from_env()?;
    tracing::info!("Starting PVE Manager server...");

    // 初始化数据库
    let pool = db::init_pool(&config.database_url).await?;

    // 创建应用路由
    let app = Router::new()
        // 静态文件服务（前端）
        .nest_service("/", axum::service::ServeDir::new("static"))
        // API 路由
        .route("/api/health", get(health))
        .route("/api/auth/register", post(register))
        .route("/api/auth/login", post(login))
        .route("/api/vms", get(list_vms))
        .route("/api/vms", post(create_vm))
        .route("/api/vms/:id", get(get_vm))
        .route("/api/vms/:id", put(update_vm))
        .route("/api/vms/:id", delete(delete_vm))
        .route("/api/vms/:id/action", post(vm_action))
        .route("/api/vms/:id/snapshots", get(get_vm_snapshots))
        .route("/api/vms/:id/snapshots", post(create_snapshot))
        .route(
            "/api/vms/:id/snapshots/:snapshot_id",
            delete(delete_snapshot),
        )
        .route("/api/vms/:id/firewall", get(list_firewall_rules))
        .route("/api/vms/:id/firewall", post(create_firewall_rule))
        .route("/api/firewall/:id", put(update_firewall_rule))
        .route("/api/firewall/:id", delete(delete_firewall_rule))
        .route("/api/audit-logs", get(list_audit_logs))
        .route("/api/stats/system", get(get_system_stats))
        .layer(
            ServiceBuilder::new().layer(CorsLayer::permissive()),
        )
        .with_state(pool);

    // 启动服务器
    let listener = tokio::net::TcpListener::bind(&format!("{}:{}", config.host, config.port)).await?;
    tracing::info!("Server listening on http://{}:{}", config.host, config.port);
    axum::serve(listener, app).await?;

    Ok(())
}

async fn health() -> axum::Json<serde_json::Value> {
    axum::Json(serde_json::json!({
        "status": "ok",
        "timestamp": chrono::Utc::now(),
    }))
}
