<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { inject } from 'vue';
import type { Shop } from '@/types/shop';
import Button from '@/components/ui/button/Button.vue';

const featured_shops = inject<Shop[]>('featured_shops', []);
</script>

<template>
    <section class="FeaturedShops">
        <div class="section-header">
            <h2 class="section-title">Featured Shops</h2>
            <Link href="/shops" class="section-link">View all →</Link>
        </div>
        
        <!-- Only show if shops exist -->
        <div v-if="featured_shops.length > 0" class="shops-wrapper">
            <div 
                v-for="shop in featured_shops" 
                :key="shop.id"
                class="shop-card"
                @click="$inertia.visit(`/shop-details/${shop.slug}`)"
            >
                <div class="shop-card-images">
                    <div class="shop-card-cover">
                        <img :src="shop.cover_image" alt="Shop cover Image">
                    </div>

                    <div class="shop-card-logo">
                        <img :src="shop.logo_image" alt="Shop logo">
                    </div>
                </div>

                <div class="shop-card-body">
                    <h3 class="shop-card-name">{{ shop.name }}</h3>
                    <p class="shop-card-category">{{ shop.category || 'Uncategorized' }}</p>
                    <div class="shop-card-meta">
                        <div class="shop-rating">
                            <span class="star">★</span> 
                            <span class="rating">{{ shop.rating }}</span>
                            <span class="reviews">({{ shop.reviews_count }} reviews)</span>
                        </div>
                        <span class="badge">{{ shop.is_active ? 'Open' : 'Closed' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="shops-wrapper-empty-state">
            <div class="content">
                <div class="icon">🏪</div>
                <h3 class="title">No shops available yet</h3>
                <p class="description">
                    Be the first to create a shop and start selling!
                </p>
                <Link href="/shops/create">
                    <Button>Create Your Shop</Button>
                </Link>
            </div>
        </div>
    </section>
</template>