<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useAuth } from '@/composables/useAuth';
import type { ToolExecution } from '@/types';
import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Select from 'primevue/select';

const { authHeaders } = useAuth();
const executions = ref<ToolExecution[]>([]);
const loading = ref(false);
const riskFilter = ref<string | null>(null);

const riskOptions = [
    { label: 'All', value: null },
    { label: 'Low', value: 'low' },
    { label: 'Medium', value: 'medium' },
    { label: 'High', value: 'high' },
    { label: 'Critical', value: 'critical' },
];

onMounted(() => fetchExecutions());

async function fetchExecutions() {
    loading.value = true;
    let url = '/api/tool-executions';
    if (riskFilter.value) url += `?risk_level=${riskFilter.value}`;
    const res = await fetch(url, { headers: authHeaders() });
    const json = await res.json();
    executions.value = json.data;
    loading.value = false;
}

function riskSeverity(level: string) {
    const map: Record<string, string> = { low: 'success', medium: 'info', high: 'warn', critical: 'danger' };
    return map[level] || 'secondary';
}
</script>

<template>
    <div class="audit-page">
        <div class="audit-header">
            <h1>Audit Log</h1>
            <Select v-model="riskFilter" :options="riskOptions" optionLabel="label" optionValue="value" placeholder="Risk Level" @change="fetchExecutions" />
        </div>
        <Card>
            <template #content>
                <DataTable :value="executions" :loading="loading" paginator :rows="20" size="small" stripedRows>
                    <Column field="tool_name" header="Tool" />
                    <Column field="tool_category" header="Category">
                        <template #body="{ data }"><Tag :value="data.tool_category" /></template>
                    </Column>
                    <Column field="risk_level" header="Risk">
                        <template #body="{ data }"><Tag :value="data.risk_level" :severity="riskSeverity(data.risk_level)" /></template>
                    </Column>
                    <Column field="guardian_passed" header="Guardian">
                        <template #body="{ data }">
                            <i :class="data.guardian_passed ? 'pi pi-check-circle' : 'pi pi-times-circle'" :style="{ color: data.guardian_passed ? 'var(--stan-success)' : 'var(--stan-danger)' }" />
                        </template>
                    </Column>
                    <Column field="duration_ms" header="Duration">
                        <template #body="{ data }">{{ data.duration_ms }}ms</template>
                    </Column>
                    <Column field="created_at" header="Time">
                        <template #body="{ data }">{{ new Date(data.created_at).toLocaleString() }}</template>
                    </Column>
                </DataTable>
            </template>
        </Card>
    </div>
</template>

<style scoped>
.audit-page h1 { margin-bottom: 1rem; }
.audit-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
</style>
