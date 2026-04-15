import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

interface CartItem {
    id: number;
    product_id: number;
    product_name: string;
    product_slug: string;
    product_image: string | null;
    variant_id: number | null;
    variant_name: string | null;
    shop_id: number;
    shop_name: string;
    quantity: number;
    unit_price: number;
    subtotal: number;
    stock: number;
}

export const useCartStore = defineStore('cart', () => {
    // Initialize with empty array
    const items = ref<CartItem[]>([]);
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    const total = computed(() => {
        if (!items.value || !Array.isArray(items.value)) return 0;
        return items.value.reduce((sum, item) => sum + (item.subtotal || 0), 0);
    });

    const itemCount = computed(() => {
        if (!items.value || !Array.isArray(items.value)) return 0;
        return items.value.reduce((sum, item) => sum + (item.quantity || 0), 0);
    });

    const fetchCart = async () => {
        isLoading.value = true;
        try {
            const response = await fetch('/cart/summary', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            items.value = data.items || [];
        } catch (err) {
            console.error('Failed to fetch cart:', err);
            items.value = [];
        } finally {
            isLoading.value = false;
        }
    };

    const addToCart = async (productId: number, quantity: number = 1, variantId: number | null = null) => {
        isLoading.value = true;
        error.value = null;
        
        try {
            const response = await fetch('/cart/add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity,
                    variant_id: variantId
                })
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to add to cart');
            }

            const result = await response.json();
            
            if (result.cart) {
                items.value = result.cart.items;
            }
            
            return { success: true, message: result.message };
        } catch (err: any) {
            error.value = err.message;
            return { success: false, message: err.message };
        } finally {
            isLoading.value = false;
        }
    };

    const updateQuantity = async (itemId: number, quantity: number) => {
        if (quantity < 1) {
            return removeItem(itemId);
        }

        isLoading.value = true;
        try {
            const response = await fetch(`/cart/item/${itemId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ quantity })
            });

            if (!response.ok) {
                throw new Error('Failed to update quantity');
            }

            const result = await response.json();
            if (result.cart) {
                items.value = result.cart.items;
            }
        } catch (err) {
            console.error('Failed to update quantity:', err);
        } finally {
            isLoading.value = false;
        }
    };

    const removeItem = async (itemId: number) => {
        isLoading.value = true;
        try {
            const response = await fetch(`/cart/item/${itemId}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to remove item');
            }

            const result = await response.json();
            if (result.cart) {
                items.value = result.cart.items;
            }
        } catch (err) {
            console.error('Failed to remove item:', err);
        } finally {
            isLoading.value = false;
        }
    };

    const clearCart = async () => {
        isLoading.value = true;
        try {
            const response = await fetch('/cart/clear', {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to clear cart');
            }

            items.value = [];
        } catch (err) {
            console.error('Failed to clear cart:', err);
        } finally {
            isLoading.value = false;
        }
    };

    return {
        items,
        isLoading,
        error,
        total,
        itemCount,
        fetchCart,
        addToCart,
        updateQuantity,
        removeItem,
        clearCart
    };
});