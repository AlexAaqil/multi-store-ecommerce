<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

const hotDeals = [
    { id: 1, name: 'Aloe Vera Serum 50ml', shop: 'Amani Botanics', icon: 'https://media.gettyimages.com/id/1299655280/photo/apps-installed-on-a-samsung-galaxy-s21-smart-phone.jpg?s=612x612&w=gi&k=20&c=lhaG0yW0xaeexcoXhPyRacQdORcdjCEqv14ONGwluCg=', discount: '40% OFF', was: 'KES 1,200' },
    { id: 2, name: 'Ankara Sneakers', shop: 'Zuri Threads', icon: '👟', discount: '25% OFF', was: 'KES 4,500' },
    { id: 3, name: 'Pure Honey 500g', shop: "Mama's Pantry", icon: '🍯', discount: '15% OFF', was: 'KES 650' }
];
</script>

<template>
    <section class="HotDeals">
        <div class="section-header">
            <div class="section-title">Hot Right Now</div>
            <Link href="/deals" class="section-link">See all deals →</Link>
        </div>
        
        <div class="hotdeals-wrapper">
            <div 
                v-for="deal in hotDeals" 
                :key="deal.id"
                class="deal-card"
                @click="$inertia.visit(`/product/${deal.id}`)"
            >
                <div class="deal-icon">
                    <img :src=deal.icon alt="">
                </div>
                <div class="deal-info">
                    <h3 class="deal-name">{{ deal.name }}</h3>
                    <p class="deal-shop">{{ deal.shop }}</p>
                </div>
                <div class="deal-right">
                    <div class="deal-discount">{{ deal.discount }}</div>
                    <div class="deal-was">Was {{ deal.was }}</div>
                </div>
            </div>
        </div>
    </section>
</template>