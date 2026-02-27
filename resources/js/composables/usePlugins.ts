import { ref } from 'vue';
import { useAuth } from './useAuth';
import type { Plugin } from '@/types';

export function usePlugins() {
    const { authHeaders } = useAuth();
    const plugins = ref<Plugin[]>([]);
    const loading = ref(false);

    async function fetchPlugins() {
        loading.value = true;
        const res = await fetch('/api/plugins', { headers: authHeaders() });
        const json = await res.json();
        plugins.value = json.data;
        loading.value = false;
    }

    async function togglePlugin(id: number) {
        const res = await fetch(`/api/plugins/${id}/toggle`, { method: 'POST', headers: authHeaders() });
        return res.json();
    }

    async function removePlugin(id: number) {
        await fetch(`/api/plugins/${id}`, { method: 'DELETE', headers: authHeaders() });
    }

    return { plugins, loading, fetchPlugins, togglePlugin, removePlugin };
}
