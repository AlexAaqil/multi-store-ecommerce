<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import GuestLayout from '@/layouts/GuestLayout.vue';
import { computed, ref, watch } from 'vue';
import checkout from '@/routes/checkout'; // Assuming you have a route file for checkout

interface Location {
    id: number;
    name: string;
};

interface Area {
    id: number;
    name: string;
    location_id: number;
    shipping_cost: number;
};

const form = useForm({
    name: '',
    email: '',
    phone_number: '',
    delivery_method: '',
    payment_method: '',
    extra_details: '',
    delivery_location_id: null as number | null,
    delivery_area_id: null as number | null,
});

// Mock data - Replace with actual API calls
const locations = ref<Location[]>([
    { id: 1, name: 'Nairobi' },
    { id: 2, name: 'Mombasa' },
    { id: 3, name: 'Kisumu' },
    { id: 4, name: 'Nakuru' },
]);

// Areas with their shipping costs
const allAreas = ref<Area[]>([
    // Nairobi areas
    { id: 1, name: 'Westlands', location_id: 1, shipping_cost: 180 },
    { id: 2, name: 'CBD', location_id: 1, shipping_cost: 150 },
    { id: 3, name: 'Kilimani', location_id: 1, shipping_cost: 160 },
    { id: 4, name: 'Langata', location_id: 1, shipping_cost: 200 },
    { id: 5, name: 'Eastlands', location_id: 1, shipping_cost: 120 },
    // Mombasa areas
    { id: 6, name: 'Nyali', location_id: 2, shipping_cost: 250 },
    { id: 7, name: 'Mombasa CBD', location_id: 2, shipping_cost: 200 },
    { id: 8, name: 'Bamburi', location_id: 2, shipping_cost: 280 },
    // Kisumu areas
    { id: 9, name: 'Milimani', location_id: 3, shipping_cost: 220 },
    { id: 10, name: 'Kisumu CBD', location_id: 3, shipping_cost: 200 },
    // Nakuru areas
    { id: 11, name: 'Nakuru CBD', location_id: 4, shipping_cost: 180 },
    { id: 12, name: 'Milimani', location_id: 4, shipping_cost: 190 },
]);

const availableAreas = ref<Area[]>([]);
const selectedAreaShipping = ref(0);
const subtotal = 1650; // Base product price

// Computed property to check if delivery method is selected
const isDeliverySelected = computed(() => form.delivery_method === 'delivery');

// Watch for location changes to filter areas
watch(() => form.delivery_location_id, (newLocationId) => {
    // TODO: Replace with actual API call
    // axios.get(`/api/locations/${newLocationId}/areas`).then(response => {
    //     availableAreas.value = response.data;
    // });
    if (newLocationId) {
        // Filter areas based on selected location
        availableAreas.value = allAreas.value.filter(area => area.location_id === newLocationId);
        
        // Reset area selection when location changes
        form.delivery_area_id = null;
        selectedAreaShipping.value = 0;
    } else {
        availableAreas.value = [];
        form.delivery_area_id = null;
        selectedAreaShipping.value = 0;
    }
});

// Watch for area selection to update shipping cost
watch(() => form.delivery_area_id, (newAreaId) => {
    if (newAreaId) {
        const selectedArea = availableAreas.value.find(area => area.id === newAreaId);
        selectedAreaShipping.value = selectedArea?.shipping_cost || 0;
    } else {
        selectedAreaShipping.value = 0;
    }
});

// Get location name by ID (handles null)
const getLocationName = (id: number | null) => {
    if (!id) return '';
    const location = locations.value.find(l => l.id === id);
    return location ? location.name : '';
};

// Get area name by ID (handles null)
const getAreaName = (id: number | null) => {
    if (!id) return '';
    const area = allAreas.value.find(a => a.id === id);
    return area ? area.name : '';
};

// Total amount calculation
const totalAmount = computed(() => {
    return isDeliverySelected.value ? subtotal + selectedAreaShipping.value : subtotal;
});

// Submit form with proper error handling
const submitForm = () => {
    // Transform data if needed before submission
    form.transform((data) => ({
        ...data,
        // Convert null values to empty string for backend if needed
        delivery_location_id: data.delivery_location_id ?? '',
        delivery_area_id: data.delivery_area_id ?? '',
    })).post(checkout.store().url, {
        preserveScroll: true,
        onSuccess: () => {
            // Redirect to order confirmation or success page
            // router.visit('/orders'); // Uncomment and adjust as needed
        },
        onError: (errors) => {
            console.error('Checkout failed:', errors);
            // Error messages are automatically displayed in form.errors
        },
        onFinish: () => {
            // Optional: Any cleanup after request finishes
        },
    });
};
</script>

<template>
    <Head title="Checkout Page" />

    <GuestLayout>
        <div class="main_container CheckoutPage">
            <div class="Hero">
                <h1>Checkout</h1>
            </div>

            <div class="checkout-page-wrapper">
                <div class="billing-info">
                    <h2>Billing Information</h2>
                    <div class="form">
                        <form @submit.prevent="submitForm">
                            <div class="form-section">
                                <p class="section-title">Contact Information</p>
                                <div class="inputs-group">
                                    <label for="name">Full Name</label>
                                    <input type="text" v-model="form.name" id="name">
                                    <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                                </div>

                                <div class="inputs-group">
                                    <label for="email">Email Address</label>
                                    <input type="email" v-model="form.email" id="email">
                                    <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
                                </div>

                                <div class="inputs-group">
                                    <label for="phone_number">Phone Number</label>
                                    <input type="text" v-model="form.phone_number" id="phone_number">
                                    <p v-if="form.errors.phone_number" class="text-red-500 text-xs mt-1">{{ form.errors.phone_number }}</p>
                                </div>
                            </div>

                            <div class="form-section">
                                <p class="section-title">Delivery Method</p>
                                <div class="radio-buttons">
                                    <label class="radio-button-label">
                                        <input
                                            type="radio"
                                            name="delivery_method"
                                            value="shop"
                                            v-model="form.delivery_method"
                                            class="hidden-radio"
                                        />
                                        <span class="radio-button-style">Shop</span>
                                    </label>

                                    <label class="radio-button-label">
                                        <input
                                            type="radio"
                                            name="delivery_method"
                                            value="delivery"
                                            v-model="form.delivery_method"
                                            class="hidden-radio"
                                        />
                                        <span class="radio-button-style">Delivery</span>
                                    </label>
                                </div>
                                <p v-if="form.errors.delivery_method" class="text-red-500 text-xs mt-1">{{ form.errors.delivery_method }}</p>
                            </div>

                            <!-- Conditional Delivery Details Section -->
                            <div class="form-section" v-if="isDeliverySelected">
                                <p class="section-title">Delivery Details</p>
                                
                                <div class="inputs-group-wrapper">
                                    <!-- Location Select Dropdown -->
                                    <div class="inputs-group">
                                        <label for="delivery_location">Select Location</label>
                                        <select 
                                            v-model.number="form.delivery_location_id" 
                                            id="delivery_location"
                                            class="delivery-select"
                                        >
                                            <option :value="null">-- Select Location --</option>
                                            <option 
                                                v-for="location in locations" 
                                                :key="location.id" 
                                                :value="location.id"
                                            >
                                                {{ location.name }}
                                            </option>
                                        </select>
                                        <p v-if="form.errors.delivery_location_id" class="text-red-500 text-xs mt-1">{{ form.errors.delivery_location_id }}</p>
                                    </div>

                                    <!-- Area Select Dropdown -->
                                    <div class="inputs-group">
                                        <label for="delivery_area">Select Area</label>
                                        <select 
                                            v-model.number="form.delivery_area_id" 
                                            id="delivery_area"
                                            class="delivery-select"
                                            :disabled="!form.delivery_location_id"
                                        >
                                            <option :value="null">-- Select Area --</option>
                                            <option 
                                                v-for="area in availableAreas" 
                                                :key="area.id" 
                                                :value="area.id"
                                            >
                                                {{ area.name }} (+Ksh. {{ area.shipping_cost }})
                                            </option>
                                        </select>
                                        <p v-if="form.errors.delivery_area_id" class="text-red-500 text-xs mt-1">{{ form.errors.delivery_area_id }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <p class="section-title">Payment Method</p>
                                <div class="radio-buttons">
                                    <label class="radio-button-label">
                                        <input
                                            type="radio"
                                            name="payment_method"
                                            value="mpesa"
                                            v-model="form.payment_method"
                                            class="hidden-radio"
                                        />
                                        <span class="radio-button-style">MPesa</span>
                                    </label>

                                    <label class="radio-button-label">
                                        <input
                                            type="radio"
                                            name="payment_method"
                                            value="stripe"
                                            v-model="form.payment_method"
                                            class="hidden-radio"
                                        />
                                        <span class="radio-button-style">Stripe</span>
                                    </label>
                                </div>
                                <p v-if="form.errors.payment_method" class="text-red-500 text-xs mt-1">{{ form.errors.payment_method }}</p>
                            </div>

                            <div class="form-section">
                                <p class="section-title">Extra Details</p>
                                <div class="inputs-group">
                                    <label for="extra_details">Extra Information about your Order</label>
                                    <textarea 
                                        placeholder="Any extra information about your order" 
                                        v-model="form.extra_details" 
                                        id="extra_details"
                                    ></textarea>
                                    <p v-if="form.errors.extra_details" class="text-red-500 text-xs mt-1">{{ form.errors.extra_details }}</p>
                                </div>
                            </div>

                            <div class="action-button">
                                <button type="submit" :disabled="form.processing">
                                    {{ form.processing ? 'Processing...' : 'Confirm Order' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="order-details">
                    <h2>Order Details</h2>
                    <div class="order-details-wrapper">
                        <div class="order">
                            <p class="label">Subtotal</p>
                            <p class="number">: Ksh. {{ subtotal.toFixed(2) }}</p>
                        </div>

                        <div class="order" v-if="isDeliverySelected && selectedAreaShipping > 0">
                            <p class="label">Shipping Cost ({{ getAreaName(form.delivery_area_id) }})</p>
                            <p class="number text-green-600">: + Ksh. {{ selectedAreaShipping.toFixed(2) }}</p>
                        </div>

                        <div class="order total">
                            <p class="label font-bold">Total Amount</p>
                            <p class="number font-bold" :class="{ 'text-green-600': isDeliverySelected }">
                                : Ksh. {{ totalAmount.toFixed(2) }}
                            </p>
                        </div>
                    </div>
                    
                    <!-- Delivery address preview with note -->
                    <div v-if="isDeliverySelected && (form.delivery_location_id || form.delivery_area_id)" class="delivery-preview mt-4 p-3 bg-gray-50 rounded-md">
                        <p class="text-sm font-medium mb-1">Delivery Address:</p>
                        <p class="text-sm text-gray-600">
                            {{ getAreaName(form.delivery_area_id) }}{{ getAreaName(form.delivery_area_id) && getLocationName(form.delivery_location_id) ? ', ' : '' }}{{ getLocationName(form.delivery_location_id) }}
                        </p>
                        <p class="text-sm text-gray-500 mt-2 pt-1 border-t border-gray-200">
                            <span class="font-medium">Note:</span> Delivery typically takes 2-3 business days after confirmation.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>