<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Pencil, Trash2, Plus } from 'lucide-vue-next';
import Toast from '@/components/custom/ToastNotification/Index.vue';
import PageHeader from '@/components/custom/PageHeader.vue';
import DeleteConfirmationDialog from '@/components/custom/DeleteConfirmation.vue';
import discounts from '@/routes/discounts';

const page = usePage<any>();

interface Discount {
    id: number;
    name: string;
    type_label: string;
    formatted_value: string;
    scope_label: string;
    is_active: boolean;
    is_expired: boolean;
    starts_at: string;
    expires_at: string;
    targets_count: number;
}

interface Props {
    discounts: {
        data: Discount[];
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
    router.get('/discounts', {
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

const deleteDiscount = (id: number) => {
    router.delete(`/discounts/${id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Discounts" />

    <div class="app_container">
        <Toast v-if="page.props.flash?.message" 
            :message="page.props.flash.message" 
            :type="page.props.flash.type || 'success'" 
            :duration="5000" 
        />

        <PageHeader 
            title="Discounts"
            v-model:search="search"
            search-placeholder="Search discounts by name..."
            :show-search-badge="true"
            :create-href="discounts.create().url"
            create-button-text="Create Discount"
        />

        <div class="table-wrapper">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Name</TableHead>
                        <TableHead>Type</TableHead>
                        <TableHead>Value</TableHead>
                        <TableHead>Applies To</TableHead>
                        <TableHead>Schedule</TableHead>
                        <TableHead class="thead-actions">Actions</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-for="discount in props.discounts.data" :key="discount.id">
                        <TableCell class="font-medium">{{ discount.name }}</TableCell>
                        <TableCell>{{ discount.type_label }}</TableCell>
                        <TableCell>
                            <span class="font-semibold text-red-600">
                                {{ discount.formatted_value }}
                            </span>
                        </TableCell>
                        <TableCell>
                            {{ discount.scope_label }}
                            <span v-if="discount.targets_count > 0" class="text-xs text-gray-500 ml-1">
                                ({{ discount.targets_count }})
                            </span>
                        </TableCell>
                        <TableCell class="text-sm">
                            {{ discount.starts_at }} - {{ discount.expires_at }}
                        </TableCell>
                        <TableCell class="tbody-actions">
                            <div class="actions">
                                <Link :href="discounts.edit(discount.id).url" class="action edit">
                                    <Pencil class="w-4 h-4" />
                                </Link>
                                <DeleteConfirmationDialog 
                                    :url="discounts.destroy(discount.id).url" 
                                    title="Delete Discount?" 
                                    description="This discount will be deleted permanently!" 
                                    confirm-text="Delete Discount">
                                    <template #trigger>
                                        <button class="action delete">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </template>
                                </DeleteConfirmationDialog>
                            </div>
                        </TableCell>
                    </TableRow>
                    <TableRow v-if="props.discounts.data.length === 0">
                        <TableCell colspan="6" class="text-center py-8 text-gray-500">
                            No discounts found. Click "Create Discount" to get started.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- Pagination -->
        <div v-if="props.discounts.links && props.discounts.links.length > 3" class="mt-6 flex justify-center gap-1">
            <Link v-for="link in props.discounts.links" :key="link.label" :href="link.url || '#'" 
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