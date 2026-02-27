<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '@/composables/useAuth';
import { useNativeMode } from '@/composables/useNativeMode';
import { useToast } from 'primevue/usetoast';
import Stepper from 'primevue/stepper';
import StepList from 'primevue/steplist';
import StepItem from 'primevue/stepitem';
import Step from 'primevue/step';
import StepPanels from 'primevue/steppanels';
import StepPanel from 'primevue/steppanel';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Button from 'primevue/button';

const router = useRouter();
const toast = useToast();
const { authHeaders, setToken } = useAuth();
const { isNative } = useNativeMode();

const tokenInput = ref('');
const providerType = ref('anthropic');
const apiKey = ref('');

const providerOptions = [
    { label: 'Anthropic', value: 'anthropic' },
    { label: 'OpenAI', value: 'openai' },
    { label: 'Ollama', value: 'ollama' },
];

function applyToken() {
    setToken(tokenInput.value);
    toast.add({ severity: 'success', summary: 'Token saved', life: 2000 });
}

async function saveProvider() {
    await fetch('/api/providers', {
        method: 'POST',
        headers: authHeaders(),
        body: JSON.stringify({
            provider: providerType.value,
            api_key: apiKey.value,
            is_default: true,
        }),
    });
    toast.add({ severity: 'success', summary: 'Provider configured', life: 2000 });
}

function finish() {
    router.push('/');
}
</script>

<template>
    <div class="setup-page">
        <h1>Welcome to Stan</h1>
        <p class="setup-intro">Let's get you set up in a few steps.</p>

        <Stepper :value="isNative ? '2' : '1'" linear>
            <StepList>
                <StepItem v-if="!isNative" value="1"><Step>Authentication</Step></StepItem>
                <StepItem value="2"><Step>Provider</Step></StepItem>
                <StepItem value="3"><Step>Ready</Step></StepItem>
            </StepList>
            <StepPanels>
                <StepPanel v-if="!isNative" v-slot="{ activateCallback }" value="1">
                    <Card>
                        <template #content>
                            <p>Enter the auth token displayed when you ran <code>php artisan stan:start</code>:</p>
                            <div class="setup-field">
                                <InputText v-model="tokenInput" type="password" placeholder="Auth token" class="w-full" />
                            </div>
                            <div class="setup-actions">
                                <Button label="Next" icon="pi pi-arrow-right" @click="applyToken(); activateCallback('2')" />
                            </div>
                        </template>
                    </Card>
                </StepPanel>
                <StepPanel v-slot="{ activateCallback }" value="2">
                    <Card>
                        <template #content>
                            <p>Configure your LLM provider:</p>
                            <div class="setup-field">
                                <Select v-model="providerType" :options="providerOptions" optionLabel="label" optionValue="value" />
                            </div>
                            <div class="setup-field">
                                <InputText v-model="apiKey" type="password" placeholder="API Key" class="w-full" />
                            </div>
                            <div class="setup-actions">
                                <Button label="Skip" severity="secondary" @click="activateCallback('3')" />
                                <Button label="Save & Next" icon="pi pi-arrow-right" @click="saveProvider(); activateCallback('3')" />
                            </div>
                        </template>
                    </Card>
                </StepPanel>
                <StepPanel value="3">
                    <Card>
                        <template #content>
                            <div class="setup-complete">
                                <i class="pi pi-check-circle" style="font-size: 3rem; color: var(--stan-success)" />
                                <h2>You're all set!</h2>
                                <p>Stan is ready to use. Create your first objective or start a coding session.</p>
                                <Button label="Go to Dashboard" icon="pi pi-home" @click="finish" />
                            </div>
                        </template>
                    </Card>
                </StepPanel>
            </StepPanels>
        </Stepper>
    </div>
</template>

<style scoped>
.setup-page { max-width: 600px; margin: 2rem auto; }
.setup-page h1 { text-align: center; }
.setup-intro { text-align: center; color: var(--stan-text-muted); margin-bottom: 2rem; }
.setup-field { margin: 1rem 0; }
.setup-actions { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem; }
.setup-complete { text-align: center; padding: 2rem 0; }
.setup-complete h2 { margin: 1rem 0 0.5rem; }
.setup-complete p { color: var(--stan-text-muted); margin-bottom: 1.5rem; }
.w-full { width: 100%; }
</style>
