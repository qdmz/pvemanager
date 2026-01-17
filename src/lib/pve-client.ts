import { getDb } from '@/db';
import { pveServers } from '@/db/schema';
import { eq } from 'drizzle-orm';

export interface PVEConfig {
  host: string;
  port: number;
  username: string;
  apiToken: string;
  realm: string;
}

export class PVEClient {
  private config: PVEConfig;

  constructor(config: PVEConfig) {
    this.config = config;
  }

  private getBaseUrl(): string {
    return `https://${this.config.host}:${this.config.port}/api2/json`;
  }

  private getHeaders(): HeadersInit {
    return {
      'Authorization': `PVEAPIToken=${this.config.username}@${this.config.realm}!${this.config.apiToken}`,
    };
  }

  async request(path: string, options?: RequestInit): Promise<any> {
    const url = `${this.getBaseUrl()}${path}`;
    const response = await fetch(url, {
      ...options,
      headers: {
        ...this.getHeaders(),
        ...(options?.headers || {}),
      },
    });

    if (!response.ok) {
      const error = await response.text();
      throw new Error(`PVE API Error: ${response.status} - ${error}`);
    }

    return response.json();
  }

  async getNodes(): Promise<any> {
    return this.request('/nodes');
  }

  async getNodeVMs(node: string): Promise<any> {
    return this.request(`/nodes/${node}/qemu`);
  }

  async getNodeCTs(node: string): Promise<any> {
    return this.request(`/nodes/${node}/lxc`);
  }

  async getVMStatus(node: string, vmid: number): Promise<any> {
    return this.request(`/nodes/${node}/qemu/${vmid}/status/current`);
  }

  async getCTStatus(node: string, vmid: number): Promise<any> {
    return this.request(`/nodes/${node}/lxc/${vmid}/status/current`);
  }

  async startVM(node: string, vmid: number): Promise<any> {
    return this.request(`/nodes/${node}/qemu/${vmid}/status/start`, {
      method: 'POST',
    });
  }

  async stopVM(node: string, vmid: number): Promise<any> {
    return this.request(`/nodes/${node}/qemu/${vmid}/status/stop`, {
      method: 'POST',
    });
  }

  async restartVM(node: string, vmid: number): Promise<any> {
    return this.request(`/nodes/${node}/qemu/${vmid}/status/reboot`, {
      method: 'POST',
    });
  }

  async shutdownVM(node: string, vmid: number): Promise<any> {
    return this.request(`/nodes/${node}/qemu/${vmid}/status/shutdown`, {
      method: 'POST',
    });
  }

  async startCT(node: string, vmid: number): Promise<any> {
    return this.request(`/nodes/${node}/lxc/${vmid}/status/start`, {
      method: 'POST',
    });
  }

  async stopCT(node: string, vmid: number): Promise<any> {
    return this.request(`/nodes/${node}/lxc/${vmid}/status/stop`, {
      method: 'POST',
    });
  }

  async restartCT(node: string, vmid: number): Promise<any> {
    return this.request(`/nodes/${node}/lxc/${vmid}/status/reboot`, {
      method: 'POST',
    });
  }

  async shutdownCT(node: string, vmid: number): Promise<any> {
    return this.request(`/nodes/${node}/lxc/${vmid}/status/shutdown`, {
      method: 'POST',
    });
  }

  async getVMConfig(node: string, vmid: number): Promise<any> {
    return this.request(`/nodes/${node}/qemu/${vmid}/config`);
  }

  async getCTConfig(node: string, vmid: number): Promise<any> {
    return this.request(`/nodes/${node}/lxc/${vmid}/config`);
  }

  async updateVMConfig(node: string, vmid: number, config: Record<string, any>): Promise<any> {
    return this.request(`/nodes/${node}/qemu/${vmid}/config`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(config),
    });
  }

  async updateCTConfig(node: string, vmid: number, config: Record<string, any>): Promise<any> {
    return this.request(`/nodes/${node}/lxc/${vmid}/config`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(config),
    });
  }

  async getVMVNCWebSocket(node: string, vmid: number): Promise<any> {
    return this.request(`/nodes/${node}/qemu/${vmid}/vncwebsocket`, {
      method: 'POST',
    });
  }

  async getCTVNCWebSocket(node: string, vmid: number): Promise<any> {
    return this.request(`/nodes/${node}/lxc/${vmid}/vncwebsocket`, {
      method: 'POST',
    });
  }

  async deleteVM(node: string, vmid: number): Promise<any> {
    return this.request(`/nodes/${node}/qemu/${vmid}`, {
      method: 'DELETE',
    });
  }

  async deleteCT(node: string, vmid: number): Promise<any> {
    return this.request(`/nodes/${node}/lxc/${vmid}`, {
      method: 'DELETE',
    });
  }
}

export async function getPVEClient(serverId: number): Promise<PVEClient> {
  const db = getDb();
  const [server] = await db.select().from(pveServers).where(eq(pveServers.id, serverId));

  if (!server) {
    throw new Error('PVE 服务器不存在');
  }

  return new PVEClient({
    host: server.host,
    port: server.port,
    username: server.username,
    apiToken: server.apiToken,
    realm: server.realm,
  });
}
