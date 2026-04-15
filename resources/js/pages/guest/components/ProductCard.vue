<!-- gurest/components/ProductCard.vue -->
 <script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProductPrice from '@/components/custom/Products/Price.vue';
import AddToCartButton from '@/pages/guest/components/AddToCartButton.vue';
import { useCartStore } from '@/stores/cart';

interface Product {
    id: number;
    name: string;
    slug: string;
    price: number;
    image_url: string;
    category: string | null;
    stock_qty: number;
    discounted_price: number | null;
    discount_pct: number | null;
}

const props = withDefaults(defineProps<{
    product: Product;
    showStockIndicator?: boolean;
    showAddToCart?: boolean;
    buttonText?: string;
}>(), {
    showStockIndicator: true,
    showAddToCart: true,
    buttonText: 'Add To Cart'
});

const cartStore = useCartStore();

// Check if product is in cart
const isInCart = computed(() => {
    return cartStore.items.some(item => item.product_id === props.product.id);
});

// Get cart quantity
const cartQuantity = computed(() => {
    const item = cartStore.items.find(item => item.product_id === props.product.id);
    return item?.quantity || 0;
});

// Determine button display text
const buttonDisplayText = computed(() => {
    if (props.buttonText !== 'Add To Cart') {
        return props.buttonText;
    }
    return isInCart.value ? `Update Cart (${cartQuantity.value})` : 'Add To Cart';
});
</script>

<template>
    <div class="product-card">
        <!-- Product Image -->
        <Link :href="`/product-details/${product.slug}`" class="product-image">
            <div class="image">
                <img 
                    :src="product.image_url" 
                    :alt="product.name"
                    loading="lazy"
                />
            </div>
        </Link>

        <!-- Product Info -->
        <div class="info">
            <div class="extras">
                <p class="category">{{ product.category || 'Uncategorized' }}</p>

                <!-- Stock Indicator (optional) -->
                <div v-if="showStockIndicator" class="stock-indicator">
                    <div v-if="product.stock_qty <= 5 && product.stock_qty > 0" class="stock-low">
                        Only {{ product.stock_qty }} left
                    </div>
                    <div v-else-if="product.stock_qty === 0" class="stock-out">
                        Out of stock
                    </div>
                    <div v-else class="stock-count">{{ product.stock_qty }} In Stock</div>
                </div>
            </div>
            <Link :href="`/product-details/${product.slug}`" class="name-link">
                <h3 class="name">{{ product.name }}</h3>
            </Link>
            
            <ProductPrice
                :price="product.price"
                :discounted-price="product.discounted_price"
                :discount-pct="product.discount_pct"
                size="sm"
            />
        </div>

        <!-- Add to Cart Button (optional) -->
        <AddToCartButton
            v-if="showAddToCart"
            :product-id="product.id"
            :product-name="product.name"
            :stock="product.stock_qty"
            :button-text="buttonDisplayText"
            @success="cartStore.fetchCart()"
        />
    </div>
</template>