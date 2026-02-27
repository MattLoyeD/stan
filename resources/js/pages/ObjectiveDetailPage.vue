<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useObjective } from '@/composables/useObjective';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Timeline from 'primevue/timeline';
import MeterGroup from 'primevue/metergroup';
import ProgressBar from 'primevue/progressbar';

const route = useRoute();
const router = useRouter();
const { current, steps, loading, fetchObjective, pauseObjective, resumeObjective, cancelObjective } = useObjective();

const objectiveId = Number(route.params.id);
let interval: ReturnType<typeof setInterval>;

onMounted(async () => {
    await fetchObjective(objectiveId);
    interval = setInterval(() => fetchObjective(objectiveId), 3000);
});

onUnmounted(() => clearInterval(interval));

function statusSeverity(status: string) {
    const map: Record<string, string> = {
        running: 'info', completed: 'success', failed: 'danger',
        paused: 'warn', pending: 'secondary', planned: 'secondary',
        executing: 'info', skipped: 'contrast', cancelled: 'contrast',
    };
    return map[status] || 'secondary';
}

function statusIcon(status: string) {
    const map: Record<string, string> = {
        planned: 'pi pi-circle', executing: 'pi pi-spin pi-spinner',
        completed: 'pi pi-check-circle', failed: 'pi pi-times-circle', skipped: 'pi pi-minus-circle',
    };
    return map[status] || 'pi pi-circle';
}
</script>

<template>
    <div v-if="current" class="detail-page">
        <div class="detail-header">
            <div>
                <Button icon="pi pi-arrow-left" text @click="router.push('/')" />
                <h1>{{ current.title }}</h1>
                <Tag :value="current.status" :severity="statusSeverity(current.status)" />
            </div>
            <div class="detail-actions">
                <Button v-if="current.status === 'running'" label="Pause" icon="pi pi-pause" severity="warn" @click="pauseObjective(objectiveId)" />
                <Button v-if="current.status === 'paused'" label="Resume" icon="pi pi-play" @click="resumeObjective(objectiveId)" />
                <Button v-if="['running', 'paused', 'pending'].includes(current.status)" label="Cancel" icon="pi pi-times" severity="danger" @click="cancelObjective(objectiveId)" />
            </div>
        </div>

        <Card class="goal-card">
            <template #content>
                <div class="goal-text">{{ current.goal }}</div>
                <div class="budget-section">
                    <label>Token Budget</label>
                    <ProgressBar :value="Math.round(current.tokens_used / current.token_budget * 100)" />
                    <span class="budget-text">{{ current.tokens_used.toLocaleString() }} / {{ current.token_budget.toLocaleString() }}</span>
                </div>
            </template>
        </Card>

        <Card v-if="current.result_summary" class="result-card">
            <template #title>Result</template>
            <template #content>
                <pre class="result-text">{{ current.result_summary }}</pre>
            </template>
        </Card>

        <Card>
            <template #title>Steps</template>
            <template #content>
                <Timeline :value="steps" align="left">
                    <template #marker="{ item }">
                        <i :class="statusIcon(item.status)" :style="{ color: item.status === 'completed' ? 'var(--stan-success)' : item.status === 'failed' ? 'var(--stan-danger)' : '' }"></i>
                    </template>
                    <template #content="{ item }">
                        <div class="step-content">
                            <div class="step-header">
                                <strong>Step {{ item.sequence }}</strong>
                                <Tag :value="item.status" :severity="statusSeverity(item.status)" size="small" />
                                <span v-if="item.tool_name" class="step-tool">{{ item.tool_name }}</span>
                            </div>
                            <div class="step-reasoning">{{ item.reasoning }}</div>
                            <div v-if="item.observation" class="step-observation">{{ item.observation }}</div>
                            <div v-if="item.error" class="step-error">{{ item.error }}</div>
                            <div v-if="item.duration_ms" class="step-meta">
                                {{ item.duration_ms }}ms | {{ item.input_tokens + item.output_tokens }} tokens
                            </div>
                        </div>
                    </template>
                </Timeline>
            </template>
        </Card>
    </div>
</template>

<style scoped>
.detail-page { max-width: 900px; margin: 0 auto; }
.detail-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; }
.detail-header > div:first-child { display: flex; align-items: center; gap: 0.75rem; }
.detail-actions { display: flex; gap: 0.5rem; }
.goal-card { margin-bottom: 1rem; }
.goal-text { font-size: 1.1rem; margin-bottom: 1rem; }
.budget-section { margin-top: 1rem; }
.budget-section label { font-weight: 600; font-size: 0.875rem; color: var(--stan-text-muted); display: block; margin-bottom: 0.25rem; }
.budget-text { font-size: 0.875rem; color: var(--stan-text-muted); }
.result-card { margin-bottom: 1rem; }
.result-text { white-space: pre-wrap; font-size: 0.875rem; }
.step-content { padding: 0.5rem 0; }
.step-header { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem; }
.step-tool { font-size: 0.75rem; background: var(--stan-bg-dark); padding: 2px 8px; border-radius: 4px; }
.step-reasoning { color: var(--stan-text-muted); font-size: 0.875rem; }
.step-observation { margin-top: 0.5rem; font-size: 0.875rem; background: var(--stan-bg-dark); padding: 0.75rem; border-radius: 6px; white-space: pre-wrap; max-height: 200px; overflow-y: auto; }
.step-error { margin-top: 0.5rem; color: var(--stan-danger); font-size: 0.875rem; }
.step-meta { margin-top: 0.25rem; font-size: 0.75rem; color: var(--stan-text-muted); }
</style>
