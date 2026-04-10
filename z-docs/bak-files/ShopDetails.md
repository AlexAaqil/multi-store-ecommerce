<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import GuestLayout from '@/layouts/GuestLayout.vue';
import { Button } from '@/components/ui/button';
import { Star, MapPin, Mail, Phone, Clock, Shield, Package, ShoppingBag, Users } from 'lucide-vue-next';

interface Product {
    id: number;
    name: string;
    slug: string;
    price: number;
    image_url: string;
    category: string | null;
    stock_qty: number;
    created_at: string;
}

interface ShopStats {
    total_products: number;
    total_sales: number;
    total_reviews: number;
    average_rating: number;
    response_rate: number;
    response_time: string;
}

interface Shop {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    logo_url: string;
    cover_url: string;
    contact_email: string | null;
    contact_phone: string | null;
    is_active: boolean;
    is_verified: boolean;
    category: string | null;
    owner: {
        name: string;
        joined: string;
    };
    stats: ShopStats;
    created_at: string;
}

const props = defineProps<{
    shop: Shop;
    products: {
        data: Product[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: any[];
    };
}>();

const activeTab = ref('products');

const tabs = [
    { id: 'products', label: 'All Products' },
    { id: 'about', label: 'About' },
    { id: 'reviews', label: 'Reviews' },
];

const formatPrice = (price: number) => {
    return new Intl.NumberFormat('en-KE', {
        style: 'currency',
        currency: 'KES',
        minimumFractionDigits: 0,
    }).format(price);
};
</script>

<template>
    <Head :title="shop.name" />

    <GuestLayout>
        <div class="shop-details-page">
            <!-- Cover Image -->
            <div class="relative h-64 md:h-80 w-full overflow-hidden">
                <img 
                    :src="shop.cover_url" 
                    :alt="shop.name"
                    class="w-full h-full object-cover"
                />
                <div class="absolute inset-0 bg-black/40"></div>
                
                <!-- Back Button -->
                <button 
                    @click=""
                    class="absolute top-4 left-4 bg-white/90 hover:bg-white rounded-lg px-3 py-1.5 text-sm font-medium shadow-sm"
                >
                    ← Back
                </button>
            </div>

            <!-- Shop Info -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-16 relative z-10">
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="p-6">
                        <div class="flex flex-col md:flex-row gap-6">
                            <!-- Logo -->
                            <div class="flex-shrink-0">
                                <div class="w-32 h-32 rounded-xl border-4 border-white shadow-lg overflow-hidden bg-gray-100">
                                    <img 
                                        :src="shop.logo_url" 
                                        :alt="shop.name"
                                        class="w-full h-full object-cover"
                                    />
                                </div>
                            </div>

                            <!-- Shop Details -->
                            <div class="flex-1">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <h1 class="text-2xl md:text-3xl font-serif font-bold">{{ shop.name }}</h1>
                                        <div class="flex flex-wrap items-center gap-3 mt-2">
                                            <div class="flex items-center gap-1">
                                                <Star class="w-4 h-4 fill-yellow-400 text-yellow-400" />
                                                <span class="font-medium">{{ shop.stats.average_rating.toFixed(1) }}</span>
                                                <span class="text-gray-500">({{ shop.stats.total_reviews }} reviews)</span>
                                            </div>
                                            <span class="text-gray-300">|</span>
                                            <div class="flex items-center gap-1 text-gray-500">
                                                <Users class="w-4 h-4" />
                                                <span>{{ shop.stats.total_sales }} sales</span>
                                            </div>
                                            <span class="text-gray-300">|</span>
                                            <div class="flex items-center gap-1 text-gray-500">
                                                <Package class="w-4 h-4" />
                                                <span>{{ shop.stats.total_products }} products</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Verification Badge -->
                                    <div v-if="shop.is_verified" class="flex items-center gap-1 px-3 py-1 bg-blue-50 text-blue-700 rounded-full">
                                        <Shield class="w-4 h-4" />
                                        <span class="text-sm font-medium">Verified Shop</span>
                                    </div>
                                </div>

                                <!-- Contact Info -->
                                <div class="flex flex-wrap gap-4 mt-4">
                                    <div v-if="shop.contact_email" class="flex items-center gap-2 text-gray-600">
                                        <Mail class="w-4 h-4" />
                                        <span class="text-sm">{{ shop.contact_email }}</span>
                                    </div>
                                    <div v-if="shop.contact_phone" class="flex items-center gap-2 text-gray-600">
                                        <Phone class="w-4 h-4" />
                                        <span class="text-sm">{{ shop.contact_phone }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-gray-600">
                                        <Clock class="w-4 h-4" />
                                        <span class="text-sm">Response: {{ shop.stats.response_time }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs and Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Tabs -->
                <div class="border-b border-gray-200 mb-6">
                    <div class="flex gap-6">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="[
                                'pb-3 text-sm font-medium transition-colors',
                                activeTab === tab.id
                                    ? 'border-b-2 border-gray-900 text-gray-900'
                                    : 'text-gray-500 hover:text-gray-700'
                            ]"
                        >
                            {{ tab.label }}
                        </button>
                    </div>
                </div>

                <!-- Products Tab -->
                <div v-if="activeTab === 'products'">
                    <div v-if="products.data.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
                        <div 
                            v-for="product in products.data" 
                            :key="product.id"
                            class="product-card cursor-pointer group"
                            @click="$inertia.visit(`/product/${product.slug}`)"
                        >
                            <div class="aspect-square rounded-lg overflow-hidden bg-gray-100">
                                <img 
                                    :src="product.image_url" 
                                    :alt="product.name"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                />
                            </div>
                            <div class="mt-3">
                                <h3 class="font-medium text-sm line-clamp-2">{{ product.name }}</h3>
                                <p class="text-gray-500 text-xs mt-1">{{ product.category || 'Uncategorized' }}</p>
                                <p class="font-semibold text-lg mt-2">{{ formatPrice(product.price) }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Empty Products -->
                    <div v-else class="text-center py-12">
                        <ShoppingBag class="w-16 h-16 text-gray-300 mx-auto mb-4" />
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No products yet</h3>
                        <p class="text-gray-500">Check back soon for new products from this shop.</p>
                    </div>

                    <!-- Pagination -->
                    <div v-if="products.links && products.links.length > 3" class="mt-8 flex justify-center gap-1">
                        <Link v-for="link in products.links" :key="link.label" :href="link.url || '#'" 
                              v-html="link.label" 
                              class="px-3 py-1 border rounded text-sm"
                              :class="{
                                  'bg-gray-100 text-gray-500 cursor-not-allowed': !link.url,
                                  'bg-gray-900 text-white border-gray-900': link.active,
                                  'hover:bg-gray-50': link.url && !link.active
                              }" />
                    </div>
                </div>

                <!-- About Tab -->
                <div v-if="activeTab === 'about'" class="prose max-w-none">
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h2 class="text-xl font-semibold mb-4">About {{ shop.name }}</h2>
                        <p class="text-gray-600 leading-relaxed">
                            {{ shop.description || 'No description provided.' }}
                        </p>
                        
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <h3 class="font-medium mb-3">Shop Information</h3>
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-sm text-gray-500">Shop Owner</dt>
                                    <dd class="font-medium">{{ shop.owner.name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500">Member Since</dt>
                                    <dd class="font-medium">{{ shop.owner.joined }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500">Category</dt>
                                    <dd class="font-medium">{{ shop.category || 'General' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500">Total Products</dt>
                                    <dd class="font-medium">{{ shop.stats.total_products }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Reviews Tab -->
                <div v-if="activeTab === 'reviews'">
                    <div class="bg-gray-50 rounded-lg p-6 text-center">
                        <Star class="w-12 h-12 text-gray-300 mx-auto mb-3" />
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Reviews Coming Soon</h3>
                        <p class="text-gray-500">Customer reviews will appear here once available.</p>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>