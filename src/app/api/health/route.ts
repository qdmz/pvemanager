import { NextResponse } from 'next/server';

/**
 * 健康检查 API
 * 用于 Docker 容器健康检查和负载均衡器检测
 */
export async function GET() {
  try {
    return NextResponse.json(
      {
        status: 'ok',
        timestamp: new Date().toISOString(),
        service: 'pve-manager',
        version: process.env.npm_package_version || '1.0.0',
      },
      { status: 200 }
    );
  } catch (error) {
    return NextResponse.json(
      {
        status: 'error',
        timestamp: new Date().toISOString(),
        error: 'Health check failed',
      },
      { status: 503 }
    );
  }
}
