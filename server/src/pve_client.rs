use anyhow::Result;
use reqwest::Client;
use serde_json::Value;

pub struct PveClient {
    client: Client,
    base_url: String,
    ticket: Option<String>,
    csrf_token: Option<String>,
}

impl PveClient {
    pub async fn new(
        url: String,
        username: String,
        password: String,
        realm: String,
    ) -> Result<Self> {
        let client = Client::builder()
            .danger_accept_invalid_certs(true)
            .build()?;

        let mut pve_client = Self {
            client,
            base_url: url.trim_end_matches('/').to_string(),
            ticket: None,
            csrf_token: None,
        };

        pve_client.login(&username, &password, &realm).await?;
        Ok(pve_client)
    }

    async fn login(&mut self, username: &str, password: &str, realm: &str) -> Result<()> {
        let url = format!("{}/api2/json/access/ticket", self.base_url);
        let payload = serde_json::json!({
            "username": username,
            "password": password,
            "realm": realm
        });

        let response: Value = self.client
            .post(&url)
            .json(&payload)
            .send()
            .await?
            .json()
            .await?;

        let data = &response["data"];
        self.ticket = Some(data["ticket"].as_str().unwrap().to_string());
        self.csrf_token = Some(data["CSRFPreventionToken"].as_str().unwrap().to_string());

        Ok(())
    }

    pub async fn list_vms(&self) -> Result<Vec<Value>> {
        let url = format!("{}/api2/json/cluster/resources?type=vm", self.base_url);
        let response: Value = self.get(&url).await?;
        Ok(response["data"].as_array().unwrap().to_vec());
    }

    pub async fn get_vm_status(&self, node: &str, vmid: i32) -> Result<Value> {
        let url = format!("{}/api2/json/nodes/{}/qemu/{}/status/current", self.base_url, node, vmid);
        let response: Value = self.get(&url).await?;
        Ok(response["data"].clone())
    }

    pub async fn start_vm(&self, node: &str, vmid: i32) -> Result<Value> {
        let url = format!("{}/api2/json/nodes/{}/qemu/{}/status/start", self.base_url, node, vmid);
        let response: Value = self.post(&url, &Value::Null).await?;
        Ok(response)
    }

    pub async fn stop_vm(&self, node: &str, vmid: i32) -> Result<Value> {
        let url = format!("{}/api2/json/nodes/{}/qemu/{}/status/stop", self.base_url, node, vmid);
        let response: Value = self.post(&url, &Value::Null).await?;
        Ok(response)
    }

    pub async fn create_vm(&self, node: &str, params: &Value) -> Result<Value> {
        let url = format!("{}/api2/json/nodes/{}/qemu", self.base_url, node);
        let response: Value = self.post(&url, params).await?;
        Ok(response)
    }

    pub async fn delete_vm(&self, node: &str, vmid: i32) -> Result<Value> {
        let url = format!("{}/api2/json/nodes/{}/qemu/{}", self.base_url, node, vmid);
        let response: Value = self.delete(&url).await?;
        Ok(response)
    }

    pub async fn create_snapshot(
        &self,
        node: &str,
        vmid: i32,
        name: &str,
        description: Option<&str>,
    ) -> Result<Value> {
        let url = format!(
            "{}/api2/json/nodes/{}/qemu/{}/snapshot",
            self.base_url,
            node,
            vmid,
        );
        let mut params = serde_json::json!({ "snapname": name });
        if let Some(desc) = description {
            params["description"] = serde_json::json!(desc);
        }
        let response: Value = self.post(&url, &params).await?;
        Ok(response)
    }

    pub async fn list_snapshots(&self, node: &str, vmid: i32) -> Result<Vec<Value>> {
        let url = format!(
            "{}/api2/json/nodes/{}/qemu/{}/snapshot",
            self.base_url,
            node,
            vmid,
        );
        let response: Value = self.get(&url).await?;
        Ok(response["data"].as_array().unwrap().to_vec())
    }

    pub async fn get_node_stats(&self, node: &str) -> Result<Value> {
        let url = format!("{}/api2/json/nodes/{}/status", self.base_url, node);
        let response: Value = self.get(&url).await?;
        Ok(response["data"].clone())
    }

    async fn get(&self, url: &str) -> Result<Value> {
        let response = self
            .client
            .get(url)
            .header(
                "Cookie",
                &format!("PVEAuthCookie={}", self.ticket.as_ref().unwrap()),
            )
            .send()
            .await?;

        Ok(response.json().await?)
    }

    async fn post(&self, url: &str, body: &Value) -> Result<Value> {
        let response = self
            .client
            .post(url)
            .header(
                "Cookie",
                &format!("PVEAuthCookie={}", self.ticket.as_ref().unwrap()),
            )
            .header("CSRFPreventionToken", self.csrf_token.as_ref().unwrap())
            .json(body)
            .send()
            .await?;

        Ok(response.json().await?)
    }

    async fn delete(&self, url: &str) -> Result<Value> {
        let response = self
            .client
            .delete(url)
            .header(
                "Cookie",
                &format!("PVEAuthCookie={}", self.ticket.as_ref().unwrap()),
            )
            .header("CSRFPreventionToken", self.csrf_token.as_ref().unwrap())
            .send()
            .await?;

        Ok(response.json().await?)
    }
}
