import type { InertiaLinkProps } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { computed, readonly } from 'vue';

import { toUrl } from '@/lib/utils';

export function useActiveUrl() {
    const page = usePage();
    const currentUrlReactive = computed(
        () => new URL(page.url, window?.location.origin).pathname,
    );

    function urlIsActive(
        urlToCheck: NonNullable<InertiaLinkProps['href']>,
        currentUrl?: string,
    ) {
        const target = toUrl(urlToCheck);
        const urlToCompare = currentUrl ?? currentUrlReactive.value;
        if (target === '/') {
            return urlToCompare === '/';
        }

        return urlToCompare === target || urlToCompare.startsWith(`${target}/`);
    }

    return {
        currentUrl: readonly(currentUrlReactive),
        urlIsActive,
    };
}

