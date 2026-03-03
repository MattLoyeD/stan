<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useMcpServers } from '@/composables/useMcpServers';
import { useToast } from 'primevue/usetoast';
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import type { McpServer, McpTool } from '@/types';

const toast = useToast();
const { servers, loading, fetchServers, createServer, deleteServer, testServer, discoverTools, toggleServer } = useMcpServers();

const showAddDialog = ref(false);
const showToolsDialog = ref(false);
const discoveredTools = ref<McpTool[]>([]);
const testing = ref<number | null>(null);
const discovering = ref<number | null>(null);

const form = ref({
    name: '',
    transport: 'stdio' as 'stdio' | 'sse',
    command: '',
    args: '',
    url: '',
    api_key: '',
    default_risk_level: 'high',
});

const transportOptions = [
    { label: 'Stdio (local process)', value: 'stdio' },
    { label: 'SSE (HTTP endpoint)', value: 'sse' },
];

const riskOptions = [
    { label: 'Low', value: 'low' },
    { label: 'Medium', value: 'medium' },
    { label: 'High', value: 'high' },
    { label: 'Critical', value: 'critical' },
];

onMounted(fetchServers);

async function handleAdd() {
    if (!form.value.name) {
        toast.add({ severity: 'warn', summary: 'Required', detail: 'Server name is required', life: 3000 });
        return;
    }

    const data: Record<string, unknown> = {
        name: form.value.name,
        transport: form.value.transport,
        default_risk_level: form.value.default_risk_level,
    };

    if (form.value.transport === 'stdio') {
        data.command = form.value.command;
        data.args = form.value.args ? form.value.args.split('\n').filter(Boolean) : [];
    } else {
        data.url = form.value.url;
        if (form.value.api_key) {
            data.api_key = form.value.api_key;
        }
    }

    await createServer(data as Partial<McpServer>);
    toast.add({ severity: 'success', summary: 'Added', detail: 'MCP server added', life: 3000 });
    showAddDialog.value = false;
    resetForm();
    await fetchServers();
}

async function handleTest(server: McpServer) {
    testing.value = server.id;
    const result = await testServer(server.id);
    testing.value = null;

    if (result.success) {
        toast.add({ severity: 'success', summary: 'Connected', detail: `${result.tools_count} tools available`, life: 3000 });
    } else {
        toast.add({ severity: 'error', summary: 'Failed', detail: result.message, life: 5000 });
    }
}

async function handleDiscover(server: McpServer) {
    discovering.value = server.id;
    const result = await discoverTools(server.id);
    discovering.value = null;

    if (result.tools) {
        discoveredTools.value = result.tools;
        showToolsDialog.value = true;
        await fetchServers();
    } else {
        toast.add({ severity: 'error', summary: 'Failed', detail: result.message, life: 5000 });
    }
}

async function handleToggle(server: McpServer) {
    await toggleServer(server.id);
    await fetchServers();
}

async function handleDelete(server: McpServer) {
    await deleteServer(server.id);
    toast.add({ severity: 'info', summary: 'Removed', detail: 'MCP server removed', life: 3000 });
    await fetchServers();
}

function resetForm() {
    form.value = { name: '', transport: 'stdio', command: '', args: '', url: '', api_key: '', default_risk_level: 'high' };
}
</script>

<template>
    <div class="mcp-page">
        <div class="page-header">
            <h1>MCP Servers</h1>
            <Button label="Add Server" icon="pi pi-plus" @click="showAddDialog = true" />
        </div>

        <Card>
            <template #content>
                <DataTable :value="servers" :loading="loading" stripedRows>
                    <template #empty>No MCP servers configured.</template>
                    <Column field="name" header="Name" />
                    <Column field="transport" header="Transport">
                        <template #body="{ data }">
                            <Tag :value="data.transport" :severity="data.transport === 'stdio' ? 'info' : 'warn'" />
                        </template>
                    </Column>
                    <Column field="default_risk_level" header="Risk">
                        <template #body="{ data }">
                            <Tag :value="data.default_risk_level" :severity="data.default_risk_level === 'high' ? 'danger' : data.default_risk_level === 'critical' ? 'danger' : 'warn'" />
                        </template>
                    </Column>
                    <Column header="Tools">
                        <template #body="{ data }">
                            {{ data.cached_tools?.length ?? 0 }}
                        </template>
                    </Column>
                    <Column header="Status">
                        <template #body="{ data }">
                            <Tag :value="data.is_active ? 'Active' : 'Inactive'" :severity="data.is_active ? 'success' : 'secondary'" />
                        </template>
                    </Column>
                    <Column header="Actions" style="width: 300px">
                        <template #body="{ data }">
                            <div class="action-buttons">
                                <Button icon="pi pi-bolt" text size="small" :loading="testing === data.id" @click="handleTest(data)" v-tooltip="'Test'" />
                                <Button icon="pi pi-search" text size="small" :loading="discovering === data.id" @click="handleDiscover(data)" v-tooltip="'Discover tools'" />
                                <Button :icon="data.is_active ? 'pi pi-pause' : 'pi pi-play'" text size="small" @click="handleToggle(data)" v-tooltip="data.is_active ? 'Disable' : 'Enable'" />
                                <Button icon="pi pi-trash" text severity="danger" size="small" @click="handleDelete(data)" v-tooltip="'Remove'" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </template>
        </Card>

        <Dialog v-model:visible="showAddDialog" header="Add MCP Server" :style="{ width: '500px' }" modal>
            <div class="dialog-form">
                <div class="field">
                    <label>Name</label>
                    <InputText v-model="form.name" placeholder="e.g., filesystem, github" class="w-full" />
                </div>
                <div class="field">
                    <label>Transport</label>
                    <Select v-model="form.transport" :options="transportOptions" optionLabel="label" optionValue="value" class="w-full" />
                </div>
                <template v-if="form.transport === 'stdio'">
                    <div class="field">
                        <label>Command</label>
                        <InputText v-model="form.command" placeholder="e.g., npx @modelcontextprotocol/server-filesystem" class="w-full" />
                    </div>
                    <div class="field">
                        <label>Arguments (one per line)</label>
                        <Textarea v-model="form.args" rows="3" placeholder="/tmp&#10;/home" class="w-full" />
                    </div>
                </template>
                <template v-if="form.transport === 'sse'">
                    <div class="field">
                        <label>URL</label>
                        <InputText v-model="form.url" placeholder="https://mcp-server.example.com" class="w-full" />
                    </div>
                    <div class="field">
                        <label>API Key (optional)</label>
                        <InputText v-model="form.api_key" type="password" class="w-full" />
                    </div>
                </template>
                <div class="field">
                    <label>Default Risk Level</label>
                    <Select v-model="form.default_risk_level" :options="riskOptions" optionLabel="label" optionValue="value" class="w-full" />
                </div>
                <div class="dialog-actions">
                    <Button label="Cancel" severity="secondary" @click="showAddDialog = false" />
                    <Button label="Add Server" icon="pi pi-plus" @click="handleAdd" />
                </div>
            </div>
        </Dialog>

        <Dialog v-model:visible="showToolsDialog" header="Discovered Tools" :style="{ width: '600px' }" modal>
            <DataTable :value="discoveredTools" stripedRows>
                <Column field="name" header="Name" />
                <Column field="description" header="Description" />
            </DataTable>
        </Dialog>
    </div>
</template>

<style scoped>
.mcp-page { max-width: 1000px; margin: 0 auto; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.action-buttons { display: flex; gap: 0.25rem; }
.dialog-form { display: flex; flex-direction: column; gap: 1rem; }
.field { display: flex; flex-direction: column; gap: 0.25rem; }
.field label { font-weight: 600; font-size: 0.875rem; color: var(--stan-text-muted); }
.dialog-actions { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 0.5rem; }
.w-full { width: 100%; }
</style>
