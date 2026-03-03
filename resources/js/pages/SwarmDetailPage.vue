<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useSwarm } from '@/composables/useSwarm';
import { useObjective } from '@/composables/useObjective';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Card from 'primevue/card';
import ProgressBar from 'primevue/progressbar';
import Dialog from 'primevue/dialog';
import type { SwarmTask } from '@/types';

const route = useRoute();
const router = useRouter();
const { tasks, loading, fetchSwarmTasks } = useSwarm();
const { current, fetchObjective } = useObjective();

const objectiveId = Number(route.params.id);
const selectedTask = ref<SwarmTask | null>(null);
const showDetail = ref(false);
let interval: ReturnType<typeof setInterval>;

onMounted(async () => {
    await Promise.all([
        fetchObjective(objectiveId),
        fetchSwarmTasks(objectiveId),
    ]);
    interval = setInterval(() => {
        fetchObjective(objectiveId);
        fetchSwarmTasks(objectiveId);
    }, 3000);
});

onUnmounted(() => clearInterval(interval));

const completedCount = computed(() => tasks.value.filter(t => t.status === 'completed').length);
const totalCount = computed(() => tasks.value.length);
const progress = computed(() => totalCount.value ? Math.round(completedCount.value / totalCount.value * 100) : 0);

function statusSeverity(status: string) {
    const map: Record<string, string> = {
        pending: 'secondary', queued: 'info', running: 'info',
        completed: 'success', failed: 'danger', cancelled: 'contrast',
    };
    return map[status] || 'secondary';
}

function statusIcon(status: string) {
    const map: Record<string, string> = {
        pending: 'pi pi-clock', queued: 'pi pi-hourglass',
        running: 'pi pi-spin pi-spinner', completed: 'pi pi-check-circle',
        failed: 'pi pi-times-circle', cancelled: 'pi pi-minus-circle',
    };
    return map[status] || 'pi pi-circle';
}

function roleIcon(role: string) {
    const map: Record<string, string> = {
        researcher: 'pi pi-search', coder: 'pi pi-code',
        reviewer: 'pi pi-eye', analyst: 'pi pi-chart-bar',
        writer: 'pi pi-pencil', tester: 'pi pi-check-square',
    };
    return map[role] || 'pi pi-user';
}

function viewTask(task: SwarmTask) {
    selectedTask.value = task;
    showDetail.value = true;
}
</script>

<template>
    <div v-if="current" class="swarm-page">
        <div class="swarm-header">
            <div>
                <Button icon="pi pi-arrow-left" text @click="router.push(`/objectives/${objectiveId}`)" />
                <h1>Swarm: {{ current.title }}</h1>
                <Tag :value="current.status" :severity="statusSeverity(current.status)" />
            </div>
        </div>

        <Card class="progress-card">
            <template #content>
                <div class="progress-info">
                    <span>{{ completedCount }} / {{ totalCount }} tasks completed</span>
                    <span class="budget-text">{{ current.tokens_used.toLocaleString() }} / {{ current.token_budget.toLocaleString() }} tokens</span>
                </div>
                <ProgressBar :value="progress" />
            </template>
        </Card>

        <div class="task-grid">
            <Card v-for="task in tasks" :key="task.id" class="task-card" @click="viewTask(task)">
                <template #content>
                    <div class="task-card-header">
                        <div class="task-role">
                            <i :class="roleIcon(task.role)"></i>
                            <strong>{{ task.role }}</strong>
                        </div>
                        <Tag :value="task.status" :severity="statusSeverity(task.status)" size="small" />
                    </div>
                    <div class="task-seq">#{{ task.sequence }}</div>
                    <div class="task-goal">{{ task.goal }}</div>
                    <div class="task-meta">
                        <span v-if="task.token_budget">
                            <i class="pi pi-database"></i>
                            {{ task.tokens_used.toLocaleString() }} / {{ task.token_budget.toLocaleString() }}
                        </span>
                        <span v-if="task.depends_on?.length">
                            <i class="pi pi-link"></i>
                            depends on {{ task.depends_on.length }}
                        </span>
                    </div>
                    <div v-if="task.status === 'running'" class="task-running">
                        <ProgressBar mode="indeterminate" style="height: 4px" />
                    </div>
                </template>
            </Card>
        </div>

        <Card v-if="current.result_summary" class="result-card">
            <template #title>Synthesis</template>
            <template #content>
                <pre class="result-text">{{ current.result_summary }}</pre>
            </template>
        </Card>

        <Dialog v-model:visible="showDetail" :header="`Task #${selectedTask?.sequence}: ${selectedTask?.role}`" :style="{ width: '700px' }" modal>
            <div v-if="selectedTask" class="task-detail">
                <div class="detail-row">
                    <label>Status</label>
                    <Tag :value="selectedTask.status" :severity="statusSeverity(selectedTask.status)" />
                </div>
                <div class="detail-row">
                    <label>Goal</label>
                    <p>{{ selectedTask.goal }}</p>
                </div>
                <div class="detail-row">
                    <label>Instructions</label>
                    <p>{{ selectedTask.instructions }}</p>
                </div>
                <div v-if="selectedTask.allowed_tools?.length" class="detail-row">
                    <label>Allowed Tools</label>
                    <div class="tool-tags">
                        <Tag v-for="tool in selectedTask.allowed_tools" :key="tool" :value="tool" severity="info" size="small" />
                    </div>
                </div>
                <div class="detail-row">
                    <label>Tokens</label>
                    <span>{{ selectedTask.tokens_used.toLocaleString() }} / {{ selectedTask.token_budget.toLocaleString() }}</span>
                </div>
                <div v-if="selectedTask.result" class="detail-row">
                    <label>Result</label>
                    <pre class="result-text">{{ selectedTask.result }}</pre>
                </div>
                <div v-if="selectedTask.error" class="detail-row">
                    <label>Error</label>
                    <p class="error-text">{{ selectedTask.error }}</p>
                </div>
            </div>
        </Dialog>
    </div>
</template>

<style scoped>
.swarm-page { max-width: 1100px; margin: 0 auto; }
.swarm-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; }
.swarm-header > div { display: flex; align-items: center; gap: 0.75rem; }
.progress-card { margin-bottom: 1.5rem; }
.progress-info { display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.875rem; }
.budget-text { color: var(--stan-text-muted); }

.task-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.task-card { cursor: pointer; transition: transform 0.15s; }
.task-card:hover { transform: translateY(-2px); }
.task-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
.task-role { display: flex; align-items: center; gap: 0.5rem; }
.task-seq { font-size: 0.75rem; color: var(--stan-text-muted); margin-bottom: 0.25rem; }
.task-goal { font-size: 0.875rem; margin-bottom: 0.5rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.task-meta { display: flex; gap: 1rem; font-size: 0.75rem; color: var(--stan-text-muted); }
.task-meta i { font-size: 0.7rem; }
.task-running { margin-top: 0.5rem; }

.result-card { margin-top: 1.5rem; }
.result-text { white-space: pre-wrap; font-size: 0.875rem; }

.task-detail { display: flex; flex-direction: column; gap: 1rem; }
.detail-row { display: flex; flex-direction: column; gap: 0.25rem; }
.detail-row label { font-weight: 600; font-size: 0.875rem; color: var(--stan-text-muted); }
.detail-row p { margin: 0; }
.tool-tags { display: flex; gap: 0.25rem; flex-wrap: wrap; }
.error-text { color: var(--stan-danger); }
</style>
