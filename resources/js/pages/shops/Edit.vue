<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { ImagePlus, X } from 'lucide-vue-next';
import { ref } from 'vue';
import shops from '@/routes/shops';

interface Category {
    id: number;
    name: string;
};

interface Shop {
    id: number;
    name: string;
    description: string | null;
    logo_image: string | null;
    cover_image: string | null;
    contact_email: string | null;
    contact_phone: string | null;
    is_active: boolean;
    category: {
        id: number;
        name: string;
    } | null;
}

const props = defineProps<{
    shop: Shop;
    categories: Category[];
}>();

const form = useForm({
    name: props.shop.name,
    description: props.shop.description || '',
    contact_email: props.shop.contact_email || '',
    contact_phone: props.shop.contact_phone || '',
    logo: null as File | null,
    cover: null as File | null,
    shop_category_id: props.shop.category?.id || null,
    _method: 'PUT',
});

const logoPreview = ref<string | null>(props.shop.logo_image);
const coverPreview = ref<string | null>(props.shop.cover_image);

// Handle logo file selection
const handleLogoChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        form.logo = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

// Handle cover file selection
const handleCoverChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        form.cover = file;
        coverPreview.value = URL.createObjectURL(file);
    }
};

// Remove logo
const removeLogo = () => {
    form.logo = null;
    if (logoPreview.value && !props.shop.logo_image) {
        URL.revokeObjectURL(logoPreview.value);
    }
    logoPreview.value = null;
};

// Remove cover
const removeCover = () => {
    form.cover = null;
    if (coverPreview.value && !props.shop.cover_image) {
        URL.revokeObjectURL(coverPreview.value);
    }
    coverPreview.value = null;
};

const submitForm = () => {
    form.post(shops.update(props.shop.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            router.visit('/shops');
        },
    });
};
</script>

<template>
    <Head title="Edit Shop" />

    <div class="min-w-4xl mx-auto py-8 px-4">
        <div class="mb-6">
            <h1 class="text-2xl font-serif font-semibold">Edit Shop</h1>
            <p class="text-sm text-gray-500 mt-1">Update your shop information</p>
        </div>

        <form @submit.prevent="submitForm">
            <div class="inputs-group-wrapper">
                <div class="inputs-group">
                    <Label for="name">Shop Name *</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        placeholder="e.g., Amani Botanics"
                    />
                    <p v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</p>
                </div>

                <div class="inputs-group">
                    <Label for="shop_category_id">Category</Label>
                    <select
                        id="shop_category_id"
                        v-model="form.shop_category_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                    >
                        <option :value="null">Select a category</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">
                            {{ category.name }}
                        </option>
                    </select>
                    <p v-if="form.errors.shop_category_id" class="text-xs text-red-500">{{ form.errors.shop_category_id }}</p>
                </div>
            </div>

            <div class="inputs-group-wrapper">
                <div class="inputs-group">
                    <Label for="contact_email">Contact Email</Label>
                    <Input
                        id="contact_email"
                        v-model="form.contact_email"
                        type="email"
                        placeholder="shop@example.com"
                    />
                    <p v-if="form.errors.contact_email" class="text-xs text-red-500">{{ form.errors.contact_email }}</p>
                </div>

                <div class="inputs-group">
                    <Label for="contact_phone">Contact Phone</Label>
                    <Input
                        id="contact_phone"
                        v-model="form.contact_phone"
                        type="tel"
                        placeholder="+254 XXX XXX XXX"
                    />
                    <p v-if="form.errors.contact_phone" class="text-xs text-red-500">{{ form.errors.contact_phone }}</p>
                </div>
            </div>
            
            <div class="inputs-group">
                <Label for="description">Description</Label>
                <Textarea
                    id="description"
                    v-model="form.description"
                    rows="4"
                    placeholder="Describe your shop..."
                />
                <p v-if="form.errors.description" class="text-xs text-red-500">{{ form.errors.description }}</p>
            </div>

            <div class="inputs-group-wrapper">
                <div class="inputs-group">
                    <Label>Cover Image</Label>
                    <div class="relative">
                        <div class="h-40 rounded-xl border-2 border-dashed border-gray-300 flex items-center justify-center overflow-hidden bg-gray-50">
                            <img v-if="coverPreview" :src="coverPreview" class="w-full h-full object-cover" />
                            <div v-else class="text-center">
                                <ImagePlus class="w-10 h-10 text-gray-400 mx-auto mb-2" />
                                <p class="text-sm text-gray-500">Click to upload cover image</p>
                            </div>
                        </div>
                        <button
                            v-if="coverPreview"
                            type="button"
                            @click="removeCover"
                            class="absolute -top-2 -right-2 p-1 bg-red-500 text-white rounded-full hover:bg-red-600"
                        >
                            <X class="w-3 h-3" />
                        </button>
                        <label class="absolute inset-0 cursor-pointer">
                            <input type="file" accept="image/*" class="hidden" @change="handleCoverChange" />
                        </label>
                    </div>
                    <p v-if="form.errors.cover" class="text-xs text-red-500">{{ form.errors.cover }}</p>
                </div>

                <div class="inputs-group">
                    <Label>Shop Logo</Label>
                    <div class="flex items-center gap-4">
                        <div class="relative">
                            <div class="w-24 h-24 rounded-xl border-2 border-dashed border-gray-300 flex items-center justify-center overflow-hidden bg-gray-50">
                                <img v-if="logoPreview" :src="logoPreview" class="w-full h-full object-cover" />
                                <ImagePlus v-else class="w-8 h-8 text-gray-400" />
                            </div>
                            <button
                                v-if="logoPreview"
                                type="button"
                                @click="removeLogo"
                                class="absolute -top-2 -right-2 p-1 bg-red-500 text-white rounded-full hover:bg-red-600"
                            >
                                <X class="w-3 h-3" />
                            </button>
                        </div>
                        <div>
                            <label class="cursor-pointer">
                                <span class="text-sm text-gray-600 px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 inline-block">
                                    Change Logo
                                </span>
                                <input type="file" accept="image/*" class="hidden" @change="handleLogoChange" />
                            </label>
                            <p class="text-xs text-gray-400 mt-1">PNG, JPG up to 2MB</p>
                        </div>
                    </div>
                    <p v-if="form.errors.logo" class="text-xs text-red-500">{{ form.errors.logo }}</p>
                </div>

                
            </div>

            <!-- Actions -->
            <div class="flex gap-3 pt-4">
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Saving...' : 'Update Shop' }}
                </Button>
                
                <Button type="button" variant="outline" @click="router.visit('/shops')">
                    Cancel
                </Button>
            </div>
        </form>
    </div>
</template>