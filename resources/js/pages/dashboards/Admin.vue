<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
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

const recentOrders = ref([
    { id: '#1041', product: 'Aloe Vera Serum', amount: 720, status: 'Paid', statusClass: 'badge-green' },
    { id: '#1040', product: 'Rosehip Face Oil', amount: 1850, status: 'Pending', statusClass: 'badge-orange' },
    { id: '#1039', product: 'Shea Butter Soap ×3', amount: 960, status: 'Paid', statusClass: 'badge-green' },
    { id: '#1038', product: 'Hydrating Toner', amount: 890, status: 'Shipped', statusClass: 'badge-green' },
]);

const formatCurrency = (amount: number): string => {
    return `KES ${amount.toLocaleString()}`;
};

// Weekly sales data for chart
const weeklySales = ref([35, 55, 40, 70, 60, 90, 75]);
const weeklyTotal = 18400;
</script>

<template>
    <Head title="Seller Dashboard" />

    <div class="app_container Dashboard SellerDashboard">
        <section class="Hero">
            <div class="hero-wrapper">
                <h2 class="hero-title">My Shop Dashboard</h2>
                <p class="hero-salutation">Welcome back, {{ user.name }} · Amani Botanics</p>
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

        <section class="OrdersStats">
            <div class="recent-orders">
                <div class="orders-wrapper">
                    <div class="header">
                        <div class="title">Recent Orders</div>
                        <div class="stat">5 pending</div>
                    </div>
                    <div class="body">
                        <table>
                            <thead>
                                <tr>
                                    <th>ORDER</th>
                                    <th>PRODUCT</th>
                                    <th>AMOUNT</th>
                                    <th>STATUS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="order in recentOrders" :key="order.id">
                                    <td>{{ order.id }}</td>
                                    <td>{{ order.product }}</td>
                                    <td>{{ formatCurrency(order.amount) }}</td>
                                    <td><span class="badge" :class="order.statusClass">{{ order.status }}</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="sales_stats">
                <div style="font-weight: 500; font-size: 14px; margin-bottom: 4px">Sales This Week</div>
                <div style="font-size: 12px; color: var(--text-3); margin-bottom: 8px">
                    {{ formatCurrency(weeklyTotal) }} total
                </div>
                <div class="mini-chart">
                    <div 
                        v-for="(sale, index) in weeklySales" 
                        :key="index"
                        class="bar" 
                        :class="{ highlight: index >= 5 }"
                        :style="{ height: sale + '%' }"
                    ></div>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 10px; color: var(--text-3); margin-top: 4px">
                    <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span>
                </div>
            </div>
        </section>
    </div>
</template>