<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface Shop {
    id: number;
    name: string;
    category: string;
    rating: number;
    reviews: number;
    icon_image: string;
    cover_image?: string;
    status: string;
}

const featuredShops = [
    { 
        id: 1, 
        name: 'Amani Botanics', 
        category: 'Beauty & Wellness', 
        rating: 4.9, 
        reviews: 312, 
        icon: 'https://1000logos.net/wp-content/uploads/2016/10/Apple-Logo.png',
        coverImage: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ0a72M_H_B2q5TiIQSmOpjC0x2JuweIV4d4Q&s',
        coverColor: '#f0ede8', 
        logoBg: '#eaf4ef', 
        status: 'Open', 
        statusClass: 'badge-green' 
    },
    { id: 2, name: 'Zuri Threads', category: 'Fashion & Style', rating: 4.7, reviews: 187, icon: '👗', coverColor: '#fdf0e8', logoBg: '#fdf0e8', status: 'Open', statusClass: 'badge-green' },
    { id: 3, name: 'TechNairobi', category: 'Electronics', rating: 4.8, reviews: 524, icon: '📱', coverColor: '#e8f0f8', logoBg: '#e8f0f8', status: 'Busy', statusClass: 'badge-gray' },
    { id: 4, name: "Mama's Pantry", category: 'Food & Groceries', rating: 4.9, reviews: 891, icon: '🍯', coverColor: '#f5f0e8', logoBg: '#f5f0e8', status: 'Open', statusClass: 'badge-green' }
];
</script>

<template>
    <section class="FeaturedShops">
        <div class="section-header">
            <h2 class="section-title">Featured Shops</h2>
            <Link href="/shops" class="section-link">View all →</Link>
        </div>
        
        <div class="shops_wrapper" style="margin-bottom: 28px">
            <div 
                v-for="shop in featuredShops" 
                :key="shop.id"
                class="shop-card"
                @click="$inertia.visit(`/shop/${shop.id}`)"
            >
                <div class="shop-card-images">
                    <div class="shop-card-cover" :style="{ background: shop.coverColor }">
                        <img :src=shop.coverImage alt="Shop cover Image">
                    </div>

                    <div class="shop-card-logo" :style="{ background: shop.logoBg }">
                        <img :src=shop.icon alt="Shop cover Image">
                    </div>
                </div>

                <div class="shop-card-body">
                    <h3 class="shop-card-name">{{ shop.name }}</h3>
                    <p class="shop-card-category">{{ shop.category }}</p>
                    <div class="shop-card-meta">
                        <div class="shop-rating">
                            <span>★</span> {{ shop.rating }} · {{ shop.reviews }} reviews
                        </div>
                        <span class="badge" :class="shop.statusClass">{{ shop.status }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>