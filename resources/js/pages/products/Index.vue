<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Pencil, Trash2, Plus, Package, Clock, Tag, Calendar } from 'lucide-vue-next';
import Toast from '@/components/custom/ToastNotification/Index.vue';
import PageHeader from '@/components/custom/PageHeader.vue';
import DeleteConfirmationDialog from '@/components/custom/DeleteConfirmation.vue';
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
    price: number;
    stock_qty: number;
    category: string | null;
    is_active: boolean;
    created_at: string;
    image_url: string;
    percentage_off: number | null;
    discounted_price: number | null;
    discount: Discount | null;
}

interface Props {
    products: {
        data: Product[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
        links: {
            url: string | null;
            label: string;
            active: boolean;
        }[];
        first_page_url: string | null;
        last_page_url: string | null;
        next_page_url: string | null;
        prev_page_url: string | null;
        path: string;
    };
    shop: {
        id: number;
        name: string;
    };
    filters: {
        search?: string;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');

const performSearch = useDebounceFn(() => {
    router.get('/products', {
        search: search.value
    }, {
        preserveState: true,
        replace: true
    });
}, 300);

watch(search, () => {
    performSearch();
});

const clearFilters = () => {
    search.value = '';
};

const deleteProduct = (id: number) => {
    router.delete(`/products/${id}`, {
        preserveScroll: true,
    });
};

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
    <Head title="Products" />

    <div class="app_container">
        <Toast v-if="page.props.flash?.message" 
            :message="page.props.flash.message" 
            :type="page.props.flash.type || 'success'" 
            :duration="5000" 
        />

        <PageHeader 
            title="Products"
            v-model:search="search"
            search-placeholder="Search products by name..."
            :show-search-badge="true"
            :create-href="productsRoutes.create().url"
            create-button-text="Create Product"
        />

        <div class="table-wrapper">
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
                        <TableCell class="text-sm text-gray-500">{{ product.sku ?? '-' }}</TableCell>
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
                                <span class="font-medium text-gray-900">
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
</template>