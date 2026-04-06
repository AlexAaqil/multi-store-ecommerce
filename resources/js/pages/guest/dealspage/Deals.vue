<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import GuestLayout from '@/layouts/GuestLayout.vue';
import Hero from './components/Hero.vue';
import CategoryPills from '../homepage/components/CategoryPills.vue';
import FlashSales from './components/FlashSales.vue';
import ClearanceSales from './components/ClearanceSales.vue';
</script>

<template>
    <Head title="Hot Deals" />
    <GuestLayout>
        <div class="main_container DealsPage">
            <Hero />
            <CategoryPills />
            <FlashSales />
            <ClearanceSales />
            <section>The end</section>
        </div>
    </GuestLayout>
</template>