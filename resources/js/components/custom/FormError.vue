<script setup lang="ts">
defineProps<{
    error?: string;
}>();
</script>

<template>
    <div v-if="error" class="text-sm text-red-600">
        {{ error }}
    </div>
</template>