'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { 
  Server, 
  Plus, 
  Edit, 
  Trash2,
  RefreshCw,
  CheckCircle2,
  XCircle
} from 'lucide-react';

interface PVEServer {
  id: number;
  name: string;
  host: string;
  port: number;
  username: string;
  apiToken: string;
  realm: string;
  isActive: boolean;
  createdAt: string;
  updatedAt: string;
}

export default function AdminServersPage() {
  const router = useRouter();
  const [servers, setServers] = useState<PVEServer[]>([]);
  const [loading, setLoading] = useState(true);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [formData, setFormData] = useState({
    name: '',
    host: '',
    port: 8006,
    username: '',
    apiToken: '',
    realm: 'pam',
  });

  useEffect(() => {
    fetchServers();
  }, []);

  const fetchServers = async () => {
    try {
      const response = await fetch('/api/admin/servers');
      if (!response.ok) {
        if (response.status === 401 || response.status === 403) {
          router.push('/dashboard');
          return;
        }
        throw new Error('获取服务器列表失败');
      }
      const data = await response.json();
      setServers(data.servers || []);
    } catch (err) {
      console.error('Failed to fetch servers:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const response = await fetch('/api/admin/servers', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData),
      });

      if (!response.ok) {
        throw new Error('创建服务器失败');
      }

      setDialogOpen(false);
      setFormData({
        name: '',
        host: '',
        port: 8006,
        username: '',
        apiToken: '',
        realm: 'pam',
      });
      fetchServers();
    } catch (err) {
      alert(err instanceof Error ? err.message : '创建服务器失败');
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm('确定要删除此服务器吗？')) return;

    try {
      const response = await fetch(`/api/admin/servers/${id}`, {
        method: 'DELETE',
      });

      if (!response.ok) {
        throw new Error('删除服务器失败');
      }

      fetchServers();
    } catch (err) {
      alert(err instanceof Error ? err.message : '删除服务器失败');
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">PVE 服务器管理</h1>
          <p className="text-muted-foreground">
            管理您的 Proxmox VE 服务器连接
          </p>
        </div>
        <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
          <DialogTrigger asChild>
            <Button>
              <Plus className="mr-2 h-4 w-4" />
              添加服务器
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>添加 PVE 服务器</DialogTitle>
              <DialogDescription>
                添加新的 Proxmox VE 服务器连接配置
              </DialogDescription>
            </DialogHeader>
            <form onSubmit={handleSubmit}>
              <div className="space-y-4 py-4">
                <div className="space-y-2">
                  <Label htmlFor="name">服务器名称</Label>
                  <Input
                    id="name"
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    placeholder="例如：主服务器"
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="host">主机地址</Label>
                  <Input
                    id="host"
                    value={formData.host}
                    onChange={(e) => setFormData({ ...formData, host: e.target.value })}
                    placeholder="例如：192.168.1.100"
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="port">端口</Label>
                  <Input
                    id="port"
                    type="number"
                    value={formData.port}
                    onChange={(e) => setFormData({ ...formData, port: parseInt(e.target.value) })}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="username">用户名</Label>
                  <Input
                    id="username"
                    value={formData.username}
                    onChange={(e) => setFormData({ ...formData, username: e.target.value })}
                    placeholder="PVE 用户名"
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="apiToken">API Token</Label>
                  <Input
                    id="apiToken"
                    value={formData.apiToken}
                    onChange={(e) => setFormData({ ...formData, apiToken: e.target.value })}
                    placeholder="API Token ID!Token Secret"
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="realm">Realm</Label>
                  <Input
                    id="realm"
                    value={formData.realm}
                    onChange={(e) => setFormData({ ...formData, realm: e.target.value })}
                    placeholder="例如：pam"
                    required
                  />
                </div>
              </div>
              <DialogFooter>
                <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>
                  取消
                </Button>
                <Button type="submit">添加</Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      {loading ? (
        <div className="flex items-center justify-center py-12">
          <RefreshCw className="h-8 w-8 animate-spin text-muted-foreground" />
        </div>
      ) : servers.length === 0 ? (
        <Card>
          <CardContent className="flex flex-col items-center justify-center py-12">
            <Server className="h-12 w-12 text-muted-foreground mb-4" />
            <h3 className="text-lg font-semibold mb-2">暂无服务器</h3>
            <p className="text-muted-foreground text-center mb-4">
              添加第一个 PVE 服务器以开始管理虚拟机
            </p>
            <Button onClick={() => setDialogOpen(true)}>
              <Plus className="mr-2 h-4 w-4" />
              添加服务器
            </Button>
          </CardContent>
        </Card>
      ) : (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {servers.map((server) => (
            <Card key={server.id}>
              <CardHeader>
                <div className="flex items-start justify-between">
                  <div>
                    <CardTitle className="flex items-center gap-2">
                      <Server className="h-5 w-5" />
                      {server.name}
                    </CardTitle>
                    <CardDescription className="mt-1">
                      {server.host}:{server.port}
                    </CardDescription>
                  </div>
                  {server.isActive ? (
                    <Badge variant="default" className="bg-green-500">
                      <CheckCircle2 className="mr-1 h-3 w-3" />
                      活跃
                    </Badge>
                  ) : (
                    <Badge variant="secondary">
                      <XCircle className="mr-1 h-3 w-3" />
                      停用
                    </Badge>
                  )}
                </div>
              </CardHeader>
              <CardContent className="space-y-2">
                <div className="text-sm space-y-1">
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">用户名:</span>
                    <span className="font-mono">{server.username}@{server.realm}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">API Token:</span>
                    <span className="font-mono text-xs truncate max-w-[200px]">
                      {server.apiToken.substring(0, 20)}...
                    </span>
                  </div>
                </div>
                <div className="flex gap-2 pt-2">
                  <Button size="sm" variant="outline" className="flex-1">
                    <Edit className="mr-1 h-3 w-3" />
                    编辑
                  </Button>
                  <Button
                    size="sm"
                    variant="outline"
                    className="flex-1"
                    onClick={() => handleDelete(server.id)}
                  >
                    <Trash2 className="mr-1 h-3 w-3" />
                    删除
                  </Button>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </div>
  );
}
