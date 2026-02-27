import { createRouter, createWebHistory } from 'vue-router';

const routes = [
    {
        path: '/',
        component: () => import('@/layouts/AppLayout.vue'),
        children: [
            { path: '', name: 'dashboard', component: () => import('@/pages/DashboardPage.vue') },
            { path: 'objectives/create', name: 'objective-create', component: () => import('@/pages/ObjectiveCreatePage.vue') },
            { path: 'objectives/:id', name: 'objective-detail', component: () => import('@/pages/ObjectiveDetailPage.vue') },
            { path: 'sessions/create', name: 'session-create', component: () => import('@/pages/SessionCreatePage.vue') },
            { path: 'sessions/:id', name: 'session', component: () => import('@/pages/SessionPage.vue') },
            { path: 'plugins', name: 'plugins', component: () => import('@/pages/PluginsPage.vue') },
            { path: 'channels', name: 'channels', component: () => import('@/pages/ChannelsPage.vue') },
            { path: 'audit', name: 'audit', component: () => import('@/pages/AuditLogPage.vue') },
            { path: 'settings', name: 'settings', component: () => import('@/pages/SettingsPage.vue') },
            { path: 'setup', name: 'setup', component: () => import('@/pages/SetupPage.vue') },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

let setupChecked = false;

router.beforeEach(async (to, _from, next) => {
    if (to.name === 'setup' || setupChecked) {
        return next();
    }

    const token = localStorage.getItem('stan_token');

    if (!token) {
        return next();
    }

    try {
        const res = await fetch('/api/setup/status');

        if (res.ok) {
            const data = await res.json();
            setupChecked = true;

            if (!data.has_provider) {
                return next({ name: 'setup' });
            }
        }
    } catch {
        // Ignore — endpoint not available
    }

    setupChecked = true;
    next();
});

export default router;
