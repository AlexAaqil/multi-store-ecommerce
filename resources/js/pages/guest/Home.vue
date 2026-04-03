<!-- resources/js/pages/Home.vue -->
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import GuestLayout from '@/layouts/GuestLayout.vue';

const searchQuery = ref('');


const categories = [
    'All', 'Fashion', 'Electronics', 'Food & Groceries', 
    'Beauty', 'Home & Living', 'Health', 'Art & Crafts'
];

const featuredShops = [
    { id: 1, name: 'Amani Botanics', category: 'Beauty & Wellness', rating: 4.9, reviews: 312, icon: '🌿', coverColor: '#f0ede8', logoBg: '#eaf4ef', status: 'Open', statusClass: 'badge-green' },
    { id: 2, name: 'Zuri Threads', category: 'Fashion & Style', rating: 4.7, reviews: 187, icon: '👗', coverColor: '#fdf0e8', logoBg: '#fdf0e8', status: 'Open', statusClass: 'badge-green' },
    { id: 3, name: 'TechNairobi', category: 'Electronics', rating: 4.8, reviews: 524, icon: '📱', coverColor: '#e8f0f8', logoBg: '#e8f0f8', status: 'Busy', statusClass: 'badge-gray' },
    { id: 4, name: "Mama's Pantry", category: 'Food & Groceries', rating: 4.9, reviews: 891, icon: '🍯', coverColor: '#f5f0e8', logoBg: '#f5f0e8', status: 'Open', statusClass: 'badge-green' }
];

const hotDeals = [
    { id: 1, name: 'Aloe Vera Serum 50ml', shop: 'Amani Botanics', icon: '🧴', discount: '40% OFF', was: 'KES 1,200' },
    { id: 2, name: 'Ankara Sneakers', shop: 'Zuri Threads', icon: '👟', discount: '25% OFF', was: 'KES 4,500' },
    { id: 3, name: 'Pure Honey 500g', shop: "Mama's Pantry", icon: '🍯', discount: '15% OFF', was: 'KES 650' }
];
</script>

<template>
    <GuestLayout>
        <Head title="Home - Sokohub" />
    
        <div class="screen active">
            <!-- Hero Section -->

            <!-- Search Bar -->
            <div class="search-bar">
                <input 
                    type="text" 
                    class="search-input" 
                    placeholder="Search shops, products, categories…"
                    v-model="searchQuery"
                >
                <button class="search-btn">Search</button>
            </div>

            <!-- Category Pills -->
            

            <!-- Featured Shops Section -->
            
            
            <div class="grid-4" style="margin-bottom: 28px">
                <div 
                    v-for="shop in featuredShops" 
                    :key="shop.id"
                    class="shop-card"
                    @click="$inertia.visit(`/shop/${shop.id}`)"
                >
                    <div class="shop-card-cover" :style="{ background: shop.coverColor }">
                        {{ shop.icon }}
                    </div>
                    <div class="shop-card-body">
                        <div class="shop-card-logo" :style="{ background: shop.logoBg }">
                            {{ shop.icon }}
                        </div>
                        <div class="shop-card-name">{{ shop.name }}</div>
                        <div class="shop-card-cat">{{ shop.category }}</div>
                        <div class="shop-card-meta">
                            <div class="shop-rating">
                                <span>★</span> {{ shop.rating }} · {{ shop.reviews }} reviews
                            </div>
                            <span class="badge" :class="shop.statusClass">{{ shop.status }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hot Deals Section -->
            <div class="section-header">
                <div class="section-title">Hot Right Now</div>
                <Link href="/deals" class="section-link">See all deals →</Link>
            </div>
            
            <div class="grid-3">
                <div 
                    v-for="deal in hotDeals" 
                    :key="deal.id"
                    class="deal-card"
                    @click="$inertia.visit(`/product/${deal.id}`)"
                >
                    <div class="deal-icon">{{ deal.icon }}</div>
                    <div class="deal-info">
                        <div class="deal-name">{{ deal.name }}</div>
                        <div class="deal-shop">{{ deal.shop }}</div>
                    </div>
                    <div class="deal-right">
                        <div class="deal-discount">{{ deal.discount }}</div>
                        <div class="deal-was">Was {{ deal.was }}</div>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

<style scoped src="./Home.css">
/* Copy all CSS from the HTML file's <style> section here */
/* Make sure to include everything from .screen, .hero, .grid-4, etc. */
</style>