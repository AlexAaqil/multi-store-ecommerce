<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import EmptyDeals from '@/pages/guest/components/EmptyDeals.vue';

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
                v-for="product in flash_sales" 
                :key="product.id"
                class="product-card_deal"
                @click="$inertia.visit(`/product-details/${product.slug}`)"
            >
                <div class="deal-icon">
                    <img :src="product.image_url" :alt="product.name">
                </div>
                <div class="deal-info">
                    <h3 class="deal-name">{{ product.name }}</h3>
                    <p class="deal-shop">{{ product.shop_name }}</p>
                </div>
                <div class="deal-right">
                    <div class="deal-discount">{{ product.discount_pct }}% OFF</div>
                    <div class="deal-was">Was KES {{ product.price }}</div>
                </div>
            </div>
        </div>
        
        <div v-else class="deals-empty-state">
            <EmptyDeals type="flash" />
        </div>
    </section>
</template>