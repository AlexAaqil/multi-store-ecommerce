<script setup lang="ts">
import { usePage, router, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { CircleUser, ShoppingBag, Heart, Cog, LogOut } from 'lucide-vue-next';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const isOpen = ref(false);

const menuItems = [
  // { label: 'My Profile', icon: CircleUser, href: '/settings/profile' },
  { label: 'My Orders', icon: ShoppingBag, href: '/orders' },
  // { label: 'Wishlist', icon: Heart, href: '/wishlist' },
  { label: 'Settings', icon: Cog, href: '/settings' },
];

const handleLogout = () => {
  router.post('/logout');
};

const closeMenu = () => {
  isOpen.value = false;
};
</script>

<template>
  <div class="relative">
    <button
      @click="isOpen = !isOpen"
      class="flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-gray-100 transition-colors"
    >
      <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-sm font-medium text-gray-600">
        <img :src=user?.image_url :alt=user?.name?.charAt(0)>
      </div>
      <span class="hidden sm:inline text-sm font-medium text-gray-700">
        {{ user?.name?.split(' ')[0] }}
      </span>
    </button>

    <!-- Dropdown Menu -->
    <div 
      v-if="isOpen"
      class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-50"
      @click.stop
    >
      <div class="px-4 py-3 border-b border-gray-100">
        <p class="text-sm font-medium text-gray-900">{{ user?.name }}</p>
        <p class="text-xs text-gray-500 mt-0.5">{{ user?.email }}</p>
      </div>
      
      <div v-for="item in menuItems" :key="item.label">
        <Link
          :href="item.href"
          @click="closeMenu"
          class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
        >
          <component :is="item.icon" class="w-4 h-4" />
          {{ item.label }}
        </Link>
      </div>
      
      <div class="border-t border-gray-100 mt-1 pt-1">
        <button
          @click="handleLogout"
          class="w-full flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-gray-50"
        >
          <LogOut class="w-4 h-4" />
          Logout
        </button>
      </div>
    </div>
  </div>
</template>