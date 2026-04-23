<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import { Camera, X } from 'lucide-vue-next';

type Props = {
    mustVerifyEmail: boolean;
    status?: string;
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profile settings',
                href: edit(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const imagePreview = ref<string | null>(user.value.image ? `/storage/users/${user.value.image}` : null);
const isUploading = ref(false);

const handleImageUpload = async (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    
    if (!file) return;
    
    // Validate file type
    if (!file.type.startsWith('image/')) {
        alert('Please upload an image file');
        return;
    }
    
    // Validate file size (max 2MB)
    if (file.size > 2 * 1024 * 1024) {
        alert('Image size must be less than 2MB');
        return;
    }
    
    // Create preview
    const reader = new FileReader();
    reader.onload = (e) => {
        imagePreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
    
    // Upload the file
    isUploading.value = true;
    const formData = new FormData();
    formData.append('image', file);
    
    try {
        const response = await fetch('/settings/profile/image', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData
        });
        
        if (!response.ok) {
            throw new Error('Upload failed');
        }
        
        const data = await response.json();
        // Update the user data in the page props
        page.props.auth.user.image = data.image;
        
        // Show success message (you can add a toast notification here)
        console.log('Image updated successfully');
    } catch (error) {
        console.error('Upload error:', error);
        alert('Failed to upload image. Please try again.');
        // Reset preview on error
        imagePreview.value = user.value.image ? `/storage/users/${user.value.image}` : null;
    } finally {
        isUploading.value = false;
        // Clear the input
        input.value = '';
    }
};

const removeImage = async () => {
    if (!confirm('Remove your profile picture?')) return;
    
    isUploading.value = true;
    
    try {
        const response = await fetch('/settings/profile/image', {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error('Remove failed');
        }
        
        const data = await response.json();
        page.props.auth.user.image = null;
        imagePreview.value = null;
        
        console.log('Image removed successfully');
    } catch (error) {
        console.error('Remove error:', error);
        alert('Failed to remove image. Please try again.');
    } finally {
        isUploading.value = false;
    }
};
</script>

<template>
    <Head title="Profile settings" />

    <h1 class="sr-only">Profile settings</h1>

    <div class="flex flex-col space-y-6">
        <!-- Profile Image Section -->
        <div class="border-b pb-6">
            <Heading
                variant="small"
                title="Profile picture"
                description="Update your profile image"
            />
            
            <div class="mt-4 flex items-center gap-6">
                <!-- Image Preview -->
                <div class="relative">
                    <div class="w-24 h-24 rounded-full bg-gray-100 flex items-center justify-center overflow-hidden">
                        <img 
                            v-if="imagePreview" 
                            :src="imagePreview" 
                            :alt="user.name"
                            class="w-full h-full object-cover"
                        />
                        <div v-else class="text-3xl font-medium text-gray-400">
                            {{ user.name?.charAt(0) || 'U' }}
                        </div>
                    </div>
                    
                    <!-- Upload Button -->
                    <label 
                        class="absolute bottom-0 right-0 p-1.5 bg-white rounded-full shadow-lg cursor-pointer hover:bg-gray-50 transition-colors"
                        :class="{ 'opacity-50 cursor-not-allowed': isUploading }"
                    >
                        <Camera class="w-4 h-4 text-gray-600" />
                        <input 
                            type="file" 
                            accept="image/*"
                            class="hidden"
                            @change="handleImageUpload"
                            :disabled="isUploading"
                        />
                    </label>
                    
                    <!-- Remove Button -->
                    <button 
                        v-if="imagePreview"
                        @click="removeImage"
                        class="absolute -top-2 -right-2 p-1 bg-red-500 rounded-full hover:bg-red-600 transition-colors"
                        :disabled="isUploading"
                    >
                        <X class="w-3 h-3 text-white" />
                    </button>
                </div>
                
                <div class="flex-1">
                    <p class="text-sm text-gray-600">
                        Recommended: Square image, at least 200x200 pixels. Max size 2MB.
                    </p>
                    <p v-if="isUploading" class="text-xs text-blue-600 mt-1">
                        Uploading...
                    </p>
                </div>
            </div>
        </div>

        <Heading
            variant="small"
            title="Profile information"
            description="Update your name and email address"
        />

        <Form
            v-bind="ProfileController.update.form()"
            class="space-y-6"
            v-slot="{ errors, processing, recentlySuccessful }"
        >
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    class="mt-1 block w-full"
                    name="name"
                    :default-value="user.name"
                    required
                    autocomplete="name"
                    placeholder="Full name"
                />
                <InputError class="mt-2" :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    name="email"
                    :default-value="user.email"
                    required
                    autocomplete="username"
                    placeholder="Email address"
                />
                <InputError class="mt-2" :message="errors.email" />
            </div>

            <div v-if="mustVerifyEmail && !user.email_verified_at">
                <p class="-mt-4 text-sm text-muted-foreground">
                    Your email address is unverified.
                    <Link
                        :href="send()"
                        as="button"
                        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                    >
                        Click here to resend the verification email.
                    </Link>
                </p>

                <div
                    v-if="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing" data-test="update-profile-button"
                    >Save</Button
                >

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-show="recentlySuccessful"
                        class="text-sm text-neutral-600"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </Form>
    </div>

    <DeleteUser />
</template>
