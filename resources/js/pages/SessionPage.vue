<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { useRoute } from 'vue-router';
import { useSession } from '@/composables/useSession';
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';

const route = useRoute();
const sessionId = Number(route.params.id);
const { current, messages, fetchSession, fetchMessages, sendMessage, closeSession } = useSession();
const input = ref('');
const chatContainer = ref<HTMLElement>();
let interval: ReturnType<typeof setInterval>;

onMounted(async () => {
    await fetchSession(sessionId);
    await fetchMessages(sessionId);
    scrollToBottom();
    interval = setInterval(async () => {
        await fetchMessages(sessionId);
        scrollToBottom();
    }, 2000);
});

onUnmounted(() => clearInterval(interval));

async function send() {
    if (!input.value.trim()) return;
    const msg = input.value;
    input.value = '';
    await sendMessage(sessionId, msg);
    await fetchMessages(sessionId);
    scrollToBottom();
}

function scrollToBottom() {
    nextTick(() => {
        if (chatContainer.value) {
            chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
        }
    });
}
</script>

<template>
    <div v-if="current" class="session-page">
        <div class="session-header">
            <div>
                <h1>{{ current.title }}</h1>
                <Tag :value="current.status" :severity="current.status === 'active' ? 'success' : 'secondary'" />
                <span class="project-path">{{ current.project_path }}</span>
            </div>
            <div class="session-actions">
                <span class="token-info">{{ current.tokens_used.toLocaleString() }} / {{ current.token_budget.toLocaleString() }} tokens</span>
                <Button v-if="current.status === 'active'" label="Close Session" icon="pi pi-times" severity="danger" size="small" @click="closeSession(sessionId)" />
            </div>
        </div>

        <div class="chat-container" ref="chatContainer">
            <div v-for="msg in messages" :key="msg.id" :class="['message', msg.role]">
                <div class="message-role">{{ msg.role }}</div>
                <div class="message-content">{{ msg.content }}</div>
                <div v-if="msg.tool_calls" class="message-tools">
                    <div v-for="(call, i) in msg.tool_calls" :key="i" class="tool-call">
                        Tool: {{ (call as any).name || 'unknown' }}
                    </div>
                </div>
                <div class="message-meta">
                    {{ new Date(msg.created_at).toLocaleTimeString() }}
                    <span v-if="msg.input_tokens"> | {{ msg.input_tokens + msg.output_tokens }} tokens</span>
                </div>
            </div>
        </div>

        <div v-if="current.status === 'active'" class="chat-input">
            <InputText v-model="input" placeholder="Type a message..." class="chat-input-field" @keyup.enter="send" />
            <Button icon="pi pi-send" @click="send" />
        </div>
    </div>
</template>

<style scoped>
.session-page { display: flex; flex-direction: column; height: calc(100vh - 80px); }
.session-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; }
.session-header > div:first-child { display: flex; align-items: center; gap: 0.75rem; }
.session-actions { display: flex; align-items: center; gap: 1rem; }
.project-path { font-size: 0.875rem; color: var(--stan-text-muted); }
.token-info { font-size: 0.875rem; color: var(--stan-text-muted); }
.chat-container { flex: 1; overflow-y: auto; padding: 1rem; background: var(--stan-bg-surface); border-radius: 8px; display: flex; flex-direction: column; gap: 0.75rem; }
.message { padding: 0.75rem 1rem; border-radius: 8px; max-width: 80%; }
.message.user { align-self: flex-end; background: var(--stan-primary); color: white; }
.message.assistant { align-self: flex-start; background: var(--stan-bg-dark); }
.message-role { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; opacity: 0.7; margin-bottom: 0.25rem; }
.message-content { white-space: pre-wrap; word-break: break-word; }
.message-tools { margin-top: 0.5rem; font-size: 0.75rem; opacity: 0.7; }
.tool-call { padding: 2px 0; }
.message-meta { font-size: 0.7rem; opacity: 0.5; margin-top: 0.25rem; }
.chat-input { display: flex; gap: 0.5rem; margin-top: 0.75rem; }
.chat-input-field { flex: 1; }
</style>
