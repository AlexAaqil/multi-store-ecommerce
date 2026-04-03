<script setup lang="ts">
import Navbar from '@/components/custom/Navbar/Navbar.vue';
</script>

<template>
    <div>
        <Navbar />
        <main>
            <slot />
        </main>
    </div>
</template>