use sqlx::{Pool, Postgres, postgres::PgPoolOptions};
use pve_shared::error::Result;

pub type DbPool = Pool<Postgres>;

pub async fn init_pool(database_url: &str) -> Result<DbPool> {
    let pool = PgPoolOptions::new()
        .max_connections(10)
        .connect(database_url)
        .await?;

    // 运行迁移
    sqlx::query(include_str!("../migrations/init.sql"))
        .execute(&pool)
        .await?;

    Ok(pool)
}
