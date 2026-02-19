use anyhow::Result;
use config::{Config as AppConfig, Environment, File};
use serde::Deserialize;

#[derive(Debug, Deserialize, Clone)]
pub struct Config {
    pub host: String,
    pub port: u16,
    pub database_url: String,
    pub jwt_secret: String,
    pub pve_url: String,
    pub pve_username: String,
    pub pve_password: String,
    pub pve_realm: String,
}

impl Config {
    pub fn from_env() -> Result<Self> {
        let config = AppConfig::builder()
            .add_source(File::with_name("config/config").required(false))
            .add_source(Environment::with_prefix("PVE").separator("_"))
            .build()?;

        let config: Config = config.try_deserialize()?;

        Ok(config)
    }
}
