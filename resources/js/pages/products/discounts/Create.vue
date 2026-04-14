<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import FormError from '@/components/custom/FormError.vue';
import discounts from '@/routes/discounts';

interface Category {
    id: number;
    name: string;
}

interface Product {
    id: number;
    name: string;
    price: number;
}

interface Shop {
    id: number;
    name: string;
}

const props = defineProps<{
    shop: Shop;
    categories: Category[];
    products: Product[];
}>();

// Scope constants matching the backend
const SCOPE_SHOP_WIDE         = 0;
const SCOPE_PRODUCT_CATEGORY  = 1;
const SCOPE_SPECIFIC_PRODUCTS = 2;

const form = useForm({
    name:             '',
    type:             0,        // 0=percentage, 1=fixed
    value:            '',
    scope:            SCOPE_SHOP_WIDE,
    // Separate fields for different scope types
    category_ids:     [] as number[],   // For scope = 1
    product_ids:      [] as number[],   // For scope = 2
    min_order_amount: '',
    min_quantity:     '',
    starts_at:        '',
    expires_at:       '',
    is_active:        true,
});

// Watch scope changes and clear the appropriate fields
const onScopeChange = () => {
    // Clear both arrays when scope changes
    form.category_ids = [];
    form.product_ids = [];
};

// Dynamic label for the value field
const valueLabel = computed(() =>
    form.type === 1 ? 'Discount Amount (KES)' : 'Discount Percentage (%)'
);

const valuePlaceholder = computed(() =>
    form.type === 1 ? 'e.g., 500' : 'e.g., 20'
);

// Whether a target picker is needed
const needsTargets = computed(() =>
    form.scope === SCOPE_PRODUCT_CATEGORY || form.scope === SCOPE_SPECIFIC_PRODUCTS
);

// Get the selected IDs based on current scope (for validation display)
const selectedCount = computed(() => {
    if (form.scope === SCOPE_PRODUCT_CATEGORY) return form.category_ids.length;
    if (form.scope === SCOPE_SPECIFIC_PRODUCTS) return form.product_ids.length;
    return 0;
});

// Transform form data before submission based on scope
const submitForm = () => {
    form.transform((data) => ({
        // Base fields that are always included
        name: data.name,
        type: data.type,
        value: data.value,
        scope: data.scope,
        min_order_amount: data.min_order_amount,
        min_quantity: data.min_quantity,
        starts_at: data.starts_at,
        expires_at: data.expires_at,
        is_active: data.is_active,

        // Conditionally add fields based on scope
        ...(data.scope === SCOPE_PRODUCT_CATEGORY && { category_ids: data.category_ids }),
        ...(data.scope === SCOPE_SPECIFIC_PRODUCTS && { product_ids: data.product_ids }),
    })).post(discounts.store().url, {
        preserveScroll: true,
        onSuccess: () => router.visit('/discounts'),
    });
};
</script>

<template>
    <Head title="Create Discount" />

    <div class="py-8 px-4">
        <div class="mb-6">
            <h1 class="text-2xl font-serif font-semibold">Create New Discount</h1>
            <p class="text-sm text-gray-500 mt-1">Add a new discount to {{ shop.name }}</p>
        </div>

        <form @submit.prevent="submitForm">

            <!-- Name -->
            <div class="inputs-group">
                <Label for="name" class="required">Discount Name</Label>
                <Input
                    id="name"
                    v-model="form.name"
                    type="text"
                    required
                    placeholder="e.g., Flash Sale, Weekend Deal"
                />
                <p class="text-xs text-gray-400">Shown to customers on product and deals pages</p>
                <FormError :error="form.errors.name" />
            </div>

            <!-- Type + Value -->
            <div class="inputs-group-wrapper">
                <div class="inputs-group">
                    <Label for="type" class="required">Discount Type</Label>
                    <select
                        id="type"
                        v-model.number="form.type"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900"
                    >
                        <option :value="0">Percentage (%)</option>
                        <option :value="1">Fixed Amount (KES)</option>
                    </select>
                    <FormError :error="form.errors.type" />
                </div>

                <div class="inputs-group">
                    <Label for="value" class="required">{{ valueLabel }}</Label>
                    <Input
                        id="value"
                        v-model="form.value"
                        type="number"
                        step="0.01"
                        :max="form.type === 0 ? 100 : undefined"
                        min="0.01"
                        required
                        :placeholder="valuePlaceholder"
                    />
                    <!-- Warn if percentage > 100 -->
                    <p v-if="form.type === 0 && Number(form.value) > 100" class="text-xs text-red-500">
                        Percentage cannot exceed 100%
                    </p>
                    <FormError :error="form.errors.value" />
                </div>
            </div>

            <!-- Scope -->
            <div class="inputs-group">
                <Label for="scope" class="required">Applies To</Label>
                <select
                    id="scope"
                    v-model.number="form.scope"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900"
                    @change="onScopeChange"
                >
                    <option :value="0">All products in my shop</option>
                    <option :value="1">Specific categories</option>
                    <option :value="2">Specific products</option>
                </select>
                <FormError :error="form.errors.scope" />
            </div>

            <!-- Target picker: categories -->
            <div v-if="form.scope === SCOPE_PRODUCT_CATEGORY" class="inputs-group">
                <Label class="required">Select Categories</Label>
                <div class="border border-gray-200 rounded-lg divide-y max-h-56 overflow-y-auto">
                    <label
                        v-for="category in categories"
                        :key="category.id"
                        class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 cursor-pointer"
                    >
                        <input
                            type="checkbox"
                            :value="category.id"
                            v-model="form.category_ids"
                            class="w-4 h-4 rounded border-gray-300 focus:ring-2 focus:ring-gray-900"
                        />
                        <span class="text-sm">{{ category.name }}</span>
                    </label>
                    <p v-if="categories.length === 0" class="px-4 py-3 text-sm text-gray-400">
                        No categories available.
                    </p>
                </div>
                <p class="text-xs text-gray-400">Discount applies to all products in selected categories</p>
                <FormError :error="form.errors.category_ids" />
            </div>

            <!-- Target picker: products -->
            <div v-if="form.scope === SCOPE_SPECIFIC_PRODUCTS" class="inputs-group">
                <Label class="required">Select Products</Label>
                <div class="border border-gray-200 rounded-lg divide-y max-h-56 overflow-y-auto">
                    <label
                        v-for="product in products"
                        :key="product.id"
                        class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 cursor-pointer"
                    >
                        <input
                            type="checkbox"
                            :value="product.id"
                            v-model="form.product_ids"
                            class="w-4 h-4 rounded border-gray-300 focus:ring-2 focus:ring-gray-900"
                        />
                        <span class="text-sm flex-1">{{ product.name }}</span>
                        <span class="text-xs text-gray-400">KES {{ product.price.toLocaleString() }}</span>
                    </label>
                    <p v-if="products.length === 0" class="px-4 py-3 text-sm text-gray-400">
                        No products available.
                    </p>
                </div>
                <p class="text-xs text-gray-400">{{ selectedCount }} product(s) selected</p>
                <FormError :error="form.errors.product_ids" />
            </div>

            <!-- Schedule -->
            <div class="inputs-group-wrapper">
                <div class="inputs-group">
                    <Label for="starts_at" class="required">Starts At</Label>
                    <Input
                        id="starts_at"
                        v-model="form.starts_at"
                        type="datetime-local"
                        required
                    />
                    <FormError :error="form.errors.starts_at" />
                </div>

                <div class="inputs-group">
                    <Label for="expires_at" class="required">Expires At</Label>
                    <Input
                        id="expires_at"
                        v-model="form.expires_at"
                        type="datetime-local"
                        required
                        :min="form.starts_at"
                    />
                    <FormError :error="form.errors.expires_at" />
                </div>
            </div>

            <!-- Optional conditions -->
            <div class="inputs-group-wrapper">
                <div class="inputs-group">
                    <Label for="min_order_amount">Min. Order Amount (KES)</Label>
                    <Input
                        id="min_order_amount"
                        v-model="form.min_order_amount"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="No minimum"
                    />
                    <p class="text-xs text-gray-400">Leave blank for no minimum</p>
                    <FormError :error="form.errors.min_order_amount" />
                </div>

                <div class="inputs-group">
                    <Label for="min_quantity">Min. Quantity</Label>
                    <Input
                        id="min_quantity"
                        v-model="form.min_quantity"
                        type="number"
                        min="1"
                        placeholder="No minimum"
                    />
                    <p class="text-xs text-gray-400">Leave blank for no minimum</p>
                    <FormError :error="form.errors.min_quantity" />
                </div>
            </div>

            <!-- Active toggle -->
            <div class="inputs-group">
                <div class="flex items-center gap-2">
                    <input
                        type="checkbox"
                        id="is_active"
                        v-model="form.is_active"
                        class="w-4 h-4 rounded border-gray-300 focus:ring-2 focus:ring-gray-900"
                    />
                    <Label for="is_active">Active immediately</Label>
                </div>
                <p class="text-xs text-gray-400 mt-1">
                    If unchecked, the discount is saved as a draft and won't apply to any products
                </p>
            </div>

            <div class="submit-buttons">
                <Button 
                    type="submit" 
                    :disabled="form.processing || (needsTargets && selectedCount === 0)"
                >
                    {{ form.processing ? 'Creating...' : 'Create Discount' }}
                </Button>
                <Button type="button" variant="outline" @click="router.visit('/discounts')">
                    Cancel
                </Button>
            </div>

        </form>
    </div>
</template>