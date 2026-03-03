import { ref } from 'vue';
import { useAuth } from './useAuth';
import type { McpServer } from '@/types';

export function useMcpServers() {
    const { authHeaders } = useAuth();
    const servers = ref<McpServer[]>([]);
    const loading = ref(false);

    async function fetchServers() {
        loading.value = true;
        const res = await fetch('/api/mcp-servers', { headers: authHeaders() });
        const json = await res.json();
        servers.value = json.data;
        loading.value = false;
    }

    async function createServer(data: Partial<McpServer>) {
        const res = await fetch('/api/mcp-servers', {
            method: 'POST',
            headers: authHeaders(),
            body: JSON.stringify(data),
        });
        return res.json();
    }

    async function deleteServer(id: number) {
        await fetch(`/api/mcp-servers/${id}`, { method: 'DELETE', headers: authHeaders() });
    }

    async function testServer(id: number) {
        const res = await fetch(`/api/mcp-servers/${id}/test`, {
            method: 'POST',
            headers: authHeaders(),
        });
        return res.json();
    }

    async function discoverTools(id: number) {
        const res = await fetch(`/api/mcp-servers/${id}/discover`, {
            method: 'POST',
            headers: authHeaders(),
        });
        return res.json();
    }

    async function toggleServer(id: number) {
        const res = await fetch(`/api/mcp-servers/${id}/toggle`, {
            method: 'POST',
            headers: authHeaders(),
        });
        return res.json();
    }

    return {
        servers, loading,
        fetchServers, createServer, deleteServer,
        testServer, discoverTools, toggleServer,
    };
}
