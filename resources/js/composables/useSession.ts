import { ref } from 'vue';
import { useAuth } from './useAuth';
import type { CodingSession, SessionMessage } from '@/types';

export function useSession() {
    const { authHeaders } = useAuth();
    const sessions = ref<CodingSession[]>([]);
    const current = ref<CodingSession | null>(null);
    const messages = ref<SessionMessage[]>([]);
    const loading = ref(false);

    async function fetchSessions() {
        loading.value = true;
        const res = await fetch('/api/sessions', { headers: authHeaders() });
        const json = await res.json();
        sessions.value = json.data;
        loading.value = false;
    }

    async function fetchSession(id: number) {
        loading.value = true;
        const res = await fetch(`/api/sessions/${id}`, { headers: authHeaders() });
        const json = await res.json();
        current.value = json.data;
        loading.value = false;
    }

    async function fetchMessages(sessionId: number) {
        const res = await fetch(`/api/sessions/${sessionId}/messages`, { headers: authHeaders() });
        const json = await res.json();
        messages.value = json.data;
    }

    async function sendMessage(sessionId: number, message: string) {
        await fetch(`/api/sessions/${sessionId}/messages`, {
            method: 'POST',
            headers: authHeaders(),
            body: JSON.stringify({ message }),
        });
    }

    async function createSession(data: Partial<CodingSession>) {
        const res = await fetch('/api/sessions', {
            method: 'POST',
            headers: authHeaders(),
            body: JSON.stringify(data),
        });
        return res.json();
    }

    async function closeSession(id: number) {
        await fetch(`/api/sessions/${id}/close`, { method: 'POST', headers: authHeaders() });
    }

    return {
        sessions, current, messages, loading,
        fetchSessions, fetchSession, fetchMessages,
        sendMessage, createSession, closeSession,
    };
}
