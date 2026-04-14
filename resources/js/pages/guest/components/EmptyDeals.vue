<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    type: 'flash' | 'clearance' | 'hot';
}>();

const content = computed(() => {
    switch (props.type) {
        case 'flash':
            return {
                icon: '⚡',
                title: 'No Flash Offers Available',
                message: 'Check back soon for exciting flash deals!',
                buttonText: 'Browse Products',
                buttonLink: '/'
            };
        case 'clearance':
            return {
                icon: '🏷️',
                title: 'No Clearance Items Available',
                message: 'Check back soon for great clearance deals!',
                buttonText: 'Shop Now',
                buttonLink: '/deals'
            };
        case 'hot':
            return {
                icon: '🔥',
                title: 'No Hot Deals Available',
                message: 'Check back soon for amazing hot deals!',
                buttonText: 'View All Deals',
                buttonLink: '/deals'
            };
        default:
            return {
                icon: '🛍️',
                title: 'No Deals Available',
                message: 'Check back soon for exciting deals!',
                buttonText: 'Browse Products',
                buttonLink: '/'
            };
    }
});
</script>

<template>
    <div class="deals-empty-state">
        <div class="empty-icon">{{ content.icon }}</div>
        <h3 class="empty-title">{{ content.title }}</h3>
        <p class="empty-message">{{ content.message }}</p>
        <Link :href="content.buttonLink" class="empty-button">{{ content.buttonText }}</Link>
    </div>
</template>