<script setup lang="ts">
import { ref } from 'vue';

const activeCategory = ref('All');
const categories = [
    'All', 'Fashion', 'Electronics', 'Food & Groceries', 
    'Beauty', 'Home & Living', 'Health', 'Art & Crafts'
];
</script>

<template>
    <section class="CategoryPills">
        <button 
            v-for="cat in categories" 
            :key="cat"
            class="cat-pill"
            :class="{ active: activeCategory === cat }"
            @click="activeCategory = cat"
        >
            {{ cat }}
        </button>
    </section>
</template>