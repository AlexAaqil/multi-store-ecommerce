<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import shopCategories from '@/routes/shop-categories';
import FormError from '@/components/custom/FormError.vue';

interface Category {
    id: number;
    name: string;
};

const props = defineProps<{
    category: Category;
}>();

const form = useForm({
    name: props.category.name,
    _method: 'PUT',
});


const submitForm = () => {
    form.post(shopCategories.update(props.category.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            router.visit('/shop-categories');
        },
    });
};
</script>

<template>
    <Head title="Edit Shop Category" />

    <div class="edit_shop_category_form">
        <form @submit.prevent="submitForm">
            <h2>Edit Shop Category</h2>

            <div class="inputs-group">
                <Label for="name" class="required">Shop Category Name</Label>
                <Input
                    id="name"
                    v-model="form.name"
                    type="text"
                    required
                    placeholder="e.g. Fashion"
                />
                <FormError :error="form.errors.name" />
            </div>

            <div class="submit-buttons">
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Updating...' : 'Update Category' }}
                </Button>

                <Button type="button" variant="outline" @click="router.visit('/shop-categories')">
                    Cancel
                </Button>
            </div>
        </form>
    </div>
</template>