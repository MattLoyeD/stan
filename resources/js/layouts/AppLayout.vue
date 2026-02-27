<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuth } from '@/composables/useAuth';
import { useNativeMode } from '@/composables/useNativeMode';
import Menubar from 'primevue/menubar';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Toast from 'primevue/toast';

const router = useRouter();
const route = useRoute();
const { isAuthenticated, setToken, fetchUser, token } = useAuth();
const { isNative } = useNativeMode();
const tokenInput = ref('');

onMounted(async () => {
    if (isNative.value && !isAuthenticated.value) {
        try {
            const res = await fetch('/api/auth/auto-token');
            if (res.ok) {
                const data = await res.json();
                setToken(data.token);
            }
        } catch {
            // Sidecar may not be ready yet
        }
    }

    if (isAuthenticated.value) {
        await fetchUser();
        await checkFirstRun();
    }
});

async function checkFirstRun(): Promise<void> {
    if (route.name === 'setup') {
        return;
    }

    try {
        const res = await fetch('/api/setup/status');

        if (res.ok) {
            const data = await res.json();

            if (!data.has_provider) {
                router.push('/setup');
            }
        }
    } catch {
        // Ignore — status endpoint not available
    }
}

function authenticate() {
    setToken(tokenInput.value);
    fetchUser();
    tokenInput.value = '';
}

const menuItems = ref([
    { label: 'Dashboard', icon: 'pi pi-home', command: () => router.push('/') },
    { label: 'Objectives', icon: 'pi pi-flag', items: [
        { label: 'New Objective', icon: 'pi pi-plus', command: () => router.push('/objectives/create') },
    ]},
    { label: 'Sessions', icon: 'pi pi-code', items: [
        { label: 'New Session', icon: 'pi pi-plus', command: () => router.push('/sessions/create') },
    ]},
    { label: 'Plugins', icon: 'pi pi-box', command: () => router.push('/plugins') },
    { label: 'Channels', icon: 'pi pi-comments', command: () => router.push('/channels') },
    { label: 'Audit Log', icon: 'pi pi-shield', command: () => router.push('/audit') },
    { label: 'Settings', icon: 'pi pi-cog', command: () => router.push('/settings') },
]);
</script>

<template>
    <Toast />
    <div v-if="!isAuthenticated" class="auth-screen">
        <div class="auth-card">
            <h1>Stan</h1>
            <p>Enter your authentication token to continue.</p>
            <div class="auth-form">
                <InputText
                    v-model="tokenInput"
                    placeholder="Paste your auth token"
                    type="password"
                    class="auth-input"
                    @keyup.enter="authenticate"
                />
                <Button label="Connect" icon="pi pi-sign-in" @click="authenticate" />
            </div>
        </div>
    </div>
    <div v-else class="app-layout">
        <Menubar :model="menuItems" class="app-menubar">
            <template #start>
                <span class="app-logo" @click="router.push('/')">Stan</span>
            </template>
        </Menubar>
        <main class="app-main">
            <router-view />
        </main>
    </div>
</template>

<style scoped>
.auth-screen {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100vh;
}

.auth-card {
    text-align: center;
    padding: 3rem;
    border-radius: 12px;
    background: var(--stan-bg-surface);
    border: 1px solid var(--stan-border);
    max-width: 400px;
    width: 100%;
}

.auth-card h1 {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    color: var(--stan-primary);
}

.auth-card p {
    color: var(--stan-text-muted);
    margin-bottom: 1.5rem;
}

.auth-form {
    display: flex;
    gap: 0.5rem;
}

.auth-input {
    flex: 1;
}

.app-layout {
    display: flex;
    flex-direction: column;
    height: 100vh;
}

.app-menubar {
    border-radius: 0;
    border-left: 0;
    border-right: 0;
    border-top: 0;
}

.app-logo {
    font-weight: 700;
    font-size: 1.25rem;
    color: var(--stan-primary);
    cursor: pointer;
    margin-right: 1rem;
}

.app-main {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
}
</style>
