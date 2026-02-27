<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useObjective } from '@/composables/useObjective';
import { useToast } from 'primevue/usetoast';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import InputNumber from 'primevue/inputnumber';
import Button from 'primevue/button';
import Select from 'primevue/select';

const router = useRouter();
const toast = useToast();
const { createObjective } = useObjective();

const form = ref({
    title: '',
    goal: '',
    token_budget: 100000,
    llm_provider: null as string | null,
    llm_model: null as string | null,
});

const providers = [
    { label: 'Anthropic', value: 'anthropic' },
    { label: 'OpenAI', value: 'openai' },
    { label: 'Ollama', value: 'ollama' },
];

async function submit() {
    if (!form.value.title || !form.value.goal) {
        toast.add({ severity: 'warn', summary: 'Required', detail: 'Title and goal are required', life: 3000 });
        return;
    }

    const result = await createObjective(form.value);
    toast.add({ severity: 'success', summary: 'Created', detail: 'Objective created and queued', life: 3000 });
    router.push(`/objectives/${result.data.id}`);
}
</script>

<template>
    <div class="create-page">
        <h1>New Objective</h1>
        <Card>
            <template #content>
                <div class="form-grid">
                    <div class="field">
                        <label>Title</label>
                        <InputText v-model="form.title" placeholder="e.g., Research PHP frameworks" class="w-full" />
                    </div>
                    <div class="field">
                        <label>Goal</label>
                        <Textarea v-model="form.goal" rows="5" placeholder="Describe what you want to achieve..." class="w-full" />
                    </div>
                    <div class="field-row">
                        <div class="field">
                            <label>Token Budget</label>
                            <InputNumber v-model="form.token_budget" :min="1000" :max="1000000" :step="10000" />
                        </div>
                        <div class="field">
                            <label>Provider</label>
                            <Select v-model="form.llm_provider" :options="providers" optionLabel="label" optionValue="value" placeholder="Default" />
                        </div>
                    </div>
                    <div class="actions">
                        <Button label="Cancel" severity="secondary" @click="router.push('/')" />
                        <Button label="Create Objective" icon="pi pi-play" @click="submit" />
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
.field-row { display: flex; gap: 1rem; }
.field-row .field { flex: 1; }
.actions { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem; }
.w-full { width: 100%; }
</style>
