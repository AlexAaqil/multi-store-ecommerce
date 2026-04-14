<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    flash_sales?: any[];
}>();
</script>

<template>
    <section class="FlashSales">
        <div class="section-header">
            <div class="section-title">Flash Offers</div>
            <Link href="/deals" class="section-link">{{ flash_sales?.length || 0 }} active</Link>
        </div>
        
        <div v-if="flash_sales && flash_sales.length > 0" class="hotdeals-wrapper">
            <div 
                v-for="deal in flash_sales" 
                :key="deal.id"
                class="product-card_deal"
                @click="$inertia.visit(`/product/${deal.id}`)"
            >
                <div class="deal-icon">
                    <img :src="deal.image_url" :alt="deal.name">
                </div>
                <div class="deal-info">
                    <h3 class="deal-name">{{ deal.name }}</h3>
                    <p class="deal-shop">{{ deal.shop_name }}</p>
                </div>
                <div class="deal-right">
                    <div class="deal-discount">{{ deal.discount_text }}</div>
                    <div class="deal-was">Was KES {{ deal.old_price }}</div>
                </div>
            </div>
        </div>
        
        <div v-else class="empty-state">
            <div class="empty-icon">⚡</div>
            <h3 class="empty-title">No Flash Offers Available</h3>
            <p class="empty-message">Check back soon for exciting flash deals!</p>
            <Link href="/" class="empty-button">Browse Products</Link>
        </div>
    </section>
</template>