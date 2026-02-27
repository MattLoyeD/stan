import { ref, computed } from 'vue';
import type { User } from '@/types';

const token = ref<string>(localStorage.getItem('stan_token') || '');
const user = ref<User | null>(null);

export function useAuth() {
    const isAuthenticated = computed(() => !!token.value);

    function setToken(newToken: string) {
        token.value = newToken;
        localStorage.setItem('stan_token', newToken);
    }

    async function fetchUser() {
        if (!token.value) return;

        const res = await fetch('/api/auth/user', {
            headers: { Authorization: `Bearer ${token.value}` },
        });

        if (res.ok) {
            user.value = await res.json();
        }
    }

    function logout() {
        token.value = '';
        user.value = null;
        localStorage.removeItem('stan_token');
    }

    function authHeaders(): Record<string, string> {
        return { Authorization: `Bearer ${token.value}`, 'Content-Type': 'application/json' };
    }

    return { token, user, isAuthenticated, setToken, fetchUser, logout, authHeaders };
}
