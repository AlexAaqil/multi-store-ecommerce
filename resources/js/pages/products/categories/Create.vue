<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import productCategories from '@/routes/product-categories';
import FormError from '@/components/custom/FormError.vue';

const form = useForm({
    name: '',
    description: ''
});


const submitForm = () => {
    form.post(productCategories.store().url, {
        preserveScroll: true,
        onSuccess: () => {
            router.visit('/product-categories');
        },
    });
};
</script>

<template>
    <Head title="Create Product Category" />

    <div class="create_product_category_form">
        <form @submit.prevent="submitForm">
            <h2>Create Product Category</h2>

            <div class="inputs-group">
                <Label for="name" class="required">Product Category Name</Label>
                <Input
                    id="name"
                    v-model="form.name"
                    type="text"
                    required
                    placeholder="e.g. Fashion"
                />
                <FormError :error="form.errors.name" />
            </div>

            <div class="inputs-group">
                <Label for="description">Description</Label>
                <Textarea
                    id="description"
                    v-model="form.description"
                    rows="4"
                    placeholder="Describe your shop..."
                />
                <FormError :error="form.errors.description" />
            </div>

            <div class="submit-buttons">
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Creating...' : 'Create Category' }}
                </Button>

                <Button type="button" variant="outline" @click="router.visit('/product-categories')">
                    Cancel
                </Button>
            </div>
        </form>
    </div>
</template>