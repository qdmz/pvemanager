// 应用状态
const state = {
    token: localStorage.getItem('token'),
    user: JSON.parse(localStorage.getItem('user') || 'null'),
    currentVms: [],
    currentLogs: []
};

// API 基础 URL
const API_BASE = '/api';

// 初始化应用
document.addEventListener('DOMContentLoaded', () => {
    if (state.token && state.user) {
        showMainApp();
    } else {
        showLoginPage();
    }

    // 绑定事件
    bindEvents();
});

// 绑定事件
function bindEvents() {
    // 登录表单
    document.getElementById('login-form').addEventListener('submit', handleLogin);

    // 导航
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', () => navigateTo(item.dataset.page));
    });

    // 退出登录
    document.getElementById('logout-btn').addEventListener('click', handleLogout);

    // 创建虚拟机按钮
    document.getElementById('create-vm-btn').addEventListener('click', () => {
        document.getElementById('modal-create-vm').classList.remove('hidden');
    });

    // 关闭模态框
    document.querySelectorAll('.modal-close, .modal-cancel').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.modal').forEach(modal => modal.classList.add('hidden'));
        });
    });

    // 创建虚拟机表单
    document.getElementById('create-vm-form').addEventListener('submit', handleCreateVM);

    // 添加防火墙规则按钮
    document.getElementById('add-firewall-rule-btn')?.addEventListener('click', () => {
        showToast('防火墙规则功能开发中', 'warning');
    });
}

// API 请求封装
async function apiRequest(endpoint, options = {}) {
    const headers = {
        'Content-Type': 'application/json',
        ...options.headers
    };

    if (state.token) {
        headers['Authorization'] = `Bearer ${state.token}`;
    }

    const response = await fetch(`${API_BASE}${endpoint}`, {
        ...options,
        headers
    });

    const data = await response.json();

    if (!response.ok) {
        throw new Error(data.message || data.error || '请求失败');
    }

    return data;
}

// 处理登录
async function handleLogin(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const email = formData.get('email');
    const password = formData.get('password');

    try {
        const data = await apiRequest('/auth/login', {
            method: 'POST',
            body: JSON.stringify({ email, password })
        });

        state.token = data.token;
        state.user = data.user;

        localStorage.setItem('token', data.token);
        localStorage.setItem('user', JSON.stringify(data.user));

        showToast('登录成功', 'success');
        showMainApp();
    } catch (error) {
        showToast(error.message, 'error');
    }
}

// 处理退出登录
function handleLogout() {
    state.token = null;
    state.user = null;
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    showLoginPage();
    showToast('已退出登录', 'success');
}

// 显示登录页面
function showLoginPage() {
    document.getElementById('login-page').classList.remove('hidden');
    document.getElementById('main-app').classList.add('hidden');
}

// 显示主应用
function showMainApp() {
    document.getElementById('login-page').classList.add('hidden');
    document.getElementById('main-app').classList.remove('hidden');
    document.getElementById('username-display').textContent = state.user?.username || '用户';

    // 加载默认页面数据
    loadDashboardData();
}

// 导航到指定页面
function navigateTo(page) {
    // 更新导航状态
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.toggle('active', item.dataset.page === page);
    });

    // 显示对应页面
    document.querySelectorAll('.page').forEach(p => {
        p.classList.remove('active');
    });
    document.getElementById(`page-${page}`).classList.add('active');

    // 加载页面数据
    switch (page) {
        case 'dashboard':
            loadDashboardData();
            break;
        case 'vms':
            loadVMs();
            break;
        case 'firewall':
            loadFirewallRules();
            break;
        case 'logs':
            loadLogs();
            break;
    }
}

// 加载控制台数据
async function loadDashboardData() {
    try {
        const data = await apiRequest('/stats/system');

        if (data.success) {
            const stats = data.data;
            document.getElementById('cpu-usage').textContent = `${stats.cpu_usage.toFixed(1)}%`;
            document.getElementById('memory-usage').textContent = `${stats.memory_usage.toFixed(1)}%`;
            document.getElementById('disk-usage').textContent = `${stats.disk_usage.toFixed(1)}%`;
            document.getElementById('network-usage').textContent = `${(stats.network_rx + stats.network_tx).toFixed(1)} MB/s`;

            // 更新进度条
            document.querySelectorAll('.stat-bar-fill').forEach(bar => {
                const parent = bar.parentElement;
                const value = parent.previousElementSibling.textContent;
                const percentage = parseFloat(value) || 0;
                bar.style.width = `${percentage}%`;
            });
        }
    } catch (error) {
        console.error('Failed to load dashboard data:', error);
    }
}

// 加载虚拟机列表
async function loadVMs() {
    try {
        const data = await apiRequest('/vms');
        state.currentVms = data.data;

        renderVMList(data.data);
    } catch (error) {
        showToast(error.message, 'error');
        renderVMList([]);
    }
}

// 渲染虚拟机列表
function renderVMList(vms) {
    const container = document.getElementById('vm-list');

    if (vms.length === 0) {
        container.innerHTML = `
            <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="color: var(--text-secondary);">
                    <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                    <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                </svg>
                <p style="margin-top: 1rem; color: var(--text-secondary);">暂无虚拟机</p>
            </div>
        `;
        return;
    }

    container.innerHTML = vms.map(vm => `
        <div class="vm-card">
            <div class="vm-card-header">
                <div>
                    <div class="vm-name">${vm.name}</div>
                    <div class="vm-id">VMID: ${vm.vmid}</div>
                </div>
                <div class="vm-status ${vm.status}">
                    ${getStatusIcon(vm.status)} ${getStatusText(vm.status)}
                </div>
            </div>
            <div class="vm-specs">
                <div class="vm-spec">
                    <div class="vm-spec-label">CPU</div>
                    <div class="vm-spec-value">${vm.cpu_cores}</div>
                </div>
                <div class="vm-spec">
                    <div class="vm-spec-label">内存</div>
                    <div class="vm-spec-value">${formatMemory(vm.memory_mb)}</div>
                </div>
                <div class="vm-spec">
                    <div class="vm-spec-label">磁盘</div>
                    <div class="vm-spec-value">${vm.disk_gb} GB</div>
                </div>
            </div>
            <div class="vm-actions">
                ${vm.status === 'running' ? `
                    <button class="btn btn-warning" onclick="vmAction('${vm.id}', 'pause')">暂停</button>
                    <button class="btn btn-secondary" onclick="vmAction('${vm.id}', 'restart')">重启</button>
                    <button class="btn btn-danger" onclick="vmAction('${vm.id}', 'stop')">停止</button>
                ` : vm.status === 'paused' ? `
                    <button class="btn btn-success" onclick="vmAction('${vm.id}', 'unpause')">恢复</button>
                    <button class="btn btn-danger" onclick="vmAction('${vm.id}', 'stop')">停止</button>
                ` : `
                    <button class="btn btn-success" onclick="vmAction('${vm.id}', 'start')">启动</button>
                `}
            </div>
        </div>
    `).join('');
}

// 虚拟机操作
async function vmAction(vmId, action) {
    try {
        await apiRequest(`/vms/${vmId}/action`, {
            method: 'POST',
            body: JSON.stringify({ action })
        });

        showToast(`虚拟机${getActionText(action)}成功`, 'success');
        loadVMs();
    } catch (error) {
        showToast(error.message, 'error');
    }
}

// 处理创建虚拟机
async function handleCreateVM(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const vmData = {
        name: formData.get('name'),
        cpu_cores: parseInt(formData.get('cpu_cores')),
        memory_mb: parseInt(formData.get('memory_mb')),
        disk_gb: parseInt(formData.get('disk_gb')),
        node: formData.get('node')
    };

    try {
        await apiRequest('/vms', {
            method: 'POST',
            body: JSON.stringify(vmData)
        });

        showToast('虚拟机创建成功', 'success');
        document.getElementById('modal-create-vm').classList.add('hidden');
        e.target.reset();
        loadVMs();
    } catch (error) {
        showToast(error.message, 'error');
    }
}

// 加载防火墙规则
async function loadFirewallRules() {
    const container = document.getElementById('firewall-rules');

    // 示例数据
    const rules = [
        { id: 1, direction: 'inbound', action: 'accept', protocol: 'TCP', port: 22, source: '0.0.0.0/0' },
        { id: 2, direction: 'inbound', action: 'accept', protocol: 'TCP', port: 80, source: '0.0.0.0/0' },
        { id: 3, direction: 'inbound', action: 'accept', protocol: 'TCP', port: 443, source: '0.0.0.0/0' },
        { id: 4, direction: 'outbound', action: 'accept', protocol: 'any', port: null, source: null },
    ];

    container.innerHTML = rules.map(rule => `
        <div class="firewall-rule">
            <div class="firewall-rule-info">
                <div class="firewall-rule-detail">
                    <span class="firewall-label">方向</span>
                    <span class="firewall-value">${rule.direction.toUpperCase()}</span>
                </div>
                <div class="firewall-rule-detail">
                    <span class="firewall-label">动作</span>
                    <span class="firewall-value">${rule.action.toUpperCase()}</span>
                </div>
                <div class="firewall-rule-detail">
                    <span class="firewall-label">协议</span>
                    <span class="firewall-value">${rule.protocol}</span>
                </div>
                <div class="firewall-rule-detail">
                    <span class="firewall-label">端口</span>
                    <span class="firewall-value">${rule.port || '任意'}</span>
                </div>
                <div class="firewall-rule-detail">
                    <span class="firewall-label">来源</span>
                    <span class="firewall-value">${rule.source || '任意'}</span>
                </div>
            </div>
            <button class="btn btn-danger" style="padding: 0.5rem;" onclick="deleteFirewallRule('${rule.id}')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
            </button>
        </div>
    `).join('');
}

// 删除防火墙规则
function deleteFirewallRule(ruleId) {
    showToast('防火墙规则删除功能开发中', 'warning');
}

// 加载日志
async function loadLogs() {
    try {
        const data = await apiRequest('/audit-logs?limit=50');
        state.currentLogs = data.data;
        renderLogs(data.data);
    } catch (error) {
        showToast(error.message, 'error');
        renderLogs([]);
    }
}

// 渲染日志列表
function renderLogs(logs) {
    const container = document.getElementById('logs-list');

    if (logs.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 2rem;">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="color: var(--text-secondary);">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                <p style="margin-top: 1rem; color: var(--text-secondary);">暂无操作日志</p>
            </div>
        `;
        return;
    }

    container.innerHTML = logs.map(log => `
        <div class="log-entry ${log.action.toLowerCase().replace(/_/g, '')}">
            <div class="log-time">${formatDateTime(log.created_at)}</div>
            <div class="log-content">
                <div class="log-user">${log.resource_type}</div>
                <div class="log-action">${log.action} ${log.resource_id || ''}</div>
            </div>
        </div>
    `).join('');
}

// 显示提示消息
function showToast(message, type = 'info') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast ${type}`;
    toast.classList.remove('hidden');

    setTimeout(() => {
        toast.classList.add('hidden');
    }, 3000);
}

// 辅助函数
function getStatusIcon(status) {
    const icons = {
        running: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>',
        stopped: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="6" width="12" height="12"></rect></svg>',
        paused: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>'
    };
    return icons[status] || '';
}

function getStatusText(status) {
    const text = {
        running: '运行中',
        stopped: '已停止',
        paused: '已暂停'
    };
    return text[status] || status;
}

function getActionText(action) {
    const text = {
        start: '启动',
        stop: '停止',
        restart: '重启',
        pause: '暂停',
        unpause: '恢复'
    };
    return text[action] || action;
}

function formatMemory(mb) {
    if (mb >= 1024) {
        return `${(mb / 1024).toFixed(1)} GB`;
    }
    return `${mb} MB`;
}

function formatDateTime(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleString('zh-CN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
}
