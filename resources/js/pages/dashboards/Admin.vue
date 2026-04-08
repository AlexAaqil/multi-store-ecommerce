<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

interface User {
    id: number;
    name: string;
    email: string;
    role: number;
}

interface Props {
    user: User;
}

const props = defineProps<Props>();
</script>

<template>
    <Head title="Admin Dashboard" />

    <div class="app_container Dashboard AdminDashboard">
        <section class="Hero">
            <div class="hero-wrapper">
                <h2 class="hero-title">Dashboard</h2>
                <p class="hero-salutation">Hi, {{ user.name }}.</p>
            </div>
        </section>

        <section class="Stats">
            <div class="stats-wrapper">
                <div class="stat-card">
                    <div class="label">REVENUE (MAR)</div>
                    <div class="value">KES 84,200</div>
                    <div class="extras">
                        <span>&uparrow; 18% vs last month</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="label">ORDERS</div>
                    <div class="value">147</div>
                    <div class="extras">
                        <span>&uparrow; 9 new today</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="label">PRODUCTS</div>
                    <div class="value">48</div>
                    <div class="extras">
                        <span>&downarrow; 3 low stock</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="label">SHOP VIEWS</div>
                    <div class="value">3,812</div>
                    <div class="extras">
                        <span>&uparrow; 22% this week</span>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>