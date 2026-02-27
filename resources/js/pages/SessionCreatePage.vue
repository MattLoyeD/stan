<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useSession } from '@/composables/useSession';
import { useToast } from 'primevue/usetoast';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Button from 'primevue/button';

const router = useRouter();
const toast = useToast();
const { createSession } = useSession();

const form = ref({
    title: '',
    project_path: '',
    token_budget: 200000,
});

async function submit() {
    if (!form.value.title || !form.value.project_path) {
        toast.add({ severity: 'warn', summary: 'Required', detail: 'Title and project path are required', life: 3000 });
        return;
    }

    const result = await createSession(form.value);

    if (result.data) {
        router.push(`/sessions/${result.data.id}`);
    } else {
        toast.add({ severity: 'error', summary: 'Error', detail: result.error || 'Failed to create session', life: 5000 });
    }
}
</script>

<template>
    <div class="create-page">
        <h1>New Coding Session</h1>
        <Card>
            <template #content>
                <div class="form-grid">
                    <div class="field">
                        <label>Session Title</label>
                        <InputText v-model="form.title" placeholder="e.g., Working on API" class="w-full" />
                    </div>
                    <div class="field">
                        <label>Project Directory</label>
                        <InputText v-model="form.project_path" placeholder="/home/user/projects/my-app" class="w-full" />
                        <small>Absolute path to the project directory. Stan will have sandboxed access to this directory only.</small>
                    </div>
                    <div class="field">
                        <label>Token Budget</label>
                        <InputNumber v-model="form.token_budget" :min="1000" :max="1000000" :step="10000" />
                    </div>
                    <div class="actions">
                        <Button label="Cancel" severity="secondary" @click="router.push('/')" />
                        <Button label="Start Session" icon="pi pi-play" @click="submit" />
                    </div>
                </div>
            </template>
        </Card>
    </div>
</template>

<style scoped>
.create-page { max-width: 700px; margin: 0 auto; }
.create-page h1 { margin-bottom: 1rem; }
.form-grid { display: flex; flex-direction: column; gap: 1rem; }
.field { display: flex; flex-direction: column; gap: 0.25rem; }
.field label { font-weight: 600; font-size: 0.875rem; color: var(--stan-text-muted); }
.field small { color: var(--stan-text-muted); font-size: 0.75rem; }
.actions { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem; }
.w-full { width: 100%; }
</style>
