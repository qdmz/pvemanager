'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { 
  Server, 
  Plus,
  RefreshCw,
  User,
  AlertTriangle
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
  node: string;
  userName?: string;
  serverName?: string;
}

export default function AdminVMsPage() {
  const router = useRouter();
  const [vms, setVMs] = useState<VirtualMachine[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchVMs();
  }, []);

  const fetchVMs = async () => {
    try {
      const response = await fetch('/api/admin/vms');
      if (!response.ok) {
        if (response.status === 401 || response.status === 403) {
          router.push('/dashboard');
          return;
        }
        throw new Error('获取虚拟机列表失败');
      }
      const data = await response.json();
      setVMs(data.vms || []);
    } catch (err) {
      console.error('Failed to fetch VMs:', err);
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
            已停止
          </Badge>
        );
      case 'paused':
        return (
          <Badge variant="outline">
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

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">虚拟机管理</h1>
          <p className="text-muted-foreground">
            管理所有虚拟机和容器
          </p>
        </div>
        <Button>
          <Plus className="mr-2 h-4 w-4" />
          新建虚拟机
        </Button>
      </div>

      {loading ? (
        <div className="flex items-center justify-center py-12">
          <RefreshCw className="h-8 w-8 animate-spin text-muted-foreground" />
        </div>
      ) : vms.length === 0 ? (
        <Card>
          <CardContent className="flex flex-col items-center justify-center py-12">
            <Server className="h-12 w-12 text-muted-foreground mb-4" />
            <h3 className="text-lg font-semibold mb-2">暂无虚拟机</h3>
            <p className="text-muted-foreground text-center mb-4">
              创建第一个虚拟机以开始管理
            </p>
            <Button>
              <Plus className="mr-2 h-4 w-4" />
              创建虚拟机
            </Button>
          </CardContent>
        </Card>
      ) : (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {vms.map((vm) => {
            const expired = isExpired(vm.expiresAt);

            return (
              <Card 
                key={vm.id} 
                className={`transition-all hover:shadow-lg ${expired ? 'border-red-300 bg-red-50/50' : ''}`}
              >
                <CardHeader>
                  <div className="flex items-start justify-between">
                    <div className="space-y-1">
                      <CardTitle className="text-lg">
                        <Link href={`/admin/vms/${vm.id}`} className="hover:underline">
                          {vm.name}
                        </Link>
                      </CardTitle>
                      <div className="flex items-center gap-2 text-sm text-muted-foreground">
                        <span className="flex items-center gap-1">
                          <User className="h-3 w-3" />
                          {vm.userName}
                        </span>
                        <span>·</span>
                        <span>{vm.type === 'vm' ? 'VM' : 'CT'}</span>
                        <span>·</span>
                        <span>{vm.serverName}</span>
                      </div>
                    </div>
                    <div className="flex flex-col gap-1">
                      {getStatusBadge(vm.status)}
                      {expired && (
                        <Badge variant="destructive" className="text-xs">
                          <AlertTriangle className="mr-1 h-2 w-2" />
                          已到期
                        </Badge>
                      )}
                    </div>
                  </div>
                </CardHeader>
                <CardContent className="space-y-3">
                  <div className="grid grid-cols-3 gap-2 text-sm">
                    <div className="flex flex-col items-center p-2 bg-muted rounded-lg">
                      <span className="font-medium">{vm.cpuCores}核</span>
                    </div>
                    <div className="flex flex-col items-center p-2 bg-muted rounded-lg">
                      <span className="font-medium">{vm.memory / 1024}G</span>
                    </div>
                    <div className="flex flex-col items-center p-2 bg-muted rounded-lg">
                      <span className="font-medium">{vm.diskSize}G</span>
                    </div>
                  </div>

                  {vm.ipAddress && (
                    <div className="text-sm text-muted-foreground font-mono">
                      {vm.ipAddress}
                    </div>
                  )}

                  {vm.expiresAt && (
                    <div className={`text-sm ${expired ? 'text-red-600' : 'text-muted-foreground'}`}>
                      {expired ? '已到期' : `到期: ${format(new Date(vm.expiresAt), 'yyyy-MM-dd', { locale: zhCN })}`}
                    </div>
                  )}

                  <div className="flex gap-2 pt-2">
                    <Link href={`/dashboard/vms/${vm.id}`} className="flex-1">
                      <Button size="sm" variant="outline" className="w-full">
                        查看详情
                      </Button>
                    </Link>
                    <Button size="sm" variant="outline" className="flex-1">
                      编辑
                    </Button>
                  </div>
                </CardContent>
              </Card>
            );
          })}
        </div>
      )}
    </div>
  );
}
