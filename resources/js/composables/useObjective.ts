import { ref } from 'vue';
import { useAuth } from './useAuth';
import type { Objective, Step } from '@/types';

export function useObjective() {
    const { authHeaders } = useAuth();
    const objectives = ref<Objective[]>([]);
    const current = ref<Objective | null>(null);
    const steps = ref<Step[]>([]);
    const loading = ref(false);

    async function fetchObjectives() {
        loading.value = true;
        const res = await fetch('/api/objectives', { headers: authHeaders() });
        const json = await res.json();
        objectives.value = json.data;
        loading.value = false;
    }

    async function fetchObjective(id: number) {
        loading.value = true;
        const res = await fetch(`/api/objectives/${id}`, { headers: authHeaders() });
        const json = await res.json();
        current.value = json.data;
        steps.value = json.data.steps || [];
        loading.value = false;
    }

    async function createObjective(data: Partial<Objective>) {
        const res = await fetch('/api/objectives', {
            method: 'POST',
            headers: authHeaders(),
            body: JSON.stringify(data),
        });
        return res.json();
    }

    async function pauseObjective(id: number) {
        await fetch(`/api/objectives/${id}/pause`, { method: 'POST', headers: authHeaders() });
    }

    async function resumeObjective(id: number) {
        await fetch(`/api/objectives/${id}/resume`, { method: 'POST', headers: authHeaders() });
    }

    async function cancelObjective(id: number) {
        await fetch(`/api/objectives/${id}/cancel`, { method: 'POST', headers: authHeaders() });
    }

    return {
        objectives, current, steps, loading,
        fetchObjectives, fetchObjective, createObjective,
        pauseObjective, resumeObjective, cancelObjective,
    };
}
