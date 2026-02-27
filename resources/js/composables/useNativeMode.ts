import { computed } from 'vue';

export function useNativeMode() {
    const isNative = computed(() => !!(window as any).__TAURI_INTERNALS__);

    return { isNative };
}
