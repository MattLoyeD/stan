import { ref } from 'vue';
import { useAuth } from './useAuth';
import type { SwarmTask } from '@/types';

export function useSwarm() {
    const { authHeaders } = useAuth();
    const tasks = ref<SwarmTask[]>([]);
    const currentTask = ref<SwarmTask | null>(null);
    const loading = ref(false);

    async function fetchSwarmTasks(objectiveId: number) {
        loading.value = true;
        const res = await fetch(`/api/objectives/${objectiveId}/swarm-tasks`, { headers: authHeaders() });
        const json = await res.json();
        tasks.value = json.data;
        loading.value = false;
    }

    async function fetchSwarmTask(taskId: number) {
        loading.value = true;
        const res = await fetch(`/api/swarm-tasks/${taskId}`, { headers: authHeaders() });
        const json = await res.json();
        currentTask.value = json.data;
        loading.value = false;
    }

    return {
        tasks, currentTask, loading,
        fetchSwarmTasks, fetchSwarmTask,
    };
}
