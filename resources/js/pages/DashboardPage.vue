<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '@/composables/useAuth';
import type { Dashboard } from '@/types';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

const router = useRouter();
const { authHeaders } = useAuth();
const dashboard = ref<Dashboard | null>(null);

onMounted(async () => {
    const res = await fetch('/api/dashboard', { headers: authHeaders() });
    dashboard.value = await res.json();
});

function statusSeverity(status: string) {
    const map: Record<string, string> = {
        running: 'info', completed: 'success', failed: 'danger',
        paused: 'warn', pending: 'secondary', cancelled: 'contrast',
    };
    return map[status] || 'secondary';
}
</script>

<template>
    <div v-if="dashboard" class="dashboard">
        <div class="dashboard-header">
            <h1>Dashboard</h1>
            <div class="dashboard-actions">
                <Button label="New Objective" icon="pi pi-plus" @click="router.push('/objectives/create')" />
                <Button label="New Session" icon="pi pi-code" severity="secondary" @click="router.push('/sessions/create')" />
            </div>
        </div>

        <div class="stats-grid">
            <Card class="stat-card">
                <template #content>
                    <div class="stat-value">{{ dashboard.objectives.total }}</div>
                    <div class="stat-label">Total Objectives</div>
                </template>
            </Card>
            <Card class="stat-card">
                <template #content>
                    <div class="stat-value running">{{ dashboard.objectives.running }}</div>
                    <div class="stat-label">Running</div>
                </template>
            </Card>
            <Card class="stat-card">
                <template #content>
                    <div class="stat-value success">{{ dashboard.sessions.active }}</div>
                    <div class="stat-label">Active Sessions</div>
                </template>
            </Card>
            <Card class="stat-card">
                <template #content>
                    <div class="stat-value">{{ dashboard.tokens.total_used.toLocaleString() }}</div>
                    <div class="stat-label">Tokens Used</div>
                </template>
            </Card>
        </div>

        <div class="dashboard-grid">
            <Card>
                <template #title>Recent Objectives</template>
                <template #content>
                    <DataTable :value="dashboard.recent_objectives" :rows="5" size="small">
                        <Column field="title" header="Title">
                            <template #body="{ data }">
                                <a class="link" @click="router.push(`/objectives/${data.id}`)">{{ data.title }}</a>
                            </template>
                        </Column>
                        <Column field="status" header="Status">
                            <template #body="{ data }">
                                <Tag :value="data.status" :severity="statusSeverity(data.status)" />
                            </template>
                        </Column>
                        <Column header="Tokens">
                            <template #body="{ data }">
                                {{ data.tokens_used.toLocaleString() }} / {{ data.token_budget.toLocaleString() }}
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <Card>
                <template #title>Active Sessions</template>
                <template #content>
                    <DataTable :value="dashboard.active_sessions" :rows="5" size="small">
                        <Column field="title" header="Title">
                            <template #body="{ data }">
                                <a class="link" @click="router.push(`/sessions/${data.id}`)">{{ data.title }}</a>
                            </template>
                        </Column>
                        <Column field="project_path" header="Project" />
                    </DataTable>
                </template>
            </Card>
        </div>
    </div>
</template>

<style scoped>
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.dashboard-actions {
    display: flex;
    gap: 0.5rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    text-align: center;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
}

.stat-value.running { color: var(--stan-primary); }
.stat-value.success { color: var(--stan-success); }

.stat-label {
    color: var(--stan-text-muted);
    font-size: 0.875rem;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.link {
    color: var(--stan-primary);
    cursor: pointer;
    text-decoration: none;
}

.link:hover {
    text-decoration: underline;
}
</style>
