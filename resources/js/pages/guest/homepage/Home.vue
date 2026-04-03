<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import GuestLayout from '@/layouts/GuestLayout.vue';
import Hero from './components/Hero.vue';
import CategoryPills from './components/CategoryPills.vue';
import FeaturedShops from './components/FeaturedShops.vue';
import HotDeals from './components/HotDeals.vue';
</script>

<template>
    <Head title="Home"></Head>
    <GuestLayout>
        <div class="main_container Homepage">
            <Hero />
            <CategoryPills />
            <FeaturedShops />
            <HotDeals />
        </div>
    </GuestLayout>
</template>