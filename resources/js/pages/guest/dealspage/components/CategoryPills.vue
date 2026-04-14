<script setup lang="ts">
import { ref } from 'vue';

interface Category {
    id: number;
    name: string;
    slug: string;
};

const props = defineProps<{
    product_categories: Category[];
}>();

const activeCategory = ref('All');
</script>

<template>
    <section class="CategoryPills">
        <button 
            class="cat-pill"
            :class="{ active: activeCategory === 'All' }"
            @click="activeCategory = 'All'"
        >
            All
        </button>
        
        <button 
            v-for="category in product_categories" 
            :key="category.id"
            class="cat-pill"
            :class="{ active: activeCategory === category.name }"
            @click="activeCategory = category.name"
        >
            {{ category.name }}
        </button>
    </section>
</template>