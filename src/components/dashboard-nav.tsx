'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useRouter } from 'next/navigation';
import { Button } from '@/components/ui/button';
import { 
  LayoutDashboard, 
  Server, 
  Users, 
  Settings, 
  LogOut,
  User
} from 'lucide-react';
import { useEffect, useState } from 'react';

export function DashboardNav() {
  const pathname = usePathname();
  const router = useRouter();
  const [user, setUser] = useState<{ username: string; role: string } | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchUser();
  }, []);

  const fetchUser = async () => {
    try {
      const response = await fetch('/api/auth/me');
      if (response.ok) {
        const data = await response.json();
        setUser(data.user);
      } else {
        router.push('/login');
      }
    } catch (error) {
      console.error('Failed to fetch user:', error);
      router.push('/login');
    } finally {
      setLoading(false);
    }
  };

  const handleLogout = async () => {
    try {
      await fetch('/api/auth/logout', { method: 'POST' });
      router.push('/login');
    } catch (error) {
      console.error('Logout error:', error);
    }
  };

  const navItems = [
    {
      href: '/dashboard',
      label: '仪表盘',
      icon: LayoutDashboard,
    },
    ...(user?.role === 'admin' ? [
      {
        href: '/admin/servers',
        label: 'PVE 服务器',
        icon: Server,
      },
      {
        href: '/admin/users',
        label: '用户管理',
        icon: Users,
      },
    ] : []),
    {
      href: '/settings',
      label: '个人设置',
      icon: Settings,
    },
  ];

  if (loading) {
    return null;
  }

  return (
    <nav className="flex items-center justify-between border-b bg-card px-6 py-4">
      <div className="flex items-center gap-6">
        <Link href="/dashboard" className="text-xl font-bold">
          PVE 管理系统
        </Link>
        <div className="flex items-center gap-1">
          {navItems.map((item) => {
            const Icon = item.icon;
            const isActive = pathname === item.href;
            return (
              <Link key={item.href} href={item.href}>
                <Button
                  variant={isActive ? 'default' : 'ghost'}
                  className="gap-2"
                >
                  <Icon className="h-4 w-4" />
                  {item.label}
                </Button>
              </Link>
            );
          })}
        </div>
      </div>
      <div className="flex items-center gap-4">
        <div className="flex items-center gap-2 text-sm text-muted-foreground">
          <User className="h-4 w-4" />
          {user?.username}
          {user?.role === 'admin' && (
            <span className="rounded-full bg-primary px-2 py-0.5 text-xs text-primary-foreground">
              管理员
            </span>
          )}
        </div>
        <Button variant="outline" size="sm" onClick={handleLogout}>
          <LogOut className="mr-2 h-4 w-4" />
          退出
        </Button>
      </div>
    </nav>
  );
}
