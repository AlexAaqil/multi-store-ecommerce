<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import ProductPrice from '@/components/custom/Products/Price.vue';
import EmptyDeals from '@/pages/guest/components/EmptyDeals.vue';

const props = defineProps<{
    clearance_sales?: any[];
}>();
</script>

<template>
    <section class="ClearanceSales">
        <div class="section-header">
            <div class="section-title">Clearance</div>
            <Link href="/deals" class="section-link">{{ clearance_sales?.length || 0 }} active</Link>
        </div>

        <div v-if="clearance_sales && clearance_sales.length > 0" class="clearancesales-wrapper">
            <div 
                v-for="product in clearance_sales" 
                :key="product.id"
                class="product-card"
            >
                <Link :href="`/product-details/${product.slug}`">
                    <div class="image">
                        <img :src="product.image_url" :alt="product.name" />
                    </div>
                </Link>
                <div class="info">
                    <h3 class="name">{{ product.name }}</h3>
                    <p class="category">{{ product.category || 'Uncategorized' }}</p>
                    <ProductPrice
                        :original-price="product.old_price"
                        :discounted-price="product.price"
                        :percentage-off="product.discount_pct"
                        size="sm"
                    />
                </div>
                <button @click.stop="">Add To Cart</button>
            </div>
        </div>

        <div v-else class="deals-empty-state">
            <EmptyDeals type="clearance" />
        </div>
    </section>
</template>