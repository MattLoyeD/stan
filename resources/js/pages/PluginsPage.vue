<script setup lang="ts">
import { onMounted } from 'vue';
import { usePlugins } from '@/composables/usePlugins';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

const { plugins, loading, fetchPlugins, togglePlugin, removePlugin } = usePlugins();

onMounted(() => fetchPlugins());
</script>

<template>
    <div class="plugins-page">
        <h1>Plugins</h1>
        <Card>
            <template #content>
                <DataTable :value="plugins" :loading="loading" size="small" stripedRows>
                    <Column field="name" header="Name" />
                    <Column field="version" header="Version" />
                    <Column field="source" header="Source">
                        <template #body="{ data }">
                            <Tag :value="data.source" :severity="data.source === 'registry' ? 'info' : 'secondary'" />
                        </template>
                    </Column>
                    <Column field="description" header="Description" />
                    <Column field="is_active" header="Status">
                        <template #body="{ data }">
                            <Tag :value="data.is_active ? 'Active' : 'Disabled'" :severity="data.is_active ? 'success' : 'secondary'" />
                        </template>
                    </Column>
                    <Column header="Actions">
                        <template #body="{ data }">
                            <div class="action-buttons">
                                <Button :label="data.is_active ? 'Disable' : 'Enable'" size="small" :severity="data.is_active ? 'warn' : 'success'" @click="togglePlugin(data.id)" />
                                <Button icon="pi pi-trash" size="small" severity="danger" text @click="removePlugin(data.id)" />
                            </div>
                        </template>
                    </Column>
                    <template #empty>No plugins installed.</template>
                </DataTable>
            </template>
        </Card>
    </div>
</template>

<style scoped>
.plugins-page h1 { margin-bottom: 1rem; }
.action-buttons { display: flex; gap: 0.25rem; }
</style>
