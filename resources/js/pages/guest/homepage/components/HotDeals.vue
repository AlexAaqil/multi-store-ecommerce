<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    hot_deals?: any[];
}>();
</script>

<template>
    <section class="HotDeals">
        <div class="section-header">
            <div class="section-title">Hot Right Now</div>
            <Link href="/deals" class="section-link">See all deals →</Link>
        </div>
        
        <div v-if="hot_deals && hot_deals.length > 0" class="hotdeals-wrapper">
            <div 
                v-for="product in hot_deals" 
                :key="product.id"
                class="product-card_deal"
                @click="$inertia.visit(`/product-details/${product.slug}`)"
            >
                <div class="deal-icon">
                    <img :src=product.image_url :alt=product.name />
                </div>
                <div class="deal-info">
                    <h3 class="deal-name">{{ product.name }}</h3>
                    <p class="deal-shop">{{ product.shop_name }}</p>
                </div>
                <div class="deal-right">
                    <div class="deal-discount">{{ product.percentage_off }}% OFF</div>
                    <div class="deal-was">Was {{ product.old_price }}</div>
                </div>
            </div>
        </div>

        <div v-else>
            <p>No available Hot Deals right now</p>
        </div>
    </section>
</template>