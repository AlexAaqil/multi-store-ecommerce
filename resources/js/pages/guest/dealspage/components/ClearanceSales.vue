<script setup lang="ts">
const products = [
    {
        id: 1,
        image: 'https://media.gettyimages.com/id/1299655280/photo/apps-installed-on-a-samsung-galaxy-s21-smart-phone.jpg?s=612x612&w=gi&k=20&c=lhaG0yW0xaeexcoXhPyRacQdORcdjCEqv14ONGwluCg=',
        name: 'Kitenge Socks (3-pack)',
        slug: 'kitenge-socks-3-pack',
        price: 280,
        old_price: 580,
        discount_pct: 52,
    },
    {
        id: 2,
        image: '',
        name: 'Hibiscus Body Scrub',
        slug: 'hibiscus-body-scrub',
        price: 490,
        old_price: 850,
        discount_pct: 42,
    }
]
</script>

<template>
    <section class="ClearanceSales">
        <div class="section-header">
            <div class="section-title">Clearance</div>
            <Link href="/deals" class="section-link">12 active</Link>
        </div>

        <div class="clearancesales-wrapper">
            <div 
                v-for="product in products" 
                :key="product.id"
                class="product-card"
                @click="$inertia.visit(`/product/${product.id}`)"
            >
                <div class="product-image">
                    <img :src=product.image :alt=product.slug />
                </div>
                <div class="product-info">
                    <h3 class="product-name">{{ product.name }}</h3>
                    <p class="product-price-info">
                        <span class="product-price">KES {{ product.price }}</span>
                        <span class="product-discount">
                            <span class="discount-price">KES {{ product.old_price }}</span>
                            <span class="discount-pct">{{ product.discount_pct }}%</span>
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>