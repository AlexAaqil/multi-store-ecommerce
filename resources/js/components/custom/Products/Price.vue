<script setup lang="ts">
interface Props {
    originalPrice: number
    discountedPrice: number | null
    percentageOff: number | null
    size?: 'sm' | 'md' | 'lg'
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md'
})

const formatPrice = (price: number) => `${price.toLocaleString()}`
</script>

<template>
    <div class="flex items-center gap-2 flex-wrap">
        <template v-if="discountedPrice !== null && percentageOff !== null">
            <span :class="{
                'text-sm font-semibold': size === 'sm',
                'text-base font-semibold': size === 'md',
                'text-xl font-bold': size === 'lg',
            }" class="text-gray-900">
                KES {{ formatPrice(discountedPrice) }}
            </span>
            <span :class="{
                'text-xs': size === 'sm',
                'text-sm': size === 'md',
                'text-base': size === 'lg',
            }" class="text-gray-400 line-through">
                {{ formatPrice(originalPrice) }}
            </span>
            <span :class="{
                'text-xs px-1.5 py-0.5': size === 'sm',
                'text-xs px-2 py-0.5': size === 'md',
                'text-sm px-2.5 py-1': size === 'lg',
            }" class="bg-green-100 text-green-600 font-medium rounded-full">
                {{ percentageOff }}% Off
            </span>
        </template>
        <template v-else>
            <span :class="{
                'text-sm font-semibold': size === 'sm',
                'text-base font-semibold': size === 'md',
                'text-xl font-bold': size === 'lg',
            }" class="text-gray-900">
                KES {{ formatPrice(originalPrice) }}
            </span>
        </template>
    </div>
</template>