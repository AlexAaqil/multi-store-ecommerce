<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { ImagePlus, X, Loader2 } from 'lucide-vue-next';
import { ref } from 'vue';
import products from '@/routes/products';
import FormError from '@/components/custom/FormError.vue';

interface Category {
    id: number;
    name: string;
}

interface ProductImage {
    id: number;
    name: string;
    sort_order: number;
    full_url: string;
}

interface Product {
    id: number;
    name: string;
    description: string | null;
    cost_price: number | null;
    price: number;
    stock_qty: number;
    low_stock_threshold: number;
    barcode: string | null;
    sku: string;
    weight: number | null;
    weight_units: string | null;
    is_featured: boolean;
    is_active: boolean;
    product_category_id: number | null;
    attributes: object | null;
    meta_title: string | null;
    meta_description: string | null;
    images: ProductImage[];
    category?: { id: number; name: string } | null;
}

interface Shop {
    id: number;
    name: string;
}

const props = defineProps<{
    product: Product;
    categories: Category[];
    shop: Shop;
}>();

interface ProductForm {
    name: string;
    description: string;
    cost_price: string;
    price: string;
    stock_qty: number;
    low_stock_threshold: number;
    barcode: string;
    sku: string;
    weight: string;
    weight_units: string;
    is_featured: boolean;
    is_active: boolean;
    product_category_id: number | null;
    attributes: object | null;
    meta_title: string;
    meta_description: string;
    images: File[];
    images_to_delete?: number[];
    _method: string;
}

const form = useForm<ProductForm>({
    name: props.product.name,
    description: props.product.description || '',
    cost_price: props.product.cost_price?.toString() || '',
    price: props.product.price.toString(),
    stock_qty: props.product.stock_qty,
    low_stock_threshold: props.product.low_stock_threshold,
    barcode: props.product.barcode || '',
    sku: props.product.sku,
    weight: props.product.weight?.toString() || '',
    weight_units: props.product.weight_units || 'kg',
    is_featured: !!props.product.is_featured,
    is_active: !!props.product.is_active,
    product_category_id: props.product.product_category_id,
    attributes: props.product.attributes,
    meta_title: props.product.meta_title || '',
    meta_description: props.product.meta_description || '',
    images: [],
    _method: 'PUT',
});

const existingImages = ref(props.product.images || []);
const newImagePreviews = ref<string[]>([]);

// Handle new image selection
const handleImageChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    const files = target.files;
    if (files) {
        const newFiles = Array.from(files);
        form.images.push(...newFiles);
        newFiles.forEach(file => {
            const reader = new FileReader();
            reader.onload = (e) => {
                newImagePreviews.value.push(e.target?.result as string);
            };
            reader.readAsDataURL(file);
        });
    }
};

const deletingImageIds = ref<Set<number>>(new Set());

const deleteExistingImage = (imageId: number, index: number) => {
    deletingImageIds.value.add(imageId);
    
    router.delete(`/product-images/${imageId}`, {
        preserveScroll: true,
        onSuccess: () => {
            existingImages.value.splice(index, 1);
        },
        onFinish: () => {
            deletingImageIds.value.delete(imageId);
        }
    });
};

// Remove new image
const removeNewImage = (index: number) => {
    form.images.splice(index, 1);
    newImagePreviews.value.splice(index, 1);
};

const submitForm = () => {
    form.post(products.update(props.product.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            router.visit('/products');
        },
    });
};
</script>

<template>
    <Head title="Edit Product" />

    <div class="max-w-4xl mx-auto py-8 px-4">
        <div class="mb-6">
            <h1 class="text-2xl font-serif font-semibold">Edit Product</h1>
            <p class="text-sm text-gray-500 mt-1">Update product details for {{ shop.name }}</p>
        </div>

        <form @submit.prevent="submitForm">
            <div class="inputs-group-wrapper">
                <div class="inputs-group">
                    <Label for="name" class="required">Product Name</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        placeholder="e.g., Aloe Vera Serum"
                    />
                    <FormError :error="form.errors.name" />
                </div>

                <div class="inputs-group">
                    <Label for="sku">SKU</Label>
                    <Input
                        id="sku"
                        v-model="form.sku"
                        type="text"
                        class="bg-gray-50"
                    />
                    <FormError :error="form.errors.sku" />
                </div>
            </div>

            <div class="inputs-group">
                <Label for="description">Description</Label>
                <Textarea
                    id="description"
                    v-model="form.description"
                    rows="4"
                    placeholder="Describe your product..."
                />
                <FormError :error="form.errors.description" />
            </div>

            <div class="inputs-group-wrapper">
                <div class="inputs-group">
                    <Label for="cost_price">Cost Price (KES)</Label>
                    <Input
                        id="cost_price"
                        v-model="form.cost_price"
                        type="number"
                        step="0.01"
                        placeholder="What you paid"
                    />
                    <FormError :error="form.errors.cost_price" />
                </div>

                <div class="inputs-group">
                    <Label for="price" class="required">Selling Price (KES)</Label>
                    <Input
                        id="price"
                        v-model="form.price"
                        type="number"
                        step="0.01"
                        required
                        placeholder="Customer price"
                    />
                    <FormError :error="form.errors.price" />
                </div>
            </div>

            <div class="inputs-group-wrapper">
                <div class="inputs-group">
                    <Label for="stock_qty">Stock Quantity</Label>
                    <Input
                        id="stock_qty"
                        v-model="form.stock_qty"
                        type="number"
                        placeholder="0"
                    />
                    <FormError :error="form.errors.stock_qty" />
                </div>

                <div class="inputs-group">
                    <Label for="low_stock_threshold">Low Stock Alert</Label>
                    <Input
                        id="low_stock_threshold"
                        v-model="form.low_stock_threshold"
                        type="number"
                        placeholder="5"
                    />
                </div>
            </div>

            <div class="inputs-group-wrapper">
                <div class="inputs-group">
                    <Label for="barcode">Barcode</Label>
                    <Input
                        id="barcode"
                        v-model="form.barcode"
                        type="text"
                        placeholder="Scanning code"
                    />
                    <FormError :error="form.errors.barcode" />
                </div>

                <div class="inputs-group">
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
                    <FormError :error="form.errors.product_category_id" />
                </div>
            </div>

            <!-- Product Images -->
            <div class="inputs-group">
                <Label>Product Images</Label>
                <div class="grid grid-cols-4 gap-4">
                    <!-- Existing Images -->
                    <div v-for="(image, index) in existingImages" :key="image.id" class="relative">
                        <div class="h-24 rounded-lg border-2 border-gray-200 overflow-hidden">
                            <img :src="image.full_url" class="w-full h-full object-cover" />
                        </div>
                        <button
                            type="button"
                            @click="deleteExistingImage(image.id, index)"
                            :disabled="deletingImageIds.has(image.id)"
                            class="absolute -top-2 -right-2 p-1 rounded-full transition-colors"
                            :class="deletingImageIds.has(image.id) 
                                ? 'bg-gray-400 cursor-not-allowed' 
                                : 'bg-red-500 hover:bg-red-600'"
                        >
                            <Loader2 v-if="deletingImageIds.has(image.id)" class="w-3 h-3 text-white animate-spin" />
                            <X v-else class="w-3 h-3 text-white" />
                        </button>
                    </div>

                    <!-- New Images Preview -->
                    <div v-for="(preview, index) in newImagePreviews" :key="'new-' + index" class="relative">
                        <div class="h-24 rounded-lg border-2 border-gray-200 overflow-hidden">
                            <img :src="preview" class="w-full h-full object-cover" />
                        </div>
                        <button
                            type="button"
                            @click="removeNewImage(index)"
                            class="absolute -top-2 -right-2 p-1 bg-red-500 text-white rounded-full hover:bg-red-600"
                        >
                            <X class="w-3 h-3" />
                        </button>
                    </div>

                    <!-- Add Image Button -->
                    <label class="cursor-pointer">
                        <div class="h-24 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center hover:border-gray-400 transition-colors">
                            <ImagePlus class="w-8 h-8 text-gray-400" />
                        </div>
                        <input type="file" accept="image/*" multiple class="hidden" @change="handleImageChange" />
                    </label>
                </div>
                <p class="text-xs text-gray-400">Upload up to 5 product images. Click X to delete existing images.</p>
                <FormError :error="form.errors.images" />
            </div>

            <!-- Status Checkboxes (replaced Switch) -->
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
                    <div class="inputs-group">
                        <Label for="meta_title">Meta Title</Label>
                        <Input
                            id="meta_title"
                            v-model="form.meta_title"
                            type="text"
                            maxlength="60"
                            placeholder="SEO title (60 chars max)"
                        />
                        <p class="text-xs text-gray-400">{{ form.meta_title?.length || 0 }}/60 characters</p>
                        <FormError :error="form.errors.meta_title" />
                    </div>

                    <div class="inputs-group">
                        <Label for="meta_description">Meta Description</Label>
                        <Textarea
                            id="meta_description"
                            v-model="form.meta_description"
                            rows="2"
                            maxlength="160"
                            placeholder="SEO description (160 chars max)"
                        />
                        <p class="text-xs text-gray-400">{{ form.meta_description?.length || 0 }}/160 characters</p>
                        <FormError :error="form.errors.description" />
                    </div>
                </div>
            </div>

            <div class="submit-buttons">
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Saving...' : 'Update Product' }}
                </Button>
                
                <Button type="button" variant="outline" @click="router.visit('/products')">
                    Cancel
                </Button>
            </div>
        </form>
    </div>
</template>