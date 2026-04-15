<!-- resources/js/pages/guest/productdetails/ProductDetails.vue -->
<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import { Star, Minus, Plus, ShoppingCart, Heart, Share2, Truck, RotateCcw, ShieldCheck } from 'lucide-vue-next';
import GuestLayout from '@/layouts/GuestLayout.vue';
import ProductPrice from '@/components/custom/Products/Price.vue';

interface ProductImage {
    id: number;
    name: string;
    full_url: string;
}

interface Product {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price: number;
    discounted_price: number;
    discount_pct: number;
    image_url: string;
    cost_price: number | null;
    stock_qty: number;
    sku: string;
    category: string | null;
    is_active: boolean;
    is_featured: boolean;
    images: ProductImage[];
    shop: {
        id: number;
        name: string;
        slug: string;
        logo_url: string;
        is_verified: boolean;
    };
}

interface Review {
    id: number;
    rating: number;
    comment: string;
    user: {
        name: string;
    };
    created_at: string;
}

const props = defineProps<{
    product: Product;
    related_products: Product[];
    reviews: Review[];
}>();

const quantity = ref(1);
const selectedImage = ref(props.product.images?.[0]?.full_url || null);
const activeTab = ref('details');

const tabs = [
    { id: 'details', label: 'Product Details' },
    { id: 'reviews', label: `Reviews (${props.reviews.length})` },
    { id: 'shipping', label: 'Shipping & Returns' },
];

const decreaseQuantity = () => {
    if (quantity.value > 1) {
        quantity.value--;
    }
};

const increaseQuantity = () => {
    if (quantity.value < (props.product.stock_qty || 10)) {
        quantity.value++;
    }
};

const addToCart = () => {
    // Add to cart logic
    console.log('Adding to cart:', props.product.id, quantity.value);
};

const formatPrice = (price: number) => {
    return new Intl.NumberFormat('en-KE', {
        style: 'currency',
        currency: 'KES',
        minimumFractionDigits: 0,
    }).format(price);
};

const averageRating = computed(() => {
    if (props.reviews.length === 0) return 0;
    const sum = props.reviews.reduce((acc, review) => acc + review.rating, 0);
    return (sum / props.reviews.length).toFixed(1);
});
</script>

<template>
    <Head :title="product.name" />

    <GuestLayout>
        <div class="main_container ProductDetailsPage">
            <section class="back-button">
                <Link href="/">
                    <Button variant="outline">
                        &larr; Back to Discover
                    </Button>
                </Link>
            </section>

            <section class="ProductHero">
                <div class="product-hero-wrapper">
                    <div class="product-gallery">
                        <div class="main-image">
                            <img :src="selectedImage || product.image_url" :alt="product.name" />
                        </div>
                        <div v-if="product.images && product.images.length > 1" class="thumbnail-list">
                            <div 
                                v-for="image in product.images" 
                                :key="image.id"
                                class="thumbnail"
                                :class="{ active: selectedImage === image.full_url }"
                                @click="selectedImage = image.full_url"
                            >
                                <img :src="image.full_url" :alt="product.name" />
                            </div>
                        </div>
                    </div>

                    <div class="product-info">
                        <div class="shop-info">
                            <Link :href="`/shop-details/${product.shop.slug}`" class="shop-link">
                                <img :src="product.shop.logo_url" :alt="product.shop.name" class="shop-logo" />
                                <span>{{ product.shop.name }}</span>
                            </Link>
                            <span v-if="product.shop.is_verified" class="verified-badge">✓ Verified</span>
                        </div>

                        <h1 class="product-title">{{ product.name }}</h1>
                        
                        <div class="product-meta">
                            <div class="rating">
                                <Star class="w-4 h-4 fill-yellow-400 text-yellow-400" />
                                <span>{{ averageRating }}</span>
                                <span class="reviews-count">({{ reviews.length }} reviews)</span>
                            </div>
                            <span class="sku">SKU: {{ product.sku || 'N/A' }}</span>
                        </div>

                        <div class="product-price">
                            <ProductPrice
                                :price="product.price"
                                :discounted-price="product.discounted_price"
                                :discount-pct="product.discount_pct"
                                size="lg"
                            />
                        </div>

                        <div class="stock-status" :class="{ 'low-stock': product.stock_qty <= 5 && product.stock_qty > 0, 'out-of-stock': product.stock_qty === 0 }">
                            <span v-if="product.stock_qty > 0">✓ In Stock ({{ product.stock_qty }} units available)</span>
                            <span v-else>✗ Out of Stock</span>
                        </div>

                        <!-- Quantity Selector -->
                        <div v-if="product.stock_qty > 0" class="quantity-selector">
                            <span class="label">Quantity:</span>
                            <div class="quantity-controls">
                                <button @click="decreaseQuantity" :disabled="quantity <= 1">
                                    <Minus class="w-4 h-4" />
                                </button>
                                <span class="quantity">{{ quantity }}</span>
                                <button @click="increaseQuantity" :disabled="quantity >= product.stock_qty">
                                    <Plus class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="action-buttons">
                            <button 
                                v-if="product.stock_qty > 0"
                                @click="addToCart" 
                                class="add-to-cart-btn"
                            >
                                <ShoppingCart class="w-4 h-4" />
                                Add to Cart
                            </button>
                            <button v-else class="add-to-cart-btn disabled" disabled>
                                Out of Stock
                            </button>
                            <button class="wishlist-btn">
                                <Heart class="w-4 h-4" />
                            </button>
                            <button class="share-btn">
                                <Share2 class="w-4 h-4" />
                            </button>
                        </div>

                        <!-- Trust Badges -->
                        <div class="trust-badges">
                            <div class="badge">
                                <Truck class="w-4 h-4" />
                                <span>Free Shipping</span>
                            </div>
                            <div class="badge">
                                <RotateCcw class="w-4 h-4" />
                                <span>30 Days Returns</span>
                            </div>
                            <div class="badge">
                                <ShieldCheck class="w-4 h-4" />
                                <span>Secure Checkout</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Tabs Section -->
            <section class="TabsContent">
                <div class="tabs">
                    <div class="tabs-wrapper">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="[
                                'tab-btn',
                                activeTab === tab.id ? 'active' : ''
                            ]"
                        >
                            {{ tab.label }}
                        </button>
                    </div>
                </div>

                <!-- Product Details Tab -->
                <div v-if="activeTab === 'details'" class="details-tab">
                    <div class="details-wrapper">
                        <div class="description">
                            <h3>Product Description</h3>
                            <p>{{ product.description || 'No description available for this product.' }}</p>
                        </div>
                        
                        <div class="specifications">
                            <h3>Specifications</h3>
                            <table class="specs-table">
                                <tr>
                                    <td>SKU</td>
                                    <td>{{ product.sku || 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td>Category</td>
                                    <td>{{ product.category || 'Uncategorized' }}</td>
                                </tr>
                                <tr>
                                    <td>Availability</td>
                                    <td>{{ product.stock_qty > 0 ? 'In Stock' : 'Out of Stock' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Reviews Tab -->
                <div v-if="activeTab === 'reviews'" class="reviews-tab">
                    <div v-if="reviews.length > 0" class="reviews-wrapper">
                        <div v-for="review in reviews" :key="review.id" class="review-card">
                            <div class="review-header">
                                <div class="reviewer-info">
                                    <div class="reviewer-avatar">
                                        {{ review.user.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <div class="reviewer-name">{{ review.user.name }}</div>
                                        <div class="review-date">{{ new Date(review.created_at).toLocaleDateString() }}</div>
                                    </div>
                                </div>
                                <div class="review-rating">
                                    <Star v-for="n in 5" :key="n" class="w-3 h-3" :class="n <= review.rating ? 'filled' : 'empty'" />
                                </div>
                            </div>
                            <p class="review-comment">{{ review.comment }}</p>
                        </div>
                    </div>
                    <div v-else class="empty-reviews">
                        <Star class="w-12 h-12 text-gray-300 mx-auto mb-3" />
                        <h3>No Reviews Yet</h3>
                        <p>Be the first to review this product!</p>
                    </div>
                </div>

                <!-- Shipping Tab -->
                <div v-if="activeTab === 'shipping'" class="shipping-tab">
                    <div class="shipping-wrapper">
                        <h3>Shipping Information</h3>
                        <p>Free shipping on all orders over KES 5,000. Delivery within 3-5 business days.</p>
                        
                        <h3>Returns Policy</h3>
                        <p>30 days return policy. Items must be unused and in original packaging.</p>
                        
                        <h3>Warranty</h3>
                        <p>1 year manufacturer warranty on all products.</p>
                    </div>
                </div>
            </section>

            <!-- Related Products Section -->
            <section v-if="related_products.length > 0" class="RelatedProducts">
                <div class="section-header">
                    <h2 class="section-title">You May Also Like</h2>
                    <Link href="/products" class="section-link">View all →</Link>
                </div>
                <div class="related-products-wrapper">
                    <div 
                        v-for="product in related_products.slice(0, 4)" 
                        :key="product.id"
                        class="related-product-card"
                        @click="$inertia.visit(`/product-details/${product.slug}`)"
                    >
                        <div class="image">
                            <img :src="product.images[0]?.full_url" :alt="product.name" />
                        </div>
                        <div class="info">
                            <h3 class="name">{{ product.name }}</h3>
                            <p class="price">{{ formatPrice(product.price) }}</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </GuestLayout>
</template>