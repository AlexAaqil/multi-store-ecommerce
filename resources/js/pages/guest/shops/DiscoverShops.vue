<script setup lang="ts">
import GuestLayout from '@/layouts/GuestLayout.vue';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

// Define props
const props = defineProps<{
    shops: {
        data: Array<{
            id: number;
            name: string;
            slug: string;
            category: string;
            description: string;
            logo_image: string;
            cover_image: string;
            is_active: boolean;
            is_verified: boolean;
            rating: number;
            reviews_count: number;
        }>;
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters?: {
        search: string;
    };
}>();

// Search functionality
const search = ref(props.filters?.search || '');

const handleSearch = () => {
    router.get('/discover-shops', { search: search.value }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearSearch = () => {
    search.value = '';
    router.get('/discover-shops', { search: '' }, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <GuestLayout>
        <div class="main_container max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <section class="Hero flex items-center justify-between">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Discover Shops</h1>
                    <p class="text-gray-600 mt-2">Browse through our collection of shops</p>
                </div>

                <!-- Search Bar -->
                <div class="mb-8">
                    <div class="max-w-md">
                        <label for="search" class="sr-only">Search shops</label>
                        <div class="relative">
                            <input
                                id="search"
                                v-model="search"
                                type="text"
                                placeholder="Search by shop name, contact email, or contact phone..."
                                @keyup.enter="handleSearch"
                                class="w-full rounded-sm border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 py-1 px-4 pr-10"
                            />
                            <!-- Clear button (X) -->
                            <button
                                v-if="search.length > 0"
                                @click="clearSearch"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none"
                                type="button"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Shops Grid -->
            <div v-if="shops.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <Link
                    v-for="shop in shops.data"
                    :key="shop.id"
                    :href="`/shop-details/${shop.slug}`"
                    class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300"
                >
                    <!-- Cover Image -->
                    <div class="h-32 bg-gray-200 relative">
                        <img
                            v-if="shop.cover_image"
                            :src="shop.cover_image"
                            :alt="shop.name"
                            class="w-full h-full object-cover"
                        />
                        <div v-else class="w-full h-full bg-gradient-to-r from-gray-400 to-gray-600"></div>
                        
                        <!-- Verified Badge -->
                        <div v-if="shop.is_verified" class="absolute top-2 right-2 bg-blue-500 text-white text-xs px-2 py-1 rounded-full">
                            Verified
                        </div>
                    </div>

                    <div class="p-4">
                        <!-- Logo and Name -->
                        <div class="flex items-start gap-3">
                            <div class="w-12 h-12 rounded-full bg-gray-200 overflow-hidden flex-shrink-0">
                                <img
                                    v-if="shop.logo_image"
                                    :src="shop.logo_image"
                                    :alt="shop.name"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center text-gray-500 text-lg font-bold">
                                    {{ shop.name.charAt(0) }}
                                </div>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-lg text-gray-900">{{ shop.name }}</h3>
                                <p v-if="shop.category" class="text-sm text-gray-500">{{ shop.category }}</p>
                            </div>
                        </div>

                        <!-- Description -->
                        <p class="text-gray-600 text-sm mt-2 line-clamp-2">
                            {{ shop.description || 'No description available' }}
                        </p>

                        <!-- Rating and Stats -->
                        <div class="mt-3 flex items-center justify-between">
                            <div class="flex items-center gap-1">
                                <span class="text-yellow-400">★</span>
                                <span class="text-sm font-medium">{{ shop.rating }}</span>
                                <span class="text-xs text-gray-500">({{ shop.reviews_count }} reviews)</span>
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ shop.is_active ? 'Open' : 'Closed' }}
                            </div>
                        </div>
                    </div>
                </Link>
            </div>

            <!-- No Results -->
            <div v-else class="text-center py-12">
                <p class="text-gray-500 text-lg">No shops found</p>
                <p v-if="filters?.search" class="text-gray-400 mt-2">
                    No shops matching "{{ filters.search }}" were found.
                </p>
            </div>

            <!-- Pagination -->
            <div v-if="shops.links && shops.last_page > 1" class="mt-8 flex justify-center">
                <div class="flex gap-2">
                    <Link
                        v-for="link in shops.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        :class="[
                            'px-3 py-2 rounded-md text-sm',
                            link.active
                                ? 'bg-gray-900 text-white'
                                : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300',
                            !link.url && 'opacity-50 cursor-not-allowed'
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </GuestLayout>
</template>