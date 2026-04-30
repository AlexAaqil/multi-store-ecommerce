<script setup>
import { Link, router } from '@inertiajs/vue3';
import cartRoutes from '@/routes/cart';
import GuestLayout from '@/layouts/GuestLayout.vue';

const props = defineProps({
  cart: Object
});

const updateQuantity = (itemId, newQuantity) => {
  if (newQuantity < 1) return;
  
  router.put(cartRoutes.update(itemId), {
    quantity: newQuantity
  }, {
    preserveScroll: true
  });
};

const removeItem = (itemId) => {
  if (confirm('Remove this item?')) {
    router.delete(cartRoutes.remove(itemId), {
      preserveScroll: true
    });
  }
};

const clearCart = () => {
  if (confirm('Clear your entire cart?')) {
    router.delete(cartRoutes.clear());
  }
};
</script>

<template>
  <GuestLayout>
    <div class="main_container">
      <h1 class="text-2xl font-bold mb-6">Shopping Cart</h1>
      
      <div v-if="!cart.items || cart.items.length === 0" class="text-center py-12">
        <p class="text-gray-500">Your cart is empty</p>
        <Link href="/" class="mt-4 inline-block bg-blue-600 text-white px-6 py-2 rounded">
          Continue Shopping
        </Link>
      </div>
      
      <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Cart Items -->
        <div class="lg:col-span-2">
          <div v-for="item in cart.items" :key="item.id" class="border rounded-lg p-4 mb-4">
            <div class="flex gap-4">
              <img 
                :src="item.product_image" 
                :alt="item.product_name"
                class="w-24 h-24 object-cover rounded"
              />
              
              <div class="flex-1">
                <div class="flex justify-between">
                  <div>
                    <h3 class="font-semibold">{{ item.product_name }}</h3>
                    <p class="text-sm text-gray-600">{{ item.shop_name }}</p>
                    <p v-if="item.variant_name" class="text-sm text-gray-500">
                      {{ item.variant_name }}
                    </p>
                  </div>
                  <p class="font-semibold">KES {{ (item.subtotal || 0).toFixed(2) }}</p>
                </div>
                
                <div class="flex items-center gap-4 mt-4">
                  <!-- Quantity selector -->
                  <div class="flex items-center border rounded">
                    <button 
                      @click="updateQuantity(item.id, item.quantity - 1)"
                      :disabled="item.quantity <= 1"
                      class="px-3 py-1 hover:bg-gray-100 disabled:opacity-50"
                    >
                      -
                    </button>
                    <span class="px-4 py-1">{{ item.quantity }}</span>
                    <button 
                      @click="updateQuantity(item.id, item.quantity + 1)"
                      :disabled="item.quantity >= item.stock"
                      class="px-3 py-1 hover:bg-gray-100 disabled:opacity-50"
                    >
                      +
                    </button>
                  </div>
                  
                  <button 
                    @click="removeItem(item.id)"
                    class="text-red-600 hover:text-red-800"
                  >
                    Remove
                  </button>
                </div>
                
                <p v-if="item.quantity > item.stock" class="text-red-600 text-sm mt-2">
                  Only {{ item.stock }} items available
                </p>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Cart Summary -->
        <div class="lg:col-span-1">
          <div class="border rounded-lg p-4">
            <h2 class="text-lg font-semibold mb-4">Order Summary</h2>
            
            <div class="space-y-2 mb-4">
              <div class="flex justify-between">
                <span>Subtotal ({{ cart.item_count || 0 }} items)</span>
                <span>KES {{ (cart.total || 0).toFixed(2) }}</span>
              </div>
              <div class="flex justify-between">
                <span>Shipping</span>
                <span>Calculated at checkout</span>
              </div>
            </div>
            
            <div class="border-t pt-4 mb-4">
              <div class="flex justify-between font-semibold text-lg">
                <span>Total</span>
                <span>KES {{ (cart.total || 0).toFixed(2) }}</span>
              </div>
            </div>
            
            <!-- Should route to: :href="cartRoutes.checkout()" -->
            <Link 
              href="/checkout"
              class="block w-full bg-blue-600 text-white text-center px-6 py-3 rounded hover:bg-blue-700"
            >
              Proceed to Checkout
            </Link>
            
            <button 
              @click="clearCart"
              class="block w-full text-red-600 text-center mt-3"
            >
              Clear Cart
            </button>
          </div>
        </div>
      </div>
    </div>
  </GuestLayout>
</template>