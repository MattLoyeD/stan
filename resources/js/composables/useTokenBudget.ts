import { computed } from 'vue';
import type { Objective, CodingSession } from '@/types';

export function useTokenBudget(entity: Objective | CodingSession) {
    const percentage = computed(() => {
        if (entity.token_budget === 0) return 0;
        return Math.round((entity.tokens_used / entity.token_budget) * 100);
    });

    const remaining = computed(() => Math.max(0, entity.token_budget - entity.tokens_used));

    const severity = computed(() => {
        if (percentage.value >= 90) return 'danger';
        if (percentage.value >= 70) return 'warning';
        return 'success';
    });

    const isExhausted = computed(() => entity.tokens_used >= entity.token_budget);

    return { percentage, remaining, severity, isExhausted };
}
