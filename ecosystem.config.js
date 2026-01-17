module.exports = {
  apps: [
    {
      name: 'pve-manager',
      script: 'node_modules/next/dist/bin/next',
      args: 'start -p 5000',
      cwd: '/var/www/pve-manager',
      instances: 1,
      exec_mode: 'fork',
      autorestart: true,
      watch: false,
      max_memory_restart: '1G',
      env: {
        NODE_ENV: 'production',
        PORT: 5000,
      },
      // 环境变量会从 .env 文件加载
      env_file: '.env',
      error_file: './logs/pm2-error.log',
      out_file: './logs/pm2-out.log',
      log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
      // 健康检查
      health_check_grace_period: 10000,
      // 自动重启延迟
      min_uptime: '10s',
      max_restarts: 10,
    }
  ],
  deploy: {
    production: {
      user: 'node',
      host: 'your-server-ip',
      ref: 'origin/main',
      repo: 'git@github.com:username/pve-manager.git',
      path: '/var/www/pve-manager',
      'post-deploy': 'pnpm install && pnpm build && pm2 reload ecosystem.config.js --env production'
    }
  }
};
