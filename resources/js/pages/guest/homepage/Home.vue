<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, provide } from 'vue';
import GuestLayout from '@/layouts/GuestLayout.vue';
import Hero from './components/Hero.vue';
import CategoryPills from './components/CategoryPills.vue';

import FeaturedShops from './components/FeaturedShops.vue';
import type { Shop } from '@/types/shop';

import HotDeals from './components/HotDeals.vue';

const props = defineProps<{
    featured_shops: any[];
    total_shops: number;
    total_products: number;
    total_shoppers: number;
}>();

provide('featured_shops', props.featured_shops);
</script>

<template>
    <Head title="Home" />
    <GuestLayout>
        <div class="main_container Homepage">
            <Hero 
                :total_shops="total_shops"
                :total_products="total_products"
                :total_shoppers="total_shoppers"
            />
            <CategoryPills />
            <FeaturedShops />
            <HotDeals />
        </div>
    </GuestLayout>
</template>