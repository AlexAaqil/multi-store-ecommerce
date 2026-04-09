<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { ImagePlus, X } from 'lucide-vue-next';
import { ref } from 'vue';
import products from '@/routes/products';

interface Category {
    id: number;
    name: string;
}

interface Shop {
    id: number;
    name: string;
}

const props = defineProps<{
    shop: Shop;
    categories: Category[];
}>();

const form = useForm({
    name: '',
    description: '',
    cost_price: '',
    price: '',
    stock_qty: 0,
    low_stock_threshold: 5,
    barcode: '',
    sku: '',
    weight: '',
    weight_units: 'kg',
    is_featured: false,
    is_active: true,
    product_category_id: null as number | null,
    attributes: null as object | null,
    meta_title: '',
    meta_description: '',
    images: [] as File[],
});

const imagePreviews = ref<string[]>([]);

// Handle image selection
const handleImageChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    const files = target.files;
    if (files) {
        const newFiles = Array.from(files);
        form.images.push(...newFiles);
        newFiles.forEach(file => {
            const reader = new FileReader();
            reader.onload = (e) => {
                imagePreviews.value.push(e.target?.result as string);
            };
            reader.readAsDataURL(file);
        });
    }
};

// Remove image
const removeImage = (index: number) => {
    form.images.splice(index, 1);
    imagePreviews.value.splice(index, 1);
};

const hasImageErrors = () => {
    if (!form.errors.images) return false;
    // If images is an array of errors or just a string
    return typeof form.errors.images === 'string' || Object.keys(form.errors.images).length > 0;
};

const getImageError = () => {
    if (!form.errors.images) return null;
    if (typeof form.errors.images === 'string') return form.errors.images;
    // If it's an object with index keys
    const firstError = Object.values(form.errors.images)[0];
    return firstError;
};

const submitForm = () => {
    form.post(products.store().url, {
        preserveScroll: true,
        onSuccess: () => {
            router.visit('/products');
        },
    });
};
</script>

<template>
    <Head title="Create Product" />

    <div class="max-w-4xl mx-auto py-8 px-4">
        <div class="mb-6">
            <h1 class="text-2xl font-serif font-semibold">Create New Product</h1>
            <p class="text-sm text-gray-500 mt-1">Add a new product to {{ shop.name }}</p>
        </div>

        <form @submit.prevent="submitForm" class="space-y-6 bg-white p-6 rounded-xl border border-gray-200">
            <!-- Basic Information -->
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <Label for="name" class="required">Product Name</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        placeholder="e.g., Aloe Vera Serum"
                    />
                    <p v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</p>
                </div>

                <div class="space-y-2">
                    <Label for="sku">SKU (Stock Keeping Unit)</Label>
                    <Input
                        id="sku"
                        v-model="form.sku"
                        type="text"
                        placeholder="e.g., ALOE-001"
                    />
                    <p class="text-xs text-gray-400">Unique product identifier. Leave empty to auto-generate.</p>
                    <p v-if="form.errors.sku" class="text-xs text-red-500">{{ form.errors.sku }}</p>
                </div>
            </div>

            <div class="space-y-2">
                <Label for="description">Description</Label>
                <Textarea
                    id="description"
                    v-model="form.description"
                    rows="4"
                    placeholder="Describe your product..."
                />
                <p v-if="form.errors.description" class="text-xs text-red-500">{{ form.errors.description }}</p>
            </div>

            <!-- Pricing & Stock -->
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <Label for="cost_price">Cost Price (KES)</Label>
                    <Input
                        id="cost_price"
                        v-model="form.cost_price"
                        type="number"
                        step="0.01"
                        placeholder="What you paid"
                    />
                    <p v-if="form.errors.cost_price" class="text-xs text-red-500">{{ form.errors.cost_price }}</p>
                </div>

                <div class="space-y-2">
                    <Label for="price" class="required">Selling Price (KES)</Label>
                    <Input
                        id="price"
                        v-model="form.price"
                        type="number"
                        step="0.01"
                        required
                        placeholder="Customer price"
                    />
                    <p v-if="form.errors.price" class="text-xs text-red-500">{{ form.errors.price }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <Label for="stock_qty">Stock Quantity</Label>
                    <Input
                        id="stock_qty"
                        v-model="form.stock_qty"
                        type="number"
                        placeholder="0"
                    />
                    <p v-if="form.errors.stock_qty" class="text-xs text-red-500">{{ form.errors.stock_qty }}</p>
                </div>

                <div class="space-y-2">
                    <Label for="low_stock_threshold">Low Stock Alert</Label>
                    <Input
                        id="low_stock_threshold"
                        v-model="form.low_stock_threshold"
                        type="number"
                        placeholder="5"
                    />
                    <p class="text-xs text-gray-400">Notify when stock falls below this number</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <Label for="barcode">Barcode</Label>
                    <Input
                        id="barcode"
                        v-model="form.barcode"
                        type="text"
                        placeholder="Scanning code"
                    />
                    <p v-if="form.errors.barcode" class="text-xs text-red-500">{{ form.errors.barcode }}</p>
                </div>

                <div class="space-y-2">
                    <Label for="product_category_id">Category</Label>
                    <select
                        id="product_category_id"
                        v-model="form.product_category_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900"
                    >
                        <option :value="null">Select a category</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">
                            {{ category.name }}
                        </option>
                    </select>
                    <p v-if="form.errors.product_category_id" class="text-xs text-red-500">{{ form.errors.product_category_id }}</p>
                </div>
            </div>

            <!-- Weight & Dimensions -->
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <Label for="weight">Weight</Label>
                    <Input
                        id="weight"
                        v-model="form.weight"
                        type="number"
                        step="0.01"
                        placeholder="0.00"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="weight_units">Weight Unit</Label>
                    <select
                        id="weight_units"
                        v-model="form.weight_units"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900"
                    >
                        <option value="kg">Kilograms (kg)</option>
                        <option value="g">Grams (g)</option>
                        <option value="lb">Pounds (lb)</option>
                        <option value="oz">Ounces (oz)</option>
                    </select>
                </div>
            </div>

            <!-- Product Images -->
            <div class="space-y-2">
                <Label>Product Images</Label>
                <div class="grid grid-cols-4 gap-4">
                    <div v-for="(preview, index) in imagePreviews" :key="index" class="relative">
                        <div class="h-24 rounded-lg border-2 border-gray-200 overflow-hidden">
                            <img :src="preview" class="w-full h-full object-cover" />
                        </div>
                        <button
                            type="button"
                            @click="removeImage(index)"
                            class="absolute -top-2 -right-2 p-1 bg-red-500 text-white rounded-full hover:bg-red-600"
                        >
                            <X class="w-3 h-3" />
                        </button>
                    </div>
                    <label class="cursor-pointer">
                        <div class="h-24 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center hover:border-gray-400 transition-colors">
                            <ImagePlus class="w-8 h-8 text-gray-400" />
                        </div>
                        <input type="file" accept="image/*" multiple class="hidden" @change="handleImageChange" />
                    </label>
                </div>
                <p class="text-xs text-gray-400">Upload up to 5 product images</p>
                <div v-if="form.errors.images" class="text-red-500 text-xs mt-1">
                    <div v-if="typeof form.errors.images === 'string'">
                        {{ form.errors.images }}
                    </div>
                    <div v-else>
                        <div v-for="(error, idx) in form.errors.images" :key="idx">
                            Image {{ Number(idx) + 1 }}: {{ error }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Switches -->
            <div class="flex gap-6">
                <div class="flex items-center gap-2">
                    <input 
                        type="checkbox" 
                        id="is_active" 
                        v-model="form.is_active"
                        class="w-4 h-4 rounded border-gray-300 focus:ring-2 focus:ring-gray-900"
                    />
                    <Label for="is_active">Active</Label>
                </div>
                <div class="flex items-center gap-2">
                    <input 
                        type="checkbox" 
                        id="is_featured" 
                        v-model="form.is_featured"
                        class="w-4 h-4 rounded border-gray-300 focus:ring-2 focus:ring-gray-900"
                    />
                    <Label for="is_featured">Featured Product</Label>
                </div>
            </div>

            <!-- SEO -->
            <div class="border-t pt-4">
                <h3 class="font-medium mb-4">SEO Information</h3>
                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="meta_title">Meta Title</Label>
                        <Input
                            id="meta_title"
                            v-model="form.meta_title"
                            type="text"
                            maxlength="60"
                            placeholder="SEO title (60 chars max)"
                        />
                        <p class="text-xs text-gray-400">{{ form.meta_title?.length || 0 }}/60 characters</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="meta_description">Meta Description</Label>
                        <Textarea
                            id="meta_description"
                            v-model="form.meta_description"
                            rows="2"
                            maxlength="160"
                            placeholder="SEO description (160 chars max)"
                        />
                        <p class="text-xs text-gray-400">{{ form.meta_description?.length || 0 }}/160 characters</p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 pt-4">
                <Button type="button" variant="outline" @click="router.visit('/products')">
                    Cancel
                </Button>
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Creating...' : 'Create Product' }}
                </Button>
            </div>
        </form>
    </div>
</template>