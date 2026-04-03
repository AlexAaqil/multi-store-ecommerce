<script setup lang="ts">
import { Link, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { HomeIcon, BadgeDollarSign, MessageCircleMore, Menu, X, Barcode } from 'lucide-vue-next';
import UserMenu from './UserMenu.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const isMobileMenuOpen = ref(false);

const tabs = [
  { id: 'home', label: 'Discover', icon: HomeIcon, path: '/' },
  { id: 'deals', label: 'Deals & Offers', icon: BadgeDollarSign, path: '/deals' },
  { id: 'social', label: 'Community', icon: MessageCircleMore, path: '/community', hasNotification: true },
];

const activeTab = computed(() => {
  const currentPath = page.url;
  const tab = tabs.find(t => t.path === currentPath);
  return tab?.id || 'home';
});

const handleTabClick = (tab: typeof tabs[0]) => {
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
        <Link href="/" class="flex items-centerfont-serif text-xl">
          Multi<span class="italic text-gray-500">Store</span>
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
          <!-- List Product button - only for sellers -->
          <button 
            v-if="user && user.role === 2"
            @click="router.visit('/dashboard')"
            class="hidden sm:block px-3 py-1.5 border border-gray-200 rounded-lg text-sm hover:bg-gray-50 whitespace-nowrap"
          >
            + List Product
          </button>
          
          <!-- User Menu (desktop and mobile) -->
          <UserMenu v-if="user" />
          
          <div v-else class="flex items-center gap-2">
            <Link
              href="/login"
              class="px-3 py-1.5 text-sm hover:text-gray-900"
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

        <!-- List Product button for mobile -->
        <button 
          v-if="user && user.role === 2"
          @click="router.visit('/dashboard'); isMobileMenuOpen = false"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-700 hover:bg-gray-50 mb-2"
        >
          <Barcode class="w-5 h-5" />
          List Product
        </button>

        <!-- User info for mobile (if logged in) -->
        <div v-if="user" class="pt-3 mt-2 border-t border-gray-100">
          <div class="px-3 py-2">
            <p class="text-sm font-medium text-gray-900">{{ user.full_name }}</p>
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