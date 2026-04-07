<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { ImagePlus, X } from 'lucide-vue-next';
import { ref } from 'vue';
import shops from '@/routes/shops';

const form = useForm({
    name: '',
    custom_slug: '',
    description: '',
    category: '',
    contact_email: '',
    contact_phone: '',
    logo: null as File | null,
    cover: null as File | null,
});

// Helper to suggest slug from name
const suggestSlug = () => {
    if (!form.custom_slug && form.name) {
        form.custom_slug = form.name
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, '');
    }
};

const logoPreview = ref<string | null>(null);
const coverPreview = ref<string | null>(null);

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
    if (logoPreview.value) {
        URL.revokeObjectURL(logoPreview.value);
        logoPreview.value = null;
    }
};

// Remove cover
const removeCover = () => {
    form.cover = null;
    if (coverPreview.value) {
        URL.revokeObjectURL(coverPreview.value);
        coverPreview.value = null;
    }
};

const submitForm = () => {
    form.post(shops.store().url, {
        preserveScroll: true,
        onSuccess: () => {
            router.visit('/shops');
        },
    });
};
</script>

<template>
    <Head title="Create Shop" />

    <div class="min-w-4xl mx-auto py-8 px-4">
        <div class="mb-6">
            <h1 class="text-2xl font-serif font-semibold">Create New Shop</h1>
            <p class="text-sm text-gray-500 mt-1">Enter your shop details to start selling</p>
        </div>

        <form @submit.prevent="submitForm" class="space-y-6 bg-white p-6 rounded-xl border border-gray-200">
            <!-- Shop Name -->
            <div class="space-y-2">
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

            <div class="space-y-2">
                <Label for="custom_slug">Custom URL (Optional)</Label>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">/shops/</span>
                    <Input
                        id="custom_slug"
                        v-model="form.custom_slug"
                        placeholder="your-custom-url"
                        class="flex-1"
                    />
                </div>
                <p class="text-xs text-gray-400">
                    Leave empty to auto-generate from shop name. Use only lowercase letters, numbers, and hyphens.
                </p>
                <p class="text-xs text-gray-400">
                    Your shop will be available at: /shops/{{ form.custom_slug || 'your-shop-name' }}
                </p>
                <p v-if="form.errors.custom_slug" class="text-xs text-red-500">{{ form.errors.custom_slug }}</p>
            </div>

            <!-- Category -->
            <div class="space-y-2">
                <Label for="category">Category</Label>
                <Input
                    id="category"
                    v-model="form.category"
                    type="text"
                    placeholder="e.g., Beauty & Wellness"
                />
                <p v-if="form.errors.category" class="text-xs text-red-500">{{ form.errors.category }}</p>
            </div>

            <!-- Description -->
            <div class="space-y-2">
                <Label for="description">Description</Label>
                <Textarea
                    id="description"
                    v-model="form.description"
                    rows="4"
                    placeholder="Describe your shop..."
                />
                <p v-if="form.errors.description" class="text-xs text-red-500">{{ form.errors.description }}</p>
            </div>

            <!-- Contact Email -->
            <div class="space-y-2">
                <Label for="contact_email">Contact Email</Label>
                <Input
                    id="contact_email"
                    v-model="form.contact_email"
                    type="email"
                    placeholder="shop@example.com"
                />
                <p v-if="form.errors.contact_email" class="text-xs text-red-500">{{ form.errors.contact_email }}</p>
            </div>

            <!-- Contact Phone -->
            <div class="space-y-2">
                <Label for="contact_phone">Contact Phone</Label>
                <Input
                    id="contact_phone"
                    v-model="form.contact_phone"
                    type="tel"
                    placeholder="+254 XXX XXX XXX"
                />
                <p v-if="form.errors.contact_phone" class="text-xs text-red-500">{{ form.errors.contact_phone }}</p>
            </div>

            <!-- Logo Upload -->
            <div class="space-y-2">
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
                                Choose Logo
                            </span>
                            <input type="file" accept="image/*" class="hidden" @change="handleLogoChange" />
                        </label>
                        <p class="text-xs text-gray-400 mt-1">PNG, JPG up to 2MB</p>
                    </div>
                </div>
                <p v-if="form.errors.logo" class="text-xs text-red-500">{{ form.errors.logo }}</p>
            </div>

            <!-- Cover Image Upload -->
            <div class="space-y-2">
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

            <!-- Actions -->
            <div class="flex justify-end gap-3 pt-4">
                <Button type="button" variant="outline" @click="router.visit('/shops')">
                    Cancel
                </Button>
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Creating...' : 'Create Shop' }}
                </Button>
            </div>
        </form>
    </div>
</template>