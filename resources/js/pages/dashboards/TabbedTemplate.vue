<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { dashboard } from '@/routes';
import { 
    TrendingUp, 
    ShoppingBag, 
    Package, 
    Eye, 
    Plus, 
    Edit2,
    DollarSign,
    Users,
    Percent,
    BarChart3
} from 'lucide-vue-next';

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

// Active tab state
const activeTab = ref('overview');

// Sample data - replace with real data from API
const stats = ref({
    revenue: 84200,
    orders: 147,
    products: 48,
    shopViews: 3812,
    revenueChange: 18,
    ordersChange: 9,
    productsChange: -3,
    viewsChange: 22
});

const recentOrders = ref([
    { id: '#1041', product: 'Aloe Vera Serum', amount: 720, status: 'Paid', statusClass: 'badge-green' },
    { id: '#1040', product: 'Rosehip Face Oil', amount: 1850, status: 'Pending', statusClass: 'badge-orange' },
    { id: '#1039', product: 'Shea Butter Soap ×3', amount: 960, status: 'Paid', statusClass: 'badge-green' },
    { id: '#1038', product: 'Hydrating Toner', amount: 890, status: 'Shipped', statusClass: 'badge-green' },
]);

const products = ref([
    { id: 1, name: 'Aloe Vera Serum', price: 720, stock: 34, status: 'Active', statusClass: 'badge-green', icon: '🧴' },
    { id: 2, name: 'Rosehip Face Oil', price: 1850, stock: 12, status: 'Active', statusClass: 'badge-green', icon: '🌹' },
    { id: 3, name: 'Shea Butter Soap', price: 320, stock: 3, status: 'Low Stock', statusClass: 'badge-orange', icon: '🧼' },
    { id: 4, name: 'Hydrating Toner', price: 890, stock: 0, status: 'Out of Stock', statusClass: 'badge-red', icon: '💧' },
]);

const inventory = ref([
    { id: 1, name: 'Aloe Vera Serum', stock: 34, threshold: 10, percentage: 85, icon: '🧴' },
    { id: 2, name: 'Rosehip Face Oil', stock: 12, threshold: 10, percentage: 40, icon: '🌹' },
    { id: 3, name: 'Shea Butter Soap', stock: 3, threshold: 10, percentage: 10, icon: '🧼', isLow: true },
    { id: 4, name: 'Hydrating Toner', stock: 0, threshold: 10, percentage: 2, icon: '💧', isLow: true },
]);

const activeDiscounts = ref([
    { id: 1, product: 'Aloe Vera Serum', discount: '40% OFF', ends: 'Mar 20', visible: true, icon: '🧴' },
    { id: 2, product: 'Hydrating Toner', discount: '19% OFF', ends: 'Mar 18', visible: true, icon: '💧' },
]);

// Weekly sales data for chart
const weeklySales = ref([35, 55, 40, 70, 60, 90, 75]);
const weeklyTotal = 18400;

// Monthly revenue data
const monthlyRevenue = ref([48, 62, 55, 78, 70, 95]);

const topProducts = ref([
    { name: 'Rosehip Face Oil', revenue: 22200, percentage: 80 },
    { name: 'Aloe Vera Serum', revenue: 18720, percentage: 68 },
    { name: 'Hydrating Toner', revenue: 14240, percentage: 52 },
    { name: 'Shea Butter Soap', revenue: 9600, percentage: 35 },
]);

// Methods
const switchTab = (tab: string) => {
    activeTab.value = tab;
};

const formatNumber = (num: number): string => {
    if (num >= 1000) {
        return `${(num / 1000).toFixed(1)}k`;
    }
    return num.toString();
};

const formatCurrency = (amount: number): string => {
    return `KES ${amount.toLocaleString()}`;
};
</script>

<template>
    <Head title="Seller Dashboard" />

    <div class="app_container Dashboard SellerDashboard">
        <section class="Hero">
            <div class="hero-wrapper">
                <h2 class="hero-title">My Shop Dashboard</h2>
                <p class="hero-salutation">Welcome back, Kamau · Amani Botanics</p>
            </div>
        </section>

        <!-- Tabs -->
        <div class="inner-tabs" id="dash-tabs">
            <button 
                class="inner-tab" 
                :class="{ active: activeTab === 'overview' }"
                @click="switchTab('overview')"
            >Overview</button>
            <button 
                class="inner-tab" 
                :class="{ active: activeTab === 'products' }"
                @click="switchTab('products')"
            >Products</button>
            <button 
                class="inner-tab" 
                :class="{ active: activeTab === 'inventory' }"
                @click="switchTab('inventory')"
            >Inventory</button>
            <button 
                class="inner-tab" 
                :class="{ active: activeTab === 'discounts' }"
                @click="switchTab('discounts')"
            >Discounts</button>
            <button 
                class="inner-tab" 
                :class="{ active: activeTab === 'analytics' }"
                @click="switchTab('analytics')"
            >Analytics</button>
        </div>

        <!-- OVERVIEW TAB -->
        <div v-show="activeTab === 'overview'" id="dash-overview">
            <!-- Stats Cards -->
            <div class="grid-4" style="margin-bottom: 20px">
                <div class="stat-card">
                    <div class="stat-label">Revenue (Mar)</div>
                    <div class="stat-value">{{ formatCurrency(stats.revenue) }}</div>
                    <div class="stat-change up">↑ {{ stats.revenueChange }}% vs last month</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Orders</div>
                    <div class="stat-value">{{ stats.orders }}</div>
                    <div class="stat-change up">↑ {{ stats.ordersChange }} new today</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Products</div>
                    <div class="stat-value">{{ stats.products }}</div>
                    <div class="stat-change dn">↓ {{ Math.abs(stats.productsChange) }} low stock</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Shop Views</div>
                    <div class="stat-value">{{ formatNumber(stats.shopViews) }}</div>
                    <div class="stat-change up">↑ {{ stats.viewsChange }}% this week</div>
                </div>
            </div>

            <!-- Recent Orders & Sales Chart -->
            <div class="grid-2">
                <!-- Recent Orders Table -->
                <div class="table-wrap">
                    <div class="table-header">
                        <span class="table-title">Recent Orders</span>
                        <span class="badge badge-orange">5 pending</span>
                    </div>
                    <table>
                        <thead>
                            <tr><th>Order</th><th>Product</th><th>Amount</th><th>Status</th></tr>
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

                <!-- Sales Chart -->
                <div class="card">
                    <div style="font-weight: 500; font-size: 14px; margin-bottom: 4px">Sales This Week</div>
                    <div style="font-size: 12px; color: var(--text-3); margin-bottom: 8px">{{ formatCurrency(weeklyTotal) }} total</div>
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
            </div>
        </div>

        <!-- PRODUCTS TAB -->
        <div v-show="activeTab === 'products'" id="dash-products">
            <div class="two-col">
                <div class="table-wrap">
                    <div class="table-header">
                        <span class="table-title">All Products</span>
                        <button class="btn btn-dark" style="font-size: 12px; padding: 6px 14px">+ Add New</button>
                    </div>
                    <table>
                        <thead>
                            <tr><th>Product</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="product in products" :key="product.id">
                                <td style="display: flex; align-items: center; gap: 8px">
                                    <span style="font-size: 20px">{{ product.icon }}</span>
                                    {{ product.name }}
                                </td>
                                <td>{{ formatCurrency(product.price) }}</td>
                                <td>{{ product.stock }}</td>
                                <td><span class="badge" :class="product.statusClass">{{ product.status }}</span></td>
                                <td style="cursor: pointer; color: var(--text-3)">✏️</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Quick Add Product Form -->
                <div class="card">
                    <div style="font-weight: 500; font-size: 14px; margin-bottom: 16px">Quick Add Product</div>
                    <div class="form-group">
                        <label class="form-label">Product name</label>
                        <input class="form-input" placeholder="e.g. Moringa Face Mask">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price (KES)</label>
                        <input class="form-input" type="number" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <input class="form-input" placeholder="e.g. Skincare">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Stock quantity</label>
                        <input class="form-input" type="number" placeholder="0">
                    </div>
                    <button class="btn btn-dark" style="width: 100%">Save Product</button>
                </div>
            </div>
        </div>

        <!-- INVENTORY TAB -->
        <div v-show="activeTab === 'inventory'" id="dash-inventory">
            <div class="grid-2">
                <div class="card">
                    <div style="font-weight: 500; font-size: 14px; margin-bottom: 4px">Stock Levels</div>
                    <div style="font-size: 12px; color: var(--text-3); margin-bottom: 16px">3 items need attention</div>
                    <div v-for="item in inventory" :key="item.id" class="inv-item">
                        <div class="inv-icon">{{ item.icon }}</div>
                        <div style="flex: 1">
                            <div class="inv-name">{{ item.name }}</div>
                            <div class="inv-stock">{{ item.stock }} units · Restock at {{ item.threshold }}</div>
                        </div>
                        <div class="inv-bar-wrap">
                            <div class="inv-bar" :class="{ low: item.isLow }" :style="{ width: item.percentage + '%' }"></div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div style="font-weight: 500; font-size: 14px; margin-bottom: 16px">Update Stock</div>
                    <div class="form-group">
                        <label class="form-label">Select product</label>
                        <input class="form-input" placeholder="Search product…">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Add units</label>
                        <input class="form-input" type="number" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Restock alert threshold</label>
                        <input class="form-input" type="number" placeholder="10">
                    </div>
                    <button class="btn btn-dark" style="width: 100%">Update Inventory</button>
                </div>
            </div>
        </div>

        <!-- DISCOUNTS TAB -->
        <div v-show="activeTab === 'discounts'" id="dash-discounts">
            <div class="two-col">
                <div>
                    <div class="table-wrap" style="margin-bottom: 16px">
                        <div class="table-header">
                            <span class="table-title">Active Discounts</span>
                        </div>
                        <table>
                            <thead>
                                <tr><th>Product</th><th>Discount</th><th>Ends</th><th>Visible</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="discount in activeDiscounts" :key="discount.id">
                                    <td style="display: flex; align-items: center; gap: 8px">
                                        <span style="font-size: 20px">{{ discount.icon }}</span>
                                        {{ discount.product }}
                                    </td>
                                    <td><span class="badge badge-green">{{ discount.discount }}</span></td>
                                    <td>{{ discount.ends }}</td>
                                    <td>
                                        <label class="toggle-switch">
                                            <input type="checkbox" :checked="discount.visible">
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div style="font-weight: 500; font-size: 14px; margin-bottom: 16px">Create Discount</div>
                    <div class="form-group">
                        <label class="form-label">Product</label>
                        <input class="form-input" placeholder="Search product…">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Discount %</label>
                        <input class="form-input" type="number" placeholder="e.g. 20">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Valid until</label>
                        <input class="form-input" type="date">
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 16px">
                        <label class="toggle-switch">
                            <input type="checkbox">
                            <span class="toggle-slider"></span>
                        </label>
                        <span style="font-size: 13px; color: var(--text-2)">Show on Deals page</span>
                    </div>
                    <button class="btn btn-dark" style="width: 100%">Apply Discount</button>
                </div>
            </div>
        </div>

        <!-- ANALYTICS TAB -->
        <div v-show="activeTab === 'analytics'" id="dash-analytics">
            <div class="grid-4" style="margin-bottom: 20px">
                <div class="stat-card">
                    <div class="stat-label">Conversion Rate</div>
                    <div class="stat-value">3.8%</div>
                    <div class="stat-change up">↑ 0.4% this week</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Avg. Order Value</div>
                    <div class="stat-value">KES 572</div>
                    <div class="stat-change up">↑ KES 48</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Return Rate</div>
                    <div class="stat-value">1.2%</div>
                    <div class="stat-change up">↓ Improved</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">New Followers</div>
                    <div class="stat-value">+84</div>
                    <div class="stat-change up">↑ This month</div>
                </div>
            </div>

            <div class="grid-2">
                <!-- Revenue Chart -->
                <div class="card">
                    <div style="font-weight: 500; font-size: 14px; margin-bottom: 4px">Revenue — Last 6 Months</div>
                    <div class="mini-chart" style="height: 80px; margin-top: 16px; gap: 6px">
                        <div 
                            v-for="(rev, index) in monthlyRevenue" 
                            :key="index"
                            class="bar" 
                            :class="{ highlight: index === monthlyRevenue.length - 1 }"
                            :style="{ height: rev + '%' }"
                        ></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 10px; color: var(--text-3); margin-top: 6px">
                        <span>Oct</span><span>Nov</span><span>Dec</span><span>Jan</span><span>Feb</span><span>Mar</span>
                    </div>
                </div>

                <!-- Top Products -->
                <div class="card">
                    <div style="font-weight: 500; font-size: 14px; margin-bottom: 14px">Top Products by Revenue</div>
                    <div v-for="product in topProducts" :key="product.name" style="margin-bottom: 12px">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 12px">
                            <span>{{ product.name }}</span>
                            <span style="font-weight: 600">{{ formatCurrency(product.revenue) }}</span>
                        </div>
                        <div style="height: 4px; border-radius: 2px; background: var(--border-light)">
                            <div style="height: 100%; width: 80%; border-radius: 2px; background: var(--text)"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Copy all dashboard-related CSS from the HTML file */
.pages_container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 24px;
}

.grid-4 {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}

.grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.two-col {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 20px;
}

/* Additional styles from the original HTML */
.badge-red {
    background: #fde8e8;
    color: #c0392b;
}
</style>