<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Pencil, Trash2, Plus, Store } from 'lucide-vue-next';
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle, AlertDialogTrigger } from '@/components/ui/alert-dialog';

interface Shop {
    id: number;
    name: string;
    description: string | null;
    category: string | null;
    logo_url: string | null;
    cover_url: string | null;
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
        total: number;
    };
    hasShop: boolean;
}

const props = defineProps<Props>();

const deleteShop = (id: number) => {
    router.delete(`/shops/${id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="My Shops" />

    <div class="app_container ShopPage">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-serif font-semibold">My Shops</h1>
                <p class="text-sm text-gray-500 mt-1">Manage your stores and settings</p>
            </div>
            <Button v-if="!hasShop" as-child>
                <a href="/shops/create">
                    <Plus class="w-4 h-4 mr-2" />
                    Create Shop
                </a>
            </Button>
        </div>

        <!-- Shops Grid -->
        <div v-if="shops.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div
                v-for="shop in shops.data"
                :key="shop.id"
                class="bg-white border border-gray-200 rounded-xl overflow-hidden hover:shadow-lg transition-shadow"
            >
                <!-- Cover Image -->
                <div class="h-32 bg-gradient-to-r from-gray-100 to-gray-200 relative">
                    <img
                        v-if="shop.cover_url"
                        :src="shop.cover_url"
                        :alt="shop.name"
                        class="w-full h-full object-cover"
                    />
                    <div v-else class="w-full h-full flex items-center justify-center text-4xl">
                        🏪
                    </div>
                    
                    <!-- Status Badge -->
                    <div class="absolute top-3 right-3">
                        <span
                            :class="[
                                'px-2 py-1 rounded-full text-xs font-medium',
                                shop.is_active
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-gray-100 text-gray-500'
                            ]"
                        >
                            {{ shop.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>

                <!-- Logo & Info -->
                <div class="p-4">
                    <div class="flex items-start gap-3">
                        <!-- Logo -->
                        <div class="w-14 h-14 rounded-xl bg-gray-100 flex items-center justify-center text-2xl -mt-8 border-2 border-white shadow-sm overflow-hidden">
                            <img
                                v-if="shop.logo_url"
                                :src="shop.logo_url"
                                :alt="shop.name"
                                class="w-full h-full object-cover"
                            />
                            <Store v-else class="w-6 h-6 text-gray-400" />
                        </div>

                        <!-- Shop Name & Category -->
                        <div class="flex-1">
                            <h3 class="font-semibold text-lg">{{ shop.name }}</h3>
                            <p class="text-xs text-gray-400">{{ shop.category || 'Uncategorized' }}</p>
                        </div>
                    </div>

                    <!-- Description -->
                    <p class="text-sm text-gray-600 mt-3 line-clamp-2">
                        {{ shop.description || 'No description provided' }}
                    </p>

                    <!-- Contact Info -->
                    <div class="mt-3 space-y-1">
                        <p v-if="shop.contact_email" class="text-xs text-gray-500 flex items-center gap-1">
                            📧 {{ shop.contact_email }}
                        </p>
                        <p v-if="shop.contact_phone" class="text-xs text-gray-500 flex items-center gap-1">
                            📞 {{ shop.contact_phone }}
                        </p>
                    </div>

                    <!-- Meta Info -->
                    <div class="mt-3 pt-3 border-t border-gray-100 flex justify-between items-center">
                        <div class="text-xs text-gray-400">
                            Created {{ new Date(shop.created_at).toLocaleDateString() }}
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex gap-2">
                            <!-- Edit -->
                            <a
                                :href="`/shops/${shop.id}/edit`"
                                class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors"
                                title="Edit Shop"
                            >
                                <Pencil class="w-4 h-4 text-gray-500" />
                            </a>

                            <!-- Delete -->
                            <AlertDialog>
                                <AlertDialogTrigger as-child>
                                    <button
                                        class="p-1.5 rounded-lg hover:bg-red-50 transition-colors"
                                        title="Delete Shop"
                                    >
                                        <Trash2 class="w-4 h-4 text-red-500" />
                                    </button>
                                </AlertDialogTrigger>
                                <AlertDialogContent>
                                    <AlertDialogHeader>
                                        <AlertDialogTitle>Delete Shop?</AlertDialogTitle>
                                        <AlertDialogDescription>
                                            Are you sure you want to delete "{{ shop.name }}"? 
                                            This action cannot be undone and will delete all 
                                            products and data associated with this shop.
                                        </AlertDialogDescription>
                                    </AlertDialogHeader>
                                    <AlertDialogFooter>
                                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                                        <AlertDialogAction
                                            @click="deleteShop(shop.id)"
                                            class="bg-red-600 hover:bg-red-700"
                                        >
                                            Delete
                                        </AlertDialogAction>
                                    </AlertDialogFooter>
                                </AlertDialogContent>
                            </AlertDialog>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-12">
            <div class="text-6xl mb-4">🏪</div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No shops yet</h3>
            <p class="text-gray-500 mb-4">Create your first shop to start selling</p>
            <Button as-child>
                <a href="/shops/create">
                    <Plus class="w-4 h-4 mr-2" />
                    Create Shop
                </a>
            </Button>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>