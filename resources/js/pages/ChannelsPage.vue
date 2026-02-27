<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useAuth } from '@/composables/useAuth';
import type { Channel } from '@/types';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Select from 'primevue/select';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

const { authHeaders } = useAuth();
const channels = ref<Channel[]>([]);
const newChannelType = ref('telegram');
const pairingToken = ref('');

const channelTypes = [
    { label: 'Telegram', value: 'telegram' },
    { label: 'Slack', value: 'slack' },
    { label: 'WhatsApp', value: 'whatsapp' },
    { label: 'Signal', value: 'signal' },
    { label: 'Teams', value: 'teams' },
];

onMounted(fetchChannels);

async function fetchChannels() {
    const res = await fetch('/api/channels', { headers: authHeaders() });
    const json = await res.json();
    channels.value = json.data;
}

async function createChannel() {
    const res = await fetch('/api/channels', {
        method: 'POST',
        headers: authHeaders(),
        body: JSON.stringify({ type: newChannelType.value }),
    });
    const json = await res.json();
    pairingToken.value = json.pairing_token;
    await fetchChannels();
}

async function removeChannel(id: number) {
    await fetch(`/api/channels/${id}`, { method: 'DELETE', headers: authHeaders() });
    await fetchChannels();
}
</script>

<template>
    <div class="channels-page">
        <h1>Messaging Channels</h1>

        <Card class="add-channel-card">
            <template #content>
                <div class="add-channel-form">
                    <Select v-model="newChannelType" :options="channelTypes" optionLabel="label" optionValue="value" />
                    <Button label="Add Channel" icon="pi pi-plus" @click="createChannel" />
                </div>
                <div v-if="pairingToken" class="pairing-info">
                    <strong>Pairing Token:</strong>
                    <code>{{ pairingToken }}</code>
                    <p>Use this token to pair your messaging app with Stan.</p>
                </div>
            </template>
        </Card>

        <Card>
            <template #content>
                <DataTable :value="channels" size="small" stripedRows>
                    <Column field="type" header="Type">
                        <template #body="{ data }">
                            <Tag :value="data.type" />
                        </template>
                    </Column>
                    <Column field="is_active" header="Status">
                        <template #body="{ data }">
                            <Tag :value="data.is_active ? 'Paired' : 'Pending'" :severity="data.is_active ? 'success' : 'warn'" />
                        </template>
                    </Column>
                    <Column field="paired_at" header="Paired At">
                        <template #body="{ data }">
                            {{ data.paired_at ? new Date(data.paired_at).toLocaleDateString() : '-' }}
                        </template>
                    </Column>
                    <Column header="Actions">
                        <template #body="{ data }">
                            <Button icon="pi pi-trash" size="small" severity="danger" text @click="removeChannel(data.id)" />
                        </template>
                    </Column>
                    <template #empty>No channels configured.</template>
                </DataTable>
            </template>
        </Card>
    </div>
</template>

<style scoped>
.channels-page h1 { margin-bottom: 1rem; }
.add-channel-card { margin-bottom: 1rem; }
.add-channel-form { display: flex; gap: 0.5rem; align-items: center; }
.pairing-info { margin-top: 1rem; padding: 1rem; background: var(--stan-bg-dark); border-radius: 6px; }
.pairing-info code { display: block; font-size: 1.1rem; margin: 0.5rem 0; color: var(--stan-primary); }
</style>
