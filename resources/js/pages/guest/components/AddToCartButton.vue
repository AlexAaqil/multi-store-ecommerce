<!-- guest/components/AddToCartButton.vue -->
<script setup lang="ts">
import { ref, computed } from 'vue';
import { useCartStore } from '@/stores/cart';
import { ShoppingCart } from 'lucide-vue-next';

const props = defineProps<{
    productId: number;
    productName: string;
    stock: number;
    variantId?: number | null;
    variantName?: string | null;
    buttonText?: string;
}>();

const emit = defineEmits<{
    (e: 'success'): void;
    (e: 'error', message: string): void;
}>();

const cartStore = useCartStore();
const isLoading = ref(false);
const showToast = ref(false);
const toastMessage = ref('');
const toastType = ref<'success' | 'error'>('success');

const isInStock = computed(() => props.stock > 0);

const showNotification = (message: string, type: 'success' | 'error') => {
    toastMessage.value = message;
    toastType.value = type;
    showToast.value = true;
    setTimeout(() => {
        showToast.value = false;
    }, 3000);
};

const handleAddToCart = async () => {
    if (!isInStock.value) {
        showNotification(`${props.productName} is out of stock`, 'error');
        return;
    }

    isLoading.value = true;
    
    const result = await cartStore.addToCart(
        props.productId,
        1, // Default quantity
        props.variantId || null
    );

    isLoading.value = false;

    if (result.success) {
        showNotification(`${props.productName} added to cart!`, 'success');
        emit('success');
    } else {
        showNotification(result.message || 'Failed to add to cart', 'error');
        emit('error', result.message);
    }
};
</script>

<template>
    <div>
        <button
            @click="handleAddToCart"
            :disabled="isLoading || !isInStock"
            :class="[
                'w-full px-4 py-2 rounded transition-colors',
                isInStock
                    ? 'bg-blue-600 hover:bg-blue-700 text-white'
                    : 'bg-gray-300 cursor-not-allowed text-gray-500'
            ]"
        >
            <span v-if="isLoading" class="flex items-center justify-center gap-2">
                <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                </svg>
                Adding...
            </span>
            <span v-else>
                <ShoppingCart /> {{ buttonText }}
            </span>
        </button>

        <!-- Toast notification for feedback -->
        <Teleport to="body">
            <div
                v-if="showToast"
                :class="[
                    'fixed bottom-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg transition-all',
                    toastType === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
                ]"
            >
                {{ toastMessage }}
            </div>
        </Teleport>
    </div>
</template>