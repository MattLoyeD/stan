import { ref, onUnmounted } from 'vue';

export function useMercure(topic: string) {
    const data = ref<unknown>(null);
    const connected = ref(false);
    let eventSource: EventSource | null = null;

    function connect(mercureUrl: string = '/.well-known/mercure') {
        const url = new URL(mercureUrl, window.location.origin);
        url.searchParams.append('topic', topic);

        eventSource = new EventSource(url.toString());

        eventSource.onopen = () => {
            connected.value = true;
        };

        eventSource.onmessage = (event) => {
            try {
                data.value = JSON.parse(event.data);
            } catch {
                data.value = event.data;
            }
        };

        eventSource.onerror = () => {
            connected.value = false;
        };
    }

    function disconnect() {
        if (eventSource) {
            eventSource.close();
            eventSource = null;
            connected.value = false;
        }
    }

    onUnmounted(() => disconnect());

    return { data, connected, connect, disconnect };
}
