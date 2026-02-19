# PVE Manager API 文档

## 认证

所有 API 请求（除登录和注册外）都需要在 Header 中携带 JWT Token:

```
Authorization: Bearer <token>
```

## 基础信息

- **Base URL**: `http://localhost:8080/api`
- **Content-Type**: `application/json`

## API 端点

### 1. 认证

#### 1.1 用户登录
```http
POST /auth/login
```

**请求体:**
```json
{
  "email": "admin@pve.local",
  "password": "admin123"
}
```

**响应:**
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "username": "admin",
    "email": "admin@pve.local",
    "role": "admin"
  }
}
```

#### 1.2 用户注册
```http
POST /auth/register
```

**请求体:**
```json
{
  "username": "newuser",
  "email": "user@example.com",
  "password": "password123"
}
```

**响应:** 同登录

### 2. 虚拟机管理

#### 2.1 获取虚拟机列表
```http
GET /vms
```

**响应:**
```json
{
  "success": true,
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440001",
      "vmid": 100,
      "name": "vm-ubuntu",
      "status": "running",
      "cpu_cores": 2,
      "memory_mb": 2048,
      "disk_gb": 50,
      "node": "node1",
      "owner_id": "550e8400-e29b-41d4-a716-446655440000",
      "created_at": "2024-01-15T10:30:00Z",
      "updated_at": "2024-01-15T10:30:00Z"
    }
  ],
  "message": null
}
```

#### 2.2 获取单个虚拟机
```http
GET /vms/{id}
```

**路径参数:**
- `id` - 虚拟机 UUID

**响应:** 同上，但 data 为单个虚拟机对象

#### 2.3 创建虚拟机
```http
POST /vms
```

**请求体:**
```json
{
  "name": "my-vm",
  "cpu_cores": 2,
  "memory_mb": 2048,
  "disk_gb": 50,
  "node": "node1"
}
```

**响应:**
```json
{
  "success": true,
  "data": { /* 虚拟机对象 */ },
  "message": "Virtual machine created successfully"
}
```

#### 2.4 更新虚拟机
```http
PUT /vms/{id}
```

**请求体:**
```json
{
  "name": "updated-vm-name",
  "cpu_cores": 4,
  "memory_mb": 4096
}
```

#### 2.5 删除虚拟机
```http
DELETE /vms/{id}
```

**响应:**
```json
{
  "success": true,
  "data": { "deleted": true },
  "message": "Virtual machine deleted successfully"
}
```

#### 2.6 虚拟机操作
```http
POST /vms/{id}/action
```

**请求体:**
```json
{
  "action": "start"
}
```

**可用操作:**
- `start` - 启动
- `stop` - 停止
- `restart` - 重启
- `pause` - 暂停
- `unpause` - 恢复

#### 2.7 获取快照列表
```http
GET /vms/{id}/snapshots
```

#### 2.8 创建快照
```http
POST /vms/{id}/snapshots
```

**请求体:**
```json
{
  "name": "backup-2024-01-15",
  "description": "Weekly backup"
}
```

#### 2.9 删除快照
```http
DELETE /vms/{id}/snapshots/{snapshot_id}
```

### 3. 防火墙管理

#### 3.1 获取防火墙规则
```http
GET /vms/{id}/firewall
```

**响应:**
```json
{
  "success": true,
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440002",
      "vm_id": "550e8400-e29b-41d4-a716-446655440001",
      "direction": "inbound",
      "action": "accept",
      "protocol": "TCP",
      "port": 22,
      "source": "0.0.0.0/0",
      "destination": null,
      "enabled": true,
      "created_at": "2024-01-15T10:30:00Z"
    }
  ]
}
```

#### 3.2 创建防火墙规则
```http
POST /vms/{id}/firewall
```

**请求体:**
```json
{
  "direction": "inbound",
  "action": "accept",
  "protocol": "TCP",
  "port": 80,
  "source": "0.0.0.0/0"
}
```

#### 3.3 更新防火墙规则
```http
PUT /firewall/{id}
```

#### 3.4 删除防火墙规则
```http
DELETE /firewall/{id}
```

### 4. 监控统计

#### 4.1 获取系统统计
```http
GET /stats/system
```

**响应:**
```json
{
  "success": true,
  "data": {
    "cpu_usage": 35.5,
    "memory_usage": 62.3,
    "disk_usage": 48.7,
    "network_rx": 1024.5,
    "network_tx": 512.3,
    "timestamp": "2024-01-15T10:30:00Z"
  }
}
```

### 5. 审计日志

#### 5.1 获取操作日志
```http
GET /audit-logs?limit=50&offset=0
```

**查询参数:**
- `limit` - 返回数量限制（默认 50）
- `offset` - 偏移量（默认 0）

**响应:**
```json
{
  "success": true,
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440003",
      "user_id": "550e8400-e29b-41d4-a716-446655440000",
      "action": "create_vm",
      "resource_type": "virtual_machine",
      "resource_id": "550e8400-e29b-41d4-a716-446655440001",
      "details": { "vm_name": "my-vm" },
      "ip_address": "192.168.1.100",
      "created_at": "2024-01-15T10:30:00Z"
    }
  ]
}
```

### 6. 健康检查

```http
GET /health
```

**响应:**
```json
{
  "status": "ok",
  "timestamp": "2024-01-15T10:30:00Z"
}
```

## 错误响应

所有错误响应都遵循以下格式:

```json
{
  "error": "错误消息",
  "code": 400
}
```

### HTTP 状态码

- `200` - 成功
- `400` - 请求错误（参数验证失败等）
- `401` - 未授权（Token 无效或过期）
- `403` - 禁止访问（权限不足）
- `404` - 资源不存在
- `500` - 服务器内部错误

## 速率限制

API 当前未实现速率限制，建议在生产环境中使用反向代理（如 Nginx）配置速率限制。

## WebSocket (计划中)

未来版本将支持 WebSocket 连接用于实时数据推送:

```
ws://localhost:8080/ws
```
