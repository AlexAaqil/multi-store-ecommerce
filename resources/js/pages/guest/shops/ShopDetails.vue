<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import GuestLayout from '@/layouts/GuestLayout.vue';
import { Star } from 'lucide-vue-next';
import ProductPrice from '@/components/custom/Products/Price.vue';

interface Product {
    id: number;
    name: string;
    slug: string;
    price: number;
    image_url: string;
    category: string | null;
    stock_qty: number;
    created_at: string;
    discounted_price: number | null;   // fix: was non-nullable
    percentage_off: number | null;     // fix: was non-nullable
}

interface ShopStats {
    total_products: number;
    total_sales: number;
    total_reviews: number;
    average_rating: number;
    response_rate: number;
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
    owner: { name: string; joined: string };
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
    { id: 'on_offer', label: 'On Offer' },
    { id: 'about', label: 'About' },
    { id: 'reviews', label: 'Reviews' },
];

// Only products that actually have a discount — drives the On Offer tab
// and the tab label count badge
const discountedProducts = computed(() =>
    props.products.data.filter(p => p.discounted_price !== null)
);
</script>

<template>
    <Head :title="shop.name" />

    <GuestLayout>
        <div class="main_container ShopDetailsPage">
            <section class="back-button">
                <Link href="/">
                    <Button variant="outline">&larr; Back to Discover</Button>
                </Link>
            </section>

            <section class="Hero">
                <div class="hero-wrapper">
                    <div class="icon-text">
                        <div class="icon">
                            <img :src="shop.logo_url" :alt="shop.name" />
                        </div>
                        <div class="text">
                            <div class="text-wrapper">
                                <h2 class="name">{{ shop.name }}</h2>
                                <div class="badges">
                                    <span>{{ shop.is_active ? 'Open' : 'Closed' }}</span>
                                    <span>{{ shop.category }}</span>
                                </div>
                                <p class="description">{{ shop.description }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="stats">
                        <div class="stat">
                            <div class="number">{{ shop.stats.total_products }}</div>
                            <div class="text">Products</div>
                        </div>
                        <div class="stat">
                            <div class="number">{{ shop.stats.average_rating }}★</div>
                            <div class="text">Rating</div>
                        </div>
                        <div class="stat">
                            <div class="number">{{ shop.stats.total_reviews }}</div>
                            <div class="text">Reviews</div>
                        </div>
                        <div class="stat">
                            <div class="number">{{ shop.stats.total_sales }}</div>
                            <div class="text">Sales</div>
                        </div>
                        <div class="stat">
                            <div class="number">{{ shop.stats.response_rate }}%</div>
                            <div class="text">Response Rate</div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="TabsContent">
                <div class="tabs">
                    <div class="tabs-wrapper">
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
                            <!-- Show count badge on On Offer tab if there are deals -->
                            <span
                                v-if="tab.id === 'on_offer' && discountedProducts.length > 0"
                                class="ml-1.5 px-1.5 py-0.5 text-xs bg-red-100 text-red-600 rounded-full"
                            >
                                {{ discountedProducts.length }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- All Products tab -->
                <div v-if="activeTab === 'products'" class="products-tab">
                    <div v-if="products.data.length > 0" class="products-wrapper">
                        <div
                            v-for="product in products.data"
                            :key="product.id"
                            class="shop-details-product-card"
                        >
                            <Link :href="`/product-details/${product.slug}`">
                                <div class="image">
                                    <img :src="product.image_url" :alt="product.name" />
                                </div>
                            </Link>
                            <div class="info">
                                <h3 class="name">{{ product.name }}</h3>
                                <p class="category">{{ product.category || 'Uncategorized' }}</p>
                                <ProductPrice
                                    :original-price="product.price"
                                    :discounted-price="product.discounted_price"
                                    :percentage-off="product.percentage_off"
                                    size="sm"
                                />
                            </div>
                            <button @click.stop="">Add To Cart</button>
                        </div>
                    </div>
                    <div v-else class="py-12 text-center text-gray-400 text-sm">
                        No products available yet.
                    </div>
                </div>

                <!-- On Offer tab — only discounted products -->
                <div v-if="activeTab === 'on_offer'" class="products-tab">
                    <div v-if="discountedProducts.length > 0" class="products-wrapper">
                        <div
                            v-for="product in discountedProducts"
                            :key="product.id"
                            class="shop-details-product-card"
                        >
                            <Link :href="`/product-details/${product.slug}`">
                                <div class="image">
                                    <img :src="product.image_url" :alt="product.name" />
                                </div>
                            </Link>
                            <div class="info">
                                <h3 class="name">{{ product.name }}</h3>
                                <p class="category">{{ product.category || 'Uncategorized' }}</p>
                                <!-- Always has a discount here so the discounted layout always shows -->
                                <ProductPrice
                                    :original-price="product.price"
                                    :discounted-price="product.discounted_price"
                                    :percentage-off="product.percentage_off"
                                    size="sm"
                                />
                            </div>
                            <button @click.stop="">Add To Cart</button>
                        </div>
                    </div>
                    <div v-else class="py-12 text-center text-gray-400 text-sm">
                        No active offers in this shop right now.
                    </div>
                </div>

                <!-- About tab -->
                <div v-if="activeTab === 'about'" class="about-tab">
                    <div class="about-tab-wrapper">
                        <h2 class="name">{{ shop.name }}</h2>
                        <p class="description">{{ shop.description || 'No description provided.' }}</p>
                        <div class="info">
                            <h3 class="title">Shop Information</h3>
                            <dl class="details">
                                <div><dt>Shop Owner</dt><dd>{{ shop.owner.name }}</dd></div>
                                <div><dt>Member Since</dt><dd>{{ shop.owner.joined }}</dd></div>
                                <div><dt>Category</dt><dd>{{ shop.category || 'General' }}</dd></div>
                                <div><dt>Total Products</dt><dd>{{ shop.stats.total_products }}</dd></div>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Reviews tab -->
                <div v-if="activeTab === 'reviews'" class="reviews-tab">
                    <div class="reviews-wrapper">
                        <Star class="w-12 h-12 text-gray-300 mx-auto mb-3" />
                        <h3 class="title">Reviews Coming Soon</h3>
                        <p class="description">Customer reviews will appear here once available.</p>
                    </div>
                </div>
            </section>
        </div>
    </GuestLayout>
</template>