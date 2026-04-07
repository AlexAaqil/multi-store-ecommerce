<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import shops from '@/routes/shops';

const props = defineProps<{
    shopId: number;
    shopName: string;
    open: boolean;
}>();

const emit = defineEmits(['close', 'success']);

const deleteShop = () => {
    router.delete(shops.destroy(props.shopId).url, {
        preserveScroll: true,
        onSuccess: () => {
            emit('success');
            emit('close');
        },
    });
};
</script>

<template>
    <AlertDialog :open="open" @update:open="$emit('close')">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Delete Shop?</AlertDialogTitle>
                <AlertDialogDescription>
                    Are you sure you want to delete "{{ shopName }}"? 
                    This action cannot be undone and will delete all 
                    products and data associated with this shop.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Cancel</AlertDialogCancel>
                <AlertDialogAction
                    @click="deleteShop"
                    class="bg-red-600 hover:bg-red-700"
                >
                    Delete
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>