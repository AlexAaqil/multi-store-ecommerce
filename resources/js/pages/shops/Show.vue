<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { Star } from 'lucide-vue-next';
import Button from '@/components/ui/button/Button.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Pencil, Trash2, Package, Clock } from 'lucide-vue-next';
import Toast from '@/components/custom/ToastNotification/Index.vue';
import DeleteConfirmationDialog from '@/components/custom/DeleteConfirmation.vue';
import ProductCard from '@/pages/guest/components/ProductCard.vue';
import { useCartStore } from '@/stores/cart';
import productsRoutes from '@/routes/products';

const page = usePage<any>();

interface Discount {
    name: string;
    type: number;
    value: number;
    formatted_value: string;
    percentage_off: number;
    starts_at: string;
    expires_at: string;
    is_scheduled: boolean;
    starts_in_days: number | null;
}

interface Product {
    id: number;
    name: string;
    sku: string;
    slug: string;
    price: number;
    stock_qty: number;
    category: string | null;
    is_active: boolean;
    created_at: string;
    image_url: string;
    percentage_off: number | null;
    discounted_price: number | null;
    discount_pct: number | null;
    discount: Discount | null;
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

const cartStore = useCartStore();
onMounted(() => {
    cartStore.fetchCart();
});

// Helper function to format discount display
const getDiscountDisplay = (product: Product) => {
    if (!product.discount) return null;
    
    const discount = product.discount;
    
    // For scheduled discounts
    if (discount.is_scheduled) {
        return {
            text: `Starts in ${discount.starts_in_days} day${discount.starts_in_days !== 1 ? 's' : ''}`,
            variant: 'scheduled',
            tooltip: `${discount.name}: ${discount.formatted_value} off - Starts ${new Date(discount.starts_at).toLocaleString()}`
        };
    }
    
    // For active discounts
    return {
        text: discount.formatted_value,
        variant: 'active',
        tooltip: `${discount.name}: ${discount.formatted_value} off - Valid until ${new Date(discount.expires_at).toLocaleString()}`
    };
};

// Format date for display
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString();
};
</script>

<template>
    <Head :title="shop.name" />

    <div class="main_container ShopShowPage">
        <Toast v-if="page.props.flash?.message" 
            :message="page.props.flash.message" 
            :type="page.props.flash.type || 'success'" 
            :duration="5000" 
        />

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
                                ? 'border-b-2 border-gray-900 text-gray-900 dark:text-gray-200'
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
                <div v-if="products.data.length > 0" class="">
                    <div class="table-wrapper">
                        <div class="table-header">
                            <div class="stats">
                                <h2>Products</h2>
                            </div>
                            
                            <div class="action-button">
                                <Link :href="productsRoutes.create().url">+ New Product</Link>
                            </div>
                        </div>

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Product</TableHead>
                                    <TableHead>SKU</TableHead>
                                    <TableHead>Discount</TableHead>
                                    <TableHead>Price</TableHead>
                                    <TableHead>Stock</TableHead>
                                    <TableHead>Category</TableHead>
                                    <TableHead class="thead-actions">Actions</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <TableRow v-for="product in props.products.data" :key="product.id">
                                    <TableCell>
                                        <div class="image-column">
                                            <div class="image">
                                                <img v-if="product.image_url" :src="product.image_url" />
                                                <Package v-else class="w-5 h-5 text-gray-400" />
                                            </div>
                                            <div class="product-info">
                                                <span class="font-medium">
                                                    {{ product.name }}
                                                    <span :class="[
                                                        'px-2 py-1 text-xs rounded-full ml-2',
                                                        product.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'
                                                    ]">
                                                        {{ product.is_active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </span>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-sm">{{ product.sku ?? '-' }}</TableCell>
                                    <TableCell>
                                        <div v-if="product.discount" class="discount-cell">
                                            <!-- Tooltip wrapper -->
                                            <div class="relative inline-block group">
                                                <span 
                                                    :class="[
                                                        'px-2 py-1 text-xs font-semibold rounded-full cursor-help',
                                                        getDiscountDisplay(product)?.variant === 'scheduled' 
                                                            ? 'bg-yellow-100 text-yellow-700' 
                                                            : 'bg-red-100 text-red-700'
                                                    ]"
                                                >
                                                    {{ getDiscountDisplay(product)?.text }}
                                                </span>
                                                
                                                <!-- Tooltip -->
                                                <div class="absolute z-10 invisible group-hover:visible bg-gray-900 text-white text-xs rounded-lg py-2 px-3 bottom-full left-1/2 transform -translate-x-1/2 mb-2 w-64">
                                                    <div class="font-semibold mb-1">{{ product.discount.name }}</div>
                                                    <div class="text-gray-300 text-xs">
                                                        {{ product.discount.formatted_value }} off
                                                    </div>
                                                    <div class="text-gray-400 text-xs mt-1">
                                                        <Clock class="inline w-3 h-3 mr-1" />
                                                        {{ formatDate(product.discount.starts_at) }} - {{ formatDate(product.discount.expires_at) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <span v-else class="text-gray-400 text-sm">-</span>
                                    </TableCell>
                                    <TableCell>
                                        <div class="price-cell">
                                            <span class="font-medium">
                                                KES {{ (product.discounted_price || product.price).toLocaleString() }}
                                            </span>
                                            <span v-if="product.discount" class="line-through text-gray-400 text-sm">{{ ' ' + product.price.toLocaleString() }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <span :class="[
                                            'px-2 py-1 text-xs rounded-full',
                                            product.stock_qty > 10 ? 'bg-green-100 text-green-700' :
                                            product.stock_qty > 0 ? 'bg-yellow-100 text-yellow-700' :
                                            'bg-red-100 text-red-700'
                                        ]">
                                            {{ product.stock_qty }} units
                                        </span>
                                    </TableCell>
                                    <TableCell>{{ product.category || 'Uncategorized' }}</TableCell>
                                    <TableCell class="tbody-actions">
                                        <div class="actions">
                                            <Link :href="productsRoutes.edit(product.id).url" class="action edit">
                                                <Pencil class="w-4 h-4" />
                                            </Link>
                                            <DeleteConfirmationDialog 
                                                :url="productsRoutes.destroy(product.id).url" 
                                                title="Delete Product?" 
                                                description="This product will be deleted permanently!" 
                                                confirm-text="Delete Product">
                                                <template #trigger>
                                                    <button class="action delete">
                                                        <Trash2 class="w-4 h-4" />
                                                    </button>
                                                </template>
                                            </DeleteConfirmationDialog>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="props.products.data.length === 0">
                                    <TableCell colspan="7" class="text-center py-8 text-gray-500">
                                        No products found. Click "Add Product" to get started.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="props.products.links && props.products.links.length > 3" class="mt-6 flex justify-center gap-1">
                        <Link v-for="link in props.products.links" :key="link.label" :href="link.url || '#'" 
                            v-html="link.label" 
                            class="px-3 py-1 border rounded text-sm"
                            :class="{
                                'bg-gray-100 text-gray-500 cursor-not-allowed': !link.url,
                                'bg-blue-600 text-white border-blue-600': link.active,
                                'hover:bg-gray-50': link.url && !link.active
                            }" />
                    </div>
                </div>
                <div v-else class="py-12 text-center text-gray-400 text-sm">
                    No products available yet.
                </div>
            </div>

            <!-- On Offer tab — only discounted products -->
            <div v-if="activeTab === 'on_offer'" class="products-tab">
                <div v-if="discountedProducts.length > 0" class="products-wrapper">
                    <ProductCard
                        v-for="product in discountedProducts"
                        :key="product.id"
                        :product="product"
                        :show-stock-indicator="true"
                        :show-add-to-cart="true"
                    />
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
</template>