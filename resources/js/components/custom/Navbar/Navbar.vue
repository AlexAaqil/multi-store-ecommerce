<script setup lang="ts">
import { Link, usePage, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';
import { HomeIcon, BadgeDollarSign, MessageCircleMore, Menu, X, Barcode, ShoppingCart, LayoutDashboard } from 'lucide-vue-next';
import { Sun, Moon } from 'lucide-vue-next';
import { useAppearance } from '@/composables/useAppearance';
import { useCartStore } from '@/stores/cart';
import UserMenu from './UserMenu.vue';

const { appearance, updateAppearance } = useAppearance();

const page = usePage();
const user = computed(() => page.props.auth?.user);
const isMobileMenuOpen = ref(false);
const cartStore = useCartStore();

// Load cart on mount
onMounted(() => {
    cartStore.fetchCart();
});

// Watch for user login to refresh cart
watch(user, (newUser, oldUser) => {
    if (newUser && !oldUser) {
        // User just logged in - refresh cart
        cartStore.fetchCart();
    }
});

const tabs = computed(() => {
    const tabsList = [
        { id: 'home', label: 'Discover', icon: HomeIcon, path: '/' },
        { id: 'deals', label: 'Deals & Offers', icon: BadgeDollarSign, path: '/deals' },
        { id: 'social', label: 'Community', icon: MessageCircleMore, path: '/community', hasNotification: true },
    ];
    
    // Add Dashboard tab for sellers (role 2) or admins (role 1)
    if (user.value) {
        tabsList.unshift({ 
            id: 'dashboard', 
            label: 'Dashboard', 
            icon: LayoutDashboard, 
            path: '/dashboard' 
        });
    }
    
    return tabsList;
});

const activeTab = computed(() => {
    const currentPath = page.url;
    const tab = tabs.value.find(t => currentPath.startsWith(t.path));
    return tab?.id || 'home';
});

const handleTabClick = (tab: typeof tabs.value[0]) => {
    router.visit(tab.path);
    isMobileMenuOpen.value = false;
};

const handleLogout = () => {
    router.post('/logout');
};
</script>

<template>
    <nav class="bg-white border-b border-gray-200 fixed top-0 w-full z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="flex items-center justify-between h-14">
                <!-- Logo -->
                <Link href="/" class="flex items-center font-serif text-xl dark:text-gray-400">
                    Multi<span class="italic text-gray-500 dark:text-gray-900">Store</span>
                </Link>

                <!-- Desktop Navigation - Hidden on mobile -->
                <div class="hidden md:flex gap-1">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="handleTabClick(tab)"
                        :class="[
                            'px-4 py-1.5 rounded-lg text-sm transition-all flex items-center gap-1.5',
                            activeTab === tab.id
                                ? 'bg-gray-900 text-white'
                                : 'text-gray-500 hover:text-gray-900 hover:bg-gray-100'
                        ]"
                    >
                        <component :is="tab.icon" class="w-4 h-4" />
                        {{ tab.label }}
                        <span 
                            v-if="tab.hasNotification" 
                            class="w-1.5 h-1.5 bg-red-500 rounded-full inline-block ml-1"
                        />
                    </button>
                </div>

                <!-- Right Section -->
                <div class="flex items-center gap-2">
                    <!-- Theme toggle -->
                    <button
                        @click="updateAppearance(appearance === 'light' ? 'dark' : 'light')"
                        class="p-2 rounded-lg hover:bg-gray-100 transition-colors dark:hover:bg-gray-800"
                        title="Toggle theme"
                    >
                        <Sun v-if="appearance === 'light'" class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                        <Moon v-else class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                    </button>

                    <!-- Cart Button -->
                    <Link
                        href="/cart"
                        class="relative p-2 rounded-lg hover:bg-gray-100 transition-colors"
                        title="Cart"
                    >
                        <ShoppingCart class="w-5 h-5 text-gray-600" />
                        <span 
                            v-if="cartStore.itemCount > 0"
                            class="absolute -top-1 -right-1 bg-green-500 text-white text-xs rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1"
                        >
                            {{ cartStore.itemCount > 99 ? '99+' : cartStore.itemCount }}
                        </span>
                    </Link>

                    <!-- User Menu (desktop and mobile) -->
                    <UserMenu v-if="user" />
                    
                    <div v-else class="flex items-center gap-2">
                        <Link
                            href="/login"
                            class="px-3 py-1.5 text-sm hover:text-gray-900 dark:text-gray-500"
                        >
                            Login
                        </Link>
                        <Link
                            href="/register"
                            class="px-3 py-1.5 bg-gray-900 text-white rounded-lg text-sm hover:bg-gray-800"
                        >
                            Sign Up
                        </Link>
                    </div>

                    <!-- Mobile menu button -->
                    <button
                        @click="isMobileMenuOpen = !isMobileMenuOpen"
                        class="md:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors"
                    >
                        <Menu v-if="!isMobileMenuOpen" class="w-5 h-5 text-gray-600" />
                        <X v-else class="w-5 h-5 text-gray-600" />
                    </button>
                </div>
            </div>

            <!-- Mobile Navigation Menu -->
            <div v-if="isMobileMenuOpen" class="md:hidden py-4 border-t border-gray-100">
                <!-- Mobile tabs -->
                <div class="flex flex-col gap-1 mb-4">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="handleTabClick(tab)"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-all',
                            activeTab === tab.id
                                ? 'bg-gray-100 text-gray-900'
                                : 'text-gray-600 hover:bg-gray-50'
                        ]"
                    >
                        <component :is="tab.icon" class="w-5 h-5" />
                        {{ tab.label }}
                        <span 
                            v-if="tab.hasNotification" 
                            class="w-1.5 h-1.5 bg-red-500 rounded-full inline-block"
                        />
                    </button>
                </div>

                <!-- Cart link for mobile -->
                <Link
                    href="/cart"
                    @click="isMobileMenuOpen = false"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 hover:bg-gray-50 mb-2"
                >
                    <ShoppingCart class="w-5 h-5" />
                    Cart
                    <span 
                        v-if="cartStore.itemCount > 0"
                        class="bg-green-500 text-white text-xs rounded-full px-1.5 py-0.5 ml-auto"
                    >
                        {{ cartStore.itemCount }}
                    </span>
                </Link>

                <!-- User info for mobile (if logged in) -->
                <div v-if="user" class="pt-3 mt-2 border-t border-gray-100">
                    <div class="px-3 py-2">
                        <p class="text-sm font-medium text-gray-900">{{ user.name }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ user.email }}</p>
                    </div>
                    <button
                        @click="handleLogout"
                        class="w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-gray-50 rounded-lg mt-1"
                    >
                        Logout
                    </button>
                </div>

                <!-- Login/Register for mobile -->
                <div v-else class="flex gap-2 pt-3 mt-2 border-t border-gray-100">
                    <Link
                        href="/login"
                        @click="isMobileMenuOpen = false"
                        class="flex-1 px-3 py-2 text-sm text-gray-700 border border-gray-200 rounded-lg text-center"
                    >
                        Login
                    </Link>
                    <Link
                        href="/register"
                        @click="isMobileMenuOpen = false"
                        class="flex-1 px-3 py-2 text-sm bg-gray-900 text-white rounded-lg text-center"
                    >
                        Sign Up
                    </Link>
                </div>
            </div>
        </div>
    </nav>
</template>