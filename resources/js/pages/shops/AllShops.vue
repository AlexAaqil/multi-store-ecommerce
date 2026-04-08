<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import PageHeader from '@/components/custom/PageHeader.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Eye, Store } from 'lucide-vue-next';
import shops from '@/routes/shops';
import shopCategories from '@/routes/shop-categories';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Shop Categories', href: shopCategories.index() },
            { title: 'Shops', href: shops.all() },
        ],
    },
});

interface Shop {
    id: number;
    name: string;
    description: string | null;
    category: string | null;
    rating: number;
    reviews_count: number;
    logo_image: string;
    cover_image: string;
    contact_email: string | null;
    contact_phone: string | null;
    is_active: boolean;
    is_verified: boolean;
    created_at: string;
}

interface Props {
    shops: {
        data: Shop[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
        links: {
            url: string | null;
            label: string;
            active: boolean;
        }[];
    };
    filters: {
        search?: string;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');

const performSearch = useDebounceFn(() => {
    router.get('/admin/shops', {
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

const viewShop = (shop: Shop) => {
    router.visit(`/shops/${shop.id}`);
};
</script>

<template>
    <Head title="All Shops" />

    <div class="app_container">
        <PageHeader 
            title="All Shops"
            v-model:search="search"
            search-placeholder="Search shops by name or contact email..."
            :show-search-badge="true"
        />

        <!-- Active Filter Indicator -->
        <div v-if="search" class="mb-4">
            <div class="text-xs text-blue-600 dark:text-blue-400">
                <span class="font-medium">Active filter:</span> 
                Searching for "{{ search }}"
                <button @click="clearFilters" class="ml-2 text-red-600 hover:text-red-800">Clear</button>
            </div>
        </div>

        <div class="shops_table">
            <div class="bg-white dark:bg-gray-900 rounded-lg border shadow-sm overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow class="bg-gray-50 dark:bg-gray-800">
                            <TableHead class="w-[50px]">#</TableHead>
                            <TableHead>Shop</TableHead>
                            <TableHead>Contact</TableHead>
                            <TableHead>Category</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Verified</TableHead>
                            <TableHead class="text-center">Actions</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <TableRow v-for="(shop, index) in props.shops.data" 
                                 :key="shop.id"
                                 class="hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
                                 @click="viewShop(shop)">
                            <TableCell class="font-medium">
                                {{ ((props.shops.current_page - 1) * props.shops.per_page) + index + 1 }}
                            </TableCell>
                            <TableCell>
                                <div class="flex items-center gap-3">
                                    <!-- Logo -->
                                    <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center overflow-hidden flex-shrink-0">
                                        <img 
                                            v-if="shop.logo_image" 
                                            :src="shop.logo_image" 
                                            :alt="shop.name"
                                            class="w-full h-full object-cover"
                                        />
                                        <Store v-else class="w-5 h-5 text-gray-400" />
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ shop.name }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            Created {{ new Date(shop.created_at).toLocaleDateString() }}
                                        </div>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <div class="text-sm">
                                    <div v-if="shop.contact_email" class="text-gray-600 dark:text-gray-400">
                                        📧 {{ shop.contact_email }}
                                    </div>
                                    <div v-if="shop.contact_phone" class="text-gray-500 dark:text-gray-500 text-xs mt-1">
                                        📞 {{ shop.contact_phone }}
                                    </div>
                                    <div v-if="!shop.contact_email && !shop.contact_phone" class="text-gray-400">
                                        No contact info
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                    {{ shop.category || 'Uncategorized' }}
                                </span>
                            </TableCell>
                            <TableCell>
                                <span :class="[
                                    'px-2 py-1 text-xs font-semibold rounded-full',
                                    shop.is_active 
                                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' 
                                        : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                                ]">
                                    {{ shop.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </TableCell>
                            <TableCell>
                                <span :class="[
                                    'px-2 py-1 text-xs font-semibold rounded-full',
                                    shop.is_verified 
                                        ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' 
                                        : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
                                ]">
                                    {{ shop.is_verified ? 'Verified' : 'Pending' }}
                                </span>
                            </TableCell>
                            <TableCell class="text-center">
                                <div class="flex justify-center space-x-2" @click.stop>
                                    <Link :href="`/shops/${shop.id}/edit`" 
                                          class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                        <Eye class="w-4 h-4" />
                                    </Link>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="props.shops.data.length === 0">
                            <TableCell colspan="7" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                No shops found.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div v-if="props.shops.links && props.shops.links.length > 3" class="mt-6 flex justify-center gap-1">
                <Link v-for="(link, linkIndex) in props.shops.links" 
                     :key="linkIndex" 
                     :href="link.url ?? ''" 
                     v-html="link.label" 
                     class="px-3 py-1 border rounded text-sm transition-colors"
                     :class="{
                        'bg-gray-100 text-gray-500 cursor-not-allowed dark:bg-gray-800 dark:text-gray-500': !link.url,
                        'bg-blue-600 text-white border-blue-600': link.active,
                        'hover:bg-gray-50 border-gray-300 dark:hover:bg-gray-800 dark:border-gray-700': link.url && !link.active,
                     }"
                     :disabled="!link.url"
                     preserve-scroll />
            </div>

            <!-- Results summary -->
            <div v-if="props.shops.total" class="mt-4 text-gray-600 dark:text-gray-400 text-sm flex justify-center items-center gap-4">
                <div>
                    Showing {{ props.shops.from || 1 }} 
                    to {{ props.shops.to || props.shops.total }} 
                    of {{ props.shops.total }} shops
                </div>
                <div v-if="search" class="text-blue-600 dark:text-blue-400">
                    Filtered results
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.shop-row {
    cursor: pointer;
}
</style>