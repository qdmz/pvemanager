'use client';

import { useEffect, useState } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { 
  Power, 
  PowerOff, 
  RefreshCw, 
  MonitorPlay,
  Terminal,
  Network,
  HardDrive,
  Cpu,
  Calendar,
  ArrowLeft,
  AlertTriangle,
  Copy,
  ExternalLink
} from 'lucide-react';
import { format } from 'date-fns';
import { zhCN } from 'date-fns/locale';

interface VirtualMachine {
  id: number;
  vmId: number;
  type: 'vm' | 'ct';
  name: string;
  status: 'running' | 'stopped' | 'paused';
  cpuCores: number;
  memory: number;
  diskSize: number;
  template?: string;
  ipAddress?: string;
  gateway?: string;
  dnsServer?: string;
  natEnabled: boolean;
  natPortForward?: { hostPort: number; vmPort: number; protocol: 'tcp' | 'udp' }[];
  expiresAt?: string;
  autoShutdownOnExpiry: boolean;
  createdAt: string;
  updatedAt: string;
  node: string;
  serverName?: string;
  serverHost?: string;
  serverPort?: number;
}

export default function VMDetailPage() {
  const params = useParams();
  const router = useRouter();
  const [vm, setVM] = useState<VirtualMachine | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [operationLoading, setOperationLoading] = useState<string | null>(null);

  useEffect(() => {
    fetchVM();
  }, [params.id]);

  const fetchVM = async () => {
    try {
      const response = await fetch(`/api/vms/${params.id}`);
      if (!response.ok) {
        if (response.status === 401) {
          router.push('/login');
          return;
        }
        throw new Error('获取虚拟机详情失败');
      }
      const data = await response.json();
      setVM(data.vm);
    } catch (err) {
      setError(err instanceof Error ? err.message : '获取虚拟机详情失败');
    } finally {
      setLoading(false);
    }
  };

  const handleOperation = async (operation: string) => {
    if (!vm) return;
    if (operation !== 'start' && !confirm(`确定要执行 ${operation} 操作吗？`)) {
      return;
    }

    setOperationLoading(operation);
    try {
      const response = await fetch(`/api/vms/${vm.id}/operations`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ operation }),
      });

      const data = await response.json();
      if (!response.ok) {
        throw new Error(data.error || '操作失败');
      }

      // Refresh VM status after operation
      setTimeout(() => {
        fetchVM();
      }, 1000);
    } catch (err) {
      setError(err instanceof Error ? err.message : '操作失败');
    } finally {
      setOperationLoading(null);
    }
  };

  const copyToClipboard = (text: string) => {
    navigator.clipboard.writeText(text);
    alert('已复制到剪贴板');
  };

  const isExpired = (expiresAt?: string) => {
    if (!expiresAt) return false;
    return new Date(expiresAt) < new Date();
  };

  const getDaysRemaining = (expiresAt?: string) => {
    if (!expiresAt) return null;
    const days = Math.ceil((new Date(expiresAt).getTime() - Date.now()) / (1000 * 60 * 60 * 24));
    return days;
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <div className="text-center">
          <RefreshCw className="mx-auto h-8 w-8 animate-spin text-muted-foreground" />
          <p className="mt-4 text-muted-foreground">加载中...</p>
        </div>
      </div>
    );
  }

  if (error || !vm) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[400px]">
        <AlertTriangle className="h-12 w-12 text-red-500 mb-4" />
        <h2 className="text-xl font-semibold mb-2">加载失败</h2>
        <p className="text-muted-foreground mb-4">{error || '虚拟机不存在'}</p>
        <Button onClick={() => router.back()}>
          <ArrowLeft className="mr-2 h-4 w-4" />
          返回
        </Button>
      </div>
    );
  }

  const expired = isExpired(vm.expiresAt);
  const daysRemaining = getDaysRemaining(vm.expiresAt);

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-start justify-between">
        <div className="space-y-2">
          <div className="flex items-center gap-4">
            <Button variant="ghost" size="sm" onClick={() => router.back()}>
              <ArrowLeft className="mr-2 h-4 w-4" />
              返回
            </Button>
            <div>
              <h1 className="text-3xl font-bold">{vm.name}</h1>
              <p className="text-muted-foreground">
                {vm.type === 'vm' ? '虚拟机' : '容器'} · {vm.serverName} · 节点: {vm.node}
              </p>
            </div>
          </div>
          <div className="flex items-center gap-3">
            {getStatusBadge(vm.status)}
            {expired && (
              <Badge variant="destructive">
                <AlertTriangle className="mr-1 h-3 w-3" />
                已到期
              </Badge>
            )}
          </div>
        </div>
        <div className="flex gap-2">
          <Button variant="outline">
            <Terminal className="mr-2 h-4 w-4" />
            Web Shell
          </Button>
          <Button variant="outline">
            <MonitorPlay className="mr-2 h-4 w-4" />
            VNC
          </Button>
        </div>
      </div>

      {error && (
        <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
          {error}
        </div>
      )}

      {/* Quick Actions */}
      <Card>
        <CardHeader>
          <CardTitle>快速操作</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="flex flex-wrap gap-3">
            {vm.status === 'running' ? (
              <>
                <Button
                  onClick={() => handleOperation('restart')}
                  disabled={operationLoading !== null}
                >
                  <RefreshCw className="mr-2 h-4 w-4" />
                  {operationLoading === 'restart' ? '重启中...' : '重启'}
                </Button>
                <Button
                  variant="destructive"
                  onClick={() => handleOperation('shutdown')}
                  disabled={operationLoading !== null}
                >
                  <PowerOff className="mr-2 h-4 w-4" />
                  {operationLoading === 'shutdown' ? '关机中...' : '关机'}
                </Button>
                <Button
                  variant="outline"
                  onClick={() => handleOperation('stop')}
                  disabled={operationLoading !== null}
                >
                  <PowerOff className="mr-2 h-4 w-4" />
                  {operationLoading === 'stop' ? '强制停止中...' : '强制停止'}
                </Button>
              </>
            ) : (
              <Button
                onClick={() => handleOperation('start')}
                disabled={expired || operationLoading !== null}
              >
                <Power className="mr-2 h-4 w-4" />
                {operationLoading === 'start' ? '启动中...' : expired ? '已到期' : '启动'}
              </Button>
            )}
          </div>
        </CardContent>
      </Card>

      <div className="grid gap-6 md:grid-cols-2">
        {/* Basic Info */}
        <Card>
          <CardHeader>
            <CardTitle>基本信息</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-2 gap-4 text-sm">
              <div>
                <p className="text-muted-foreground">类型</p>
                <p className="font-medium">{vm.type === 'vm' ? '虚拟机' : '容器'}</p>
              </div>
              <div>
                <p className="text-muted-foreground">VM ID</p>
                <p className="font-medium">{vm.vmId}</p>
              </div>
              <div>
                <p className="text-muted-foreground">状态</p>
                <p className="font-medium">{getStatusBadge(vm.status)}</p>
              </div>
              <div>
                <p className="text-muted-foreground">节点</p>
                <p className="font-medium">{vm.node}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Resources */}
        <Card>
          <CardHeader>
            <CardTitle>资源配置</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-3 gap-4 text-sm">
              <div className="flex flex-col items-center p-4 bg-muted rounded-lg">
                <Cpu className="h-6 w-6 text-muted-foreground mb-2" />
                <p className="text-muted-foreground text-sm">CPU</p>
                <p className="font-semibold text-lg">{vm.cpuCores} 核</p>
              </div>
              <div className="flex flex-col items-center p-4 bg-muted rounded-lg">
                <MonitorPlay className="h-6 w-6 text-muted-foreground mb-2" />
                <p className="text-muted-foreground text-sm">内存</p>
                <p className="font-semibold text-lg">{vm.memory / 1024} GB</p>
              </div>
              <div className="flex flex-col items-center p-4 bg-muted rounded-lg">
                <HardDrive className="h-6 w-6 text-muted-foreground mb-2" />
                <p className="text-muted-foreground text-sm">磁盘</p>
                <p className="font-semibold text-lg">{vm.diskSize} GB</p>
              </div>
            </div>
            {vm.template && (
              <div className="pt-2">
                <p className="text-muted-foreground text-sm">系统模板</p>
                <p className="font-medium">{vm.template}</p>
              </div>
            )}
          </CardContent>
        </Card>

        {/* Network */}
        <Card>
          <CardHeader>
            <CardTitle>网络配置</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {vm.ipAddress ? (
              <>
                <div>
                  <p className="text-muted-foreground text-sm mb-2">IP 地址</p>
                  <div className="flex items-center gap-2">
                    <code className="flex-1 bg-muted px-3 py-2 rounded text-sm font-mono">
                      {vm.ipAddress}
                    </code>
                    <Button
                      size="sm"
                      variant="outline"
                      onClick={() => copyToClipboard(vm.ipAddress!)}
                    >
                      <Copy className="h-4 w-4" />
                    </Button>
                  </div>
                </div>
                {vm.gateway && (
                  <div>
                    <p className="text-muted-foreground text-sm mb-2">网关</p>
                    <code className="bg-muted px-3 py-2 rounded text-sm font-mono block">
                      {vm.gateway}
                    </code>
                  </div>
                )}
                {vm.dnsServer && (
                  <div>
                    <p className="text-muted-foreground text-sm mb-2">DNS 服务器</p>
                    <code className="bg-muted px-3 py-2 rounded text-sm font-mono block">
                      {vm.dnsServer}
                    </code>
                  </div>
                )}
                {vm.natEnabled && vm.natPortForward && vm.natPortForward.length > 0 && (
                  <div className="pt-2 border-t">
                    <p className="text-muted-foreground text-sm mb-2 flex items-center gap-1">
                      <Network className="h-3 w-3" />
                      NAT 端口转发
                    </p>
                    <div className="space-y-2">
                      {vm.natPortForward.map((pf, i) => (
                        <div key={i} className="flex items-center gap-2 text-sm">
                          <span className="font-mono bg-muted px-2 py-1 rounded">
                            {pf.protocol.toUpperCase()}
                          </span>
                          <span className="text-muted-foreground">主机:</span>
                          <span className="font-mono">{pf.hostPort}</span>
                          <span className="text-muted-foreground">→</span>
                          <span className="font-mono">{pf.vmPort}</span>
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </>
            ) : (
              <div className="text-center py-4 text-muted-foreground text-sm">
                暂无网络配置
              </div>
            )}
          </CardContent>
        </Card>

        {/* Expiry */}
        <Card>
          <CardHeader>
            <CardTitle>到期时间</CardTitle>
          </CardHeader>
          <CardContent>
            {vm.expiresAt ? (
              <div className="space-y-2">
                <div className={`flex items-center gap-2 text-lg ${expired ? 'text-red-600' : ''}`}>
                  <Calendar className="h-5 w-5" />
                  <span className="font-medium">
                    {format(new Date(vm.expiresAt), 'yyyy-MM-dd HH:mm:ss', { locale: zhCN })}
                  </span>
                </div>
                {expired ? (
                  <div className="flex items-center gap-2 text-red-600">
                    <AlertTriangle className="h-4 w-4" />
                    <span className="font-medium">此虚拟机已到期</span>
                  </div>
                ) : daysRemaining !== null && (
                  <div className={`flex items-center gap-2 ${daysRemaining <= 7 ? 'text-orange-600' : 'text-muted-foreground'}`}>
                    {daysRemaining <= 7 && <AlertTriangle className="h-4 w-4" />}
                    <span className="font-medium">
                      {daysRemaining === 0 ? '今天到期' : `${daysRemaining} 天后到期`}
                    </span>
                  </div>
                )}
                {vm.autoShutdownOnExpiry && (
                  <p className="text-sm text-muted-foreground">
                    到期后将自动关机
                  </p>
                )}
              </div>
            ) : (
              <div className="text-center py-4 text-muted-foreground text-sm">
                无到期限制
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}

function getStatusBadge(status: string) {
  switch (status) {
    case 'running':
      return (
        <Badge variant="default" className="bg-green-500">
          <div className="mr-1 h-2 w-2 rounded-full bg-white animate-pulse" />
          运行中
        </Badge>
      );
    case 'stopped':
      return (
        <Badge variant="secondary">
          <PowerOff className="mr-1 h-3 w-3" />
          已停止
        </Badge>
      );
    case 'paused':
      return (
        <Badge variant="outline">
          <span className="mr-1 h-2 w-2 rounded-full bg-yellow-500" />
          已暂停
        </Badge>
      );
    default:
      return <Badge variant="outline">{status}</Badge>;
  }
}
