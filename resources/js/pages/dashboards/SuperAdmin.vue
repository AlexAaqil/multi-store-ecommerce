<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { dashboard } from '@/routes';
import { useFormatters } from '@/composables/useFormatters';

const { formatNumber, formatDecimal } = useFormatters();

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
};

interface Stats {
    total_users: number;
    total_sellers: number;
    total_customers: number;

    total_shops: number;
    new_shops_this_week: number;
    shops_percentage_change: number;
};

interface Props {
    user: User;
    stats: Stats;
};

const props = defineProps<Props>();
</script>

<template>
    <Head title="Super Admin Dashboard" />

    <div class="app_container Dashboard SuperAdminDashboard">
        <section class="Hero">
            <div class="hero-wrapper">
                <h2 class="hero-title">Dashboard</h2>
                <p class="hero-salutation">Hi, {{ user.name }}.</p>
            </div>
        </section>

        <section class="Stats">
            <div class="stats-wrapper">
                <div class="stat-card">
                    <div class="label">USERS</div>
                    <div class="value">{{ formatNumber(stats.total_users) }}</div>
                    <div class="extras">
                        <span>{{ formatNumber(stats.total_sellers) }} sellers & {{ formatNumber(stats.total_customers) }} customers</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="label">SHOPS</div>
                    <div class="value">{{ formatNumber(stats.total_shops) }}</div>
                    <div class="extras">
                        <span :class="stats.shops_percentage_change > 0 ? 'text-green-600' : 'text-red-600'">{{ stats.shops_percentage_change > 0 ? '+' : '' }}{{ stats.new_shops_this_week }} this week</span>
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
                    <div class="label">REVENUE (MAR)</div>
                    <div class="value">Ksh. 840,200,000</div>
                    <div class="extras">
                        <span>&uparrow; 18% vs last month</span>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>