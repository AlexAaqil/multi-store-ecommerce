<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';

const props = defineProps<{
    title: string
    search: string
    createHref?: string | { url: string }
    createButtonText?: string
    searchPlaceholder?: string
    showSearchBadge?: boolean
}>();

const emit = defineEmits<{
    (e: 'update:search', value: string): void
}>();

const onSearchUpdate = (value: string | number | null) => {
    emit('update:search', value?.toString() ?? '');
};

const createUrl = computed(() => {
    if (!props.createHref) return null;

    if (typeof props.createHref === 'string') {
        return props.createHref;
    }

    return props.createHref.url;
});
</script>

<template>
    <div class="header mb-6">
        <div class="grid gap-2 lg:flex lg:justify-between lg:items-center lg:mb-4">
            <h1 class="text-xl font-bold">{{ title }}</h1>

            <div class="search-filter-bar">
                <div class="flex flex-col md:flex-row md:items-center gap-4">
                    <div class="flex-1">
                        <Input
                            :model-value="search"
                            @update:model-value="onSearchUpdate"
                            type="text"
                            :placeholder="searchPlaceholder"
                            class="w-full dark:border-gray-600"
                        />
                    </div>

                    <div class="flex flex-wrap gap-2 items-center">
                        <span v-if="showSearchBadge && search" 
                            class="px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded-full flex items-center gap-1">
                            Search: "{{ search }}"
                            <button @click="$emit('update:search', '')" class="text-blue-600 hover:text-blue-800">×</button>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Only show create button if createHref is provided -->
            <Link v-if="createUrl" :href="createUrl">
                <Button>{{ createButtonText || 'Create' }}</Button>
            </Link>
        </div>
    </div>
</template>