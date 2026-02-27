<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useAuth } from '@/composables/useAuth';
import type { LlmProvider } from '@/types';
import { useToast } from 'primevue/usetoast';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

const toast = useToast();
const { authHeaders } = useAuth();
const providers = ref<LlmProvider[]>([]);
const soulContent = ref('');

const newProvider = ref({ provider: 'anthropic', api_key: '', default_model: '', is_default: false });
const providerOptions = [
    { label: 'Anthropic', value: 'anthropic' },
    { label: 'OpenAI', value: 'openai' },
    { label: 'Ollama', value: 'ollama' },
    { label: 'Gemini', value: 'gemini' },
    { label: 'Mistral', value: 'mistral' },
    { label: 'Groq', value: 'groq' },
    { label: 'DeepSeek', value: 'deepseek' },
];

onMounted(async () => {
    await fetchProviders();
    await fetchSoul();
});

async function fetchProviders() {
    const res = await fetch('/api/providers', { headers: authHeaders() });
    const json = await res.json();
    providers.value = json.data;
}

async function addProvider() {
    await fetch('/api/providers', {
        method: 'POST', headers: authHeaders(),
        body: JSON.stringify(newProvider.value),
    });
    newProvider.value = { provider: 'anthropic', api_key: '', default_model: '', is_default: false };
    await fetchProviders();
    toast.add({ severity: 'success', summary: 'Provider added', life: 3000 });
}

async function testProvider(id: number) {
    const res = await fetch(`/api/providers/${id}/test`, { method: 'POST', headers: authHeaders() });
    const json = await res.json();
    toast.add({ severity: json.status === 'ok' ? 'success' : 'error', summary: json.message, life: 3000 });
}

async function removeProvider(id: number) {
    await fetch(`/api/providers/${id}`, { method: 'DELETE', headers: authHeaders() });
    await fetchProviders();
}

async function fetchSoul() {
    const res = await fetch('/api/settings/soul', { headers: authHeaders() });
    const json = await res.json();
    soulContent.value = json.content;
}

async function saveSoul() {
    await fetch('/api/settings/soul', {
        method: 'POST', headers: authHeaders(),
        body: JSON.stringify({ content: soulContent.value }),
    });
    toast.add({ severity: 'success', summary: 'SOUL.md saved', life: 3000 });
}
</script>

<template>
    <div class="settings-page">
        <h1>Settings</h1>
        <Tabs value="0">
            <TabList>
                <Tab value="0">Providers</Tab>
                <Tab value="1">SOUL.md</Tab>
                <Tab value="2">Security</Tab>
            </TabList>
            <TabPanels>
                <TabPanel value="0">
                    <Card class="provider-form-card">
                        <template #title>Add Provider</template>
                        <template #content>
                            <div class="provider-form">
                                <Select v-model="newProvider.provider" :options="providerOptions" optionLabel="label" optionValue="value" />
                                <InputText v-model="newProvider.api_key" type="password" placeholder="API Key" />
                                <InputText v-model="newProvider.default_model" placeholder="Default Model" />
                                <Button label="Add" icon="pi pi-plus" @click="addProvider" />
                            </div>
                        </template>
                    </Card>
                    <Card>
                        <template #title>Configured Providers</template>
                        <template #content>
                            <DataTable :value="providers" size="small">
                                <Column field="provider" header="Provider" />
                                <Column field="default_model" header="Model" />
                                <Column field="is_default" header="Default">
                                    <template #body="{ data }"><Tag v-if="data.is_default" value="Default" severity="info" /></template>
                                </Column>
                                <Column field="has_api_key" header="Key">
                                    <template #body="{ data }">
                                        <i :class="data.has_api_key ? 'pi pi-check' : 'pi pi-times'" :style="{ color: data.has_api_key ? 'var(--stan-success)' : 'var(--stan-danger)' }" />
                                    </template>
                                </Column>
                                <Column header="Actions">
                                    <template #body="{ data }">
                                        <div class="action-buttons">
                                            <Button label="Test" size="small" severity="info" @click="testProvider(data.id)" />
                                            <Button icon="pi pi-trash" size="small" severity="danger" text @click="removeProvider(data.id)" />
                                        </div>
                                    </template>
                                </Column>
                            </DataTable>
                        </template>
                    </Card>
                </TabPanel>

                <TabPanel value="1">
                    <Card>
                        <template #title>Agent Personality</template>
                        <template #content>
                            <Textarea v-model="soulContent" rows="20" class="w-full soul-editor" />
                            <div class="soul-actions">
                                <Button label="Save SOUL.md" icon="pi pi-save" @click="saveSoul" />
                            </div>
                        </template>
                    </Card>
                </TabPanel>

                <TabPanel value="2">
                    <Card>
                        <template #title>Security Configuration</template>
                        <template #content>
                            <p>Security settings are configured in <code>config/stan.php</code>.</p>
                        </template>
                    </Card>
                </TabPanel>
            </TabPanels>
        </Tabs>
    </div>
</template>

<style scoped>
.settings-page h1 { margin-bottom: 1rem; }
.provider-form-card { margin-bottom: 1rem; }
.provider-form { display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; }
.action-buttons { display: flex; gap: 0.25rem; }
.soul-editor { font-family: monospace; font-size: 0.875rem; }
.soul-actions { margin-top: 1rem; display: flex; justify-content: flex-end; }
.w-full { width: 100%; }
</style>
