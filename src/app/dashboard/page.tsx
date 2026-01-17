'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { 
  Server, 
  Power, 
  PowerOff, 
  RefreshCw, 
  AlertTriangle,
  Plus,
  Calendar,
  HardDrive,
  Cpu,
  Monitor
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
  ipAddress?: string;
  expiresAt?: string;
  createdAt: string;
  serverName?: string;
  node: string;
}

export default function DashboardPage() {
  const router = useRouter();
  const [vms, setVms] = useState<VirtualMachine[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    fetchVMs();
  }, []);

  const fetchVMs = async () => {
    try {
      const response = await fetch('/api/vms');
      if (!response.ok) {
        if (response.status === 401) {
          router.push('/login');
          return;
        }
        throw new Error('获取虚拟机列表失败');
      }
      const data = await response.json();
      setVms(data.vms || []);
    } catch (err) {
      setError(err instanceof Error ? err.message : '获取虚拟机列表失败');
    } finally {
      setLoading(false);
    }
  };

  const getStatusBadge = (status: string) => {
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
            <Pause className="mr-1 h-3 w-3" />
            已暂停
          </Badge>
        );
      default:
        return <Badge variant="outline">{status}</Badge>;
    }
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

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">仪表盘</h1>
          <p className="text-muted-foreground">
            管理您的虚拟机和容器
          </p>
        </div>
        <Button>
          <Plus className="mr-2 h-4 w-4" />
          新建虚拟机
        </Button>
      </div>

      {error && (
        <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
          {error}
        </div>
      )}

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        {vms.map((vm) => {
          const expired = isExpired(vm.expiresAt);
          const daysRemaining = getDaysRemaining(vm.expiresAt);

          return (
            <Card 
              key={vm.id} 
              className={`transition-all hover:shadow-lg ${expired ? 'border-red-300 bg-red-50/50' : ''}`}
            >
              <CardHeader>
                <div className="flex items-start justify-between">
                  <div className="space-y-1">
                    <CardTitle className="text-lg">
                      <Link href={`/dashboard/vms/${vm.id}`} className="hover:underline">
                        {vm.name}
                      </Link>
                    </CardTitle>
                    <CardDescription className="flex items-center gap-2">
                      <Server className="h-3 w-3" />
                      {vm.type === 'vm' ? '虚拟机' : '容器'} · {vm.serverName}
                    </CardDescription>
                  </div>
                  {getStatusBadge(vm.status)}
                </div>
              </CardHeader>
              <CardContent className="space-y-4">
                {/* Resources */}
                <div className="grid grid-cols-3 gap-2 text-sm">
                  <div className="flex flex-col items-center p-2 bg-muted rounded-lg">
                    <Cpu className="h-4 w-4 text-muted-foreground mb-1" />
                    <span className="font-medium">{vm.cpuCores}核</span>
                  </div>
                  <div className="flex flex-col items-center p-2 bg-muted rounded-lg">
                    <Monitor className="h-4 w-4 text-muted-foreground mb-1" />
                    <span className="font-medium">{vm.memory / 1024}G</span>
                  </div>
                  <div className="flex flex-col items-center p-2 bg-muted rounded-lg">
                    <HardDrive className="h-4 w-4 text-muted-foreground mb-1" />
                    <span className="font-medium">{vm.diskSize}G</span>
                  </div>
                </div>

                {/* IP Address */}
                {vm.ipAddress && (
                  <div className="flex items-center text-sm text-muted-foreground">
                    <Monitor className="mr-2 h-3 w-3" />
                    {vm.ipAddress}
                  </div>
                )}

                {/* Expiry */}
                {vm.expiresAt && (
                  <div className={`flex items-center gap-2 text-sm ${expired ? 'text-red-600' : 'text-muted-foreground'}`}>
                    <Calendar className="h-3 w-3" />
                    {expired ? (
                      <span className="flex items-center text-red-600 font-medium">
                        <AlertTriangle className="mr-1 h-3 w-3" />
                        已到期
                      </span>
                    ) : daysRemaining !== null && daysRemaining <= 7 ? (
                      <span className="text-orange-600 font-medium">
                        {daysRemaining === 0 ? '今天到期' : `${daysRemaining} 天后到期`}
                      </span>
                    ) : (
                      <span>
                        到期时间: {format(new Date(vm.expiresAt), 'yyyy-MM-dd', { locale: zhCN })}
                      </span>
                    )}
                  </div>
                )}

                {/* Actions */}
                <div className="flex gap-2 pt-2">
                  {vm.status === 'running' ? (
                    <>
                      <Button size="sm" variant="outline" className="flex-1">
                        <RefreshCw className="mr-1 h-3 w-3" />
                        重启
                      </Button>
                      <Button size="sm" variant="outline" className="flex-1">
                        <PowerOff className="mr-1 h-3 w-3" />
                        关机
                      </Button>
                    </>
                  ) : (
                    <Button 
                      size="sm" 
                      className="flex-1" 
                      disabled={expired}
                    >
                      <Power className="mr-1 h-3 w-3" />
                      {expired ? '已到期' : '启动'}
                    </Button>
                  )}
                  <Link href={`/dashboard/vms/${vm.id}`} className="flex-1">
                    <Button size="sm" variant="outline" className="w-full">
                      详情
                    </Button>
                  </Link>
                </div>
              </CardContent>
            </Card>
          );
        })}
      </div>

      {vms.length === 0 && !error && (
        <Card>
          <CardContent className="flex flex-col items-center justify-center py-12">
            <Server className="h-12 w-12 text-muted-foreground mb-4" />
            <h3 className="text-lg font-semibold mb-2">暂无虚拟机</h3>
            <p className="text-muted-foreground text-center mb-4">
              您还没有任何虚拟机或容器
            </p>
            <Button>
              <Plus className="mr-2 h-4 w-4" />
              创建虚拟机
            </Button>
          </CardContent>
        </Card>
      )}
    </div>
  );
}

function Pause(props: React.SVGProps<SVGSVGElement>) {
  return (
    <svg
      {...props}
      xmlns="http://www.w3.org/2000/svg"
      width="24"
      height="24"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    >
      <rect x="6" y="4" width="4" height="16" />
      <rect x="14" y="4" width="4" height="16" />
    </svg>
  );
}
