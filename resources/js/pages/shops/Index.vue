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
                <h1 class="text-2xl font-serif font-semibold">My Shop</h1>
                <p class="text-sm text-gray-500 mt-1">Manage your store and settings</p>
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
                class="shop-card shop-card-app"
            >
                <div class="shop-card-images">
                    <div class="shop-card-cover">
                        <img :src="shop.cover_image" alt="Shop cover Image">
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

                    <div class="shop-card-logo">
                        <img :src="shop.logo_image" alt="Shop logo">
                    </div>
                </div>

                <div class="shop-card-body">
                    <div class="shop-card-body">
                        <h3 class="shop-card-name">{{ shop.name }}</h3>
                        <p class="shop-card-category">{{ shop.category || 'Uncategorized' }}</p>
                        <p class="shop-card-description">
                            {{ shop.description || 'No description provided' }}
                        </p>

                        <div class="contact-info">
                            <p v-if="shop.contact_email" class="info">
                                📧 {{ shop.contact_email }}
                            </p>
                            <p v-if="shop.contact_phone" class="info">
                                📞 {{ shop.contact_phone }}
                            </p>
                        </div>

                        <div class="shop-card-ratings">
                        </div>
                        <div class="shop-rating">
                            <span>★</span> {{ shop.rating }} · {{ shop.reviews_count }} reviews
                        </div>

                        <div class="shop-card-meta">
                            <div class="date">
                                Created {{ new Date(shop.created_at).toLocaleDateString() }}
                            </div>
                            
                            <div class="actions">
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