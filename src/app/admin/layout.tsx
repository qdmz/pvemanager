'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { DashboardNav } from '@/components/dashboard-nav';
import { verifyToken } from '@/lib/auth';

export default function AdminLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const router = useRouter();

  useEffect(() => {
    const token = document.cookie
      .split('; ')
      .find(row => row.startsWith('auth-token='))
      ?.split('=')[1];

    if (!token) {
      router.push('/login');
      return;
    }

    const decoded = verifyToken(token);
    if (!decoded || decoded.role !== 'admin') {
      router.push('/dashboard');
      return;
    }
  }, [router]);

  return (
    <div className="min-h-screen bg-background">
      <DashboardNav />
      <main className="container mx-auto p-6">
        {children}
      </main>
    </div>
  );
}
