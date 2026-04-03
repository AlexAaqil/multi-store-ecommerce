<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Button from '@/components/ui/button/Button.vue';
</script>

<template>
    <section class="Hero">
        <div class="hero-wrapper">
            <div class="hero-text">
                <div class="hero-eyebrow">Kenya's Marketplace Network</div>
                <div class="hero-title">
                    Shop local.<br>
                    <em>Support bold</em><br>
                    businesses.
                </div>
                <div class="hero-description">
                    Discover curated shops, exclusive deals, and the people behind every product.
                </div>
                <div class="hero-actions">
                    <Link href="/deals">
                        <Button>Browse Offers</Button>
                    </Link>
                    <Link href="/dashboard">
                        <Button variant="outline">Get Started</Button>
                    </Link>
                </div>
            </div>

            <div class="hero-stats">
                <div class="hero-stat">
                    <div class="number">240+</div>
                    <div class="label">Active shops</div>
                </div>
                <div class="hero-stat">
                    <div class="number">12k</div>
                    <div class="label">Products listed</div>
                </div>
                <div class="hero-stat">
                    <div class="number">3.8k</div>
                    <div class="label">Daily shoppers</div>
                </div>
            </div>

            <div class="decorative-circle"></div>
        </div>
    </section>
</template>
