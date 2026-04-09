<script setup lang="ts">
import { usePage, Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import Toast from '@/components/custom/ToastNotification/Index.vue';
import PageHeader from '@/components/custom/PageHeader.vue';
import DeleteConfirmationDialog from '@/components/custom/DeleteConfirmation.vue';
import productCategories from '@/routes/product-categories';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Product Categories', href: productCategories.index() },
        ],
    },
});

const page = usePage<any>();

interface ProductCategory {
    id: number;
    name: string;
    slug: string;
    products_count: number;
}

interface Props {
    categories: ProductCategory[];
    filters: {
        search?: string;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');

const performSearch = useDebounceFn(() => {
    router.get('/product-categories', {
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
</script>

<template>
    <Head title="Product Categories" />

    <div class="app_container">
        <PageHeader 
            title="Product Categories"
            v-model:search="search"
            search-placeholder="Search product categories by name..."
            :show-search-badge="true"
            :create-href="productCategories.create().url"
            create-button-text="Create Category"
        />

        <!-- Active Filter Indicator -->
        <div v-if="search" class="mb-4">
            <div class="text-xs text-blue-600 dark:text-blue-400">
                <span class="font-medium">Active filter:</span> 
                Searching for "{{ search }}"
                <button @click="clearFilters" class="ml-2 text-red-600 hover:text-red-800">Clear</button>
            </div>
        </div>

        <div class="product_categories_table">
            <div class="bg-white dark:bg-gray-900 rounded-lg border shadow-sm overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow class="bg-gray-50 dark:bg-gray-800">
                            <TableHead class="w-[50px]">#</TableHead>
                            <TableHead>Category</TableHead>
                            <TableHead>Slug</TableHead>
                            <TableHead>Products</TableHead>
                            <TableHead class="text-center">Actions</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <TableRow v-for="(category, index) in props.categories" 
                                 :key="category.id"
                                 class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <TableCell class="font-medium">
                                {{ index + 1 }}
                            </TableCell>
                            <TableCell>{{ category.name }}</TableCell>
                            <TableCell>{{ category.slug }}</TableCell>
                            <TableCell>{{ category.products_count }}</TableCell>
                            <!-- <TableCell>{{ category.shops_count || 0 }}</TableCell> -->
                            <TableCell class="text-center">
                                <div class="flex justify-center space-x-2">
                                    <Link :href="productCategories.edit(category.id).url" 
                                          class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 hover:underline">
                                        Edit
                                    </Link>
                                    <span class="text-gray-300 dark:text-gray-600">|</span>
                                    <DeleteConfirmationDialog 
                                        :url="productCategories.destroy(category.id).url" 
                                        title="Delete Category?" 
                                        description="This category will be deleted permanently!" 
                                        confirm-text="Delete Category">
                                        <template #trigger>
                                            <button class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 hover:underline">
                                                Delete
                                            </button>
                                        </template>
                                    </DeleteConfirmationDialog>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="props.categories.length === 0">
                            <TableCell colspan="5" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                No product categories found.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Results summary -->
            <div v-if="props.categories.length" class="mt-4 text-gray-600 dark:text-gray-400 text-sm flex justify-center items-center gap-4">
                <div>
                    Showing {{ props.categories.length }} categories
                </div>
                <div v-if="search" class="text-blue-600 dark:text-blue-400">
                    Filtered results
                </div>
            </div>
        </div>
    </div>
</template>