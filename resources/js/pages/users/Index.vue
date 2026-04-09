<script setup lang="ts">
import { Head, usePage, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import users from '@/routes/users';
import PageHeader from '@/components/custom/PageHeader.vue';
import DeleteConfirmationDialog from '@/components/custom/DeleteConfirmation.vue';
import Toast from '@/components/custom/ToastNotification/Index.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Users',
                href: users.index().url,
            },
        ],
    },
});

const page = usePage<any>();

interface User {
    id: number;
    name: string;
    email: string;
    role_label: string;
    status: boolean;
    is_active: boolean;
}

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
};

interface PaginationMeta {
    current_page: number;
    from: number;
    last_page: number;
    links: PaginationLink[];
    path: string;
    per_page: number;
    to: number;
    total: number;
}

interface PaginatedUsers {
    data: User[]
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: PaginationMeta;
};

interface RoleCount {
    count: number
    label: string
}

interface Props {
    users: PaginatedUsers;
    role_counts: Record<string, RoleCount>;
    filters: {
        search?: string;
        role?: string;
    };
};

const props = defineProps<Props>();

const search = ref(props.filters.search || '');
const selectedRole = ref(props.filters.role || '');

const totalUsers = computed(() => {
    return Object.values(props.role_counts).reduce((sum, role) => sum + role.count, 0);
});

const roleValues = computed(() => {
    // Convert role_counts object to array for easier rendering
    return Object.entries(props.role_counts).map(([value, role]) => ({
        value: value,
        label: role.label,
        count: role.count
    }));
});

const performSearch = useDebounceFn(() => {
    router.get(users.index().url, {
        search: search.value,
        role: selectedRole.value
    }, {
        preserveState: true,
        replace: true
    });
}, 300);

watch([search, selectedRole], () => {
    performSearch();
});

const filterByRole = (role: string | number) => {
    const roleString = String(role);
    // If clicking the same role, clear filter, otherwise set to that role
    selectedRole.value = selectedRole.value === roleString ? '' : roleString;
};

const clearFilters = () => {
    search.value = '';
    selectedRole.value = '';
};

// Helper to check if a role is active
const isRoleActive = (roleValue: string) => {
    return selectedRole.value === roleValue;
};
</script>

<template>
    <Head title="Users" />

    <div class="app_container">
        <PageHeader 
            title="Users"
            v-model:search="search"
            :create-href="users.create().url"
            create-button-text="Create User"
            search-placeholder="Search users by name..."
        />

        <!-- Role Statistics with better visual feedback -->
        <div class="role-stats mb-4 py-2 px-4 bg-gray-50 rounded-lg dark:bg-gray-800">
            <div class="flex flex-wrap gap-8 text-sm">
                <!-- All Users Filter -->
                <div 
                    class="stat-item cursor-pointer px-2 py-1 rounded-md transition-all"
                    :class="{
                        'bg-blue-600 text-white': !selectedRole,
                        'hover:bg-gray-200 dark:hover:bg-gray-700': selectedRole
                    }"
                    @click="filterByRole('')">
                    <span class="font-semibold">{{ totalUsers }}</span> Users
                </div>
                
                <!-- Role Filters -->
                <div 
                    v-for="role in roleValues" 
                    :key="role.value"
                    class="stat-item cursor-pointer px-2 py-1 rounded-md transition-all"
                    :class="{
                        'bg-blue-600 text-white': isRoleActive(role.value),
                        'hover:bg-gray-200 dark:hover:bg-gray-700': !isRoleActive(role.value)
                    }"
                    @click="filterByRole(role.value)">
                    <span class="font-semibold">{{ role.count }}</span> {{ role.label }}{{ role.count !== 1 ? 's' : '' }}
                </div>
                
                <!-- Clear Filters Button (visible when filters are active) -->
                <button 
                    v-if="search || selectedRole"
                    @click="clearFilters"
                    class="ml-auto text-sm text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 underline">
                    Clear Filters
                </button>
            </div>
            
            <!-- Active Filter Indicator -->
            <div v-if="selectedRole" class="mt-2 text-xs text-blue-600 dark:text-blue-400">
                <span class="font-medium">Active filter:</span> 
                {{ roleValues.find(r => r.value === selectedRole)?.label || 'Unknown' }} role
            </div>
            <div v-else-if="search" class="mt-2 text-xs text-blue-600 dark:text-blue-400">
                <span class="font-medium">Active filter:</span> 
                Searching for "{{ search }}"
            </div>
        </div>

        <div class="users_table">
            <div class="bg-white dark:bg-gray-900 rounded-lg border shadow-sm overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow class="bg-gray-50 dark:bg-gray-800">
                            <TableHead class="w-[50px]">#</TableHead>
                            <TableHead>Name</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Role</TableHead>
                            <TableHead class="text-center">Actions</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <TableRow v-for="(user, index) in props.users.data" 
                                 :key="user.id"
                                 class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <TableCell class="font-medium">
                                {{ (props.users.meta.current_page - 1) * props.users.meta.per_page + index + 1 }}
                            </TableCell>
                            <TableCell :class="{ 'text-red-500' : !user.is_active, '' : user.is_active }">
                                {{ user.name }}
                            </TableCell>
                            <TableCell>{{ user.email ?? '-' }}</TableCell>
                            <TableCell>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full"
                                      :class="{
                                        'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200': user.role_label === 'Super Admin',
                                        'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200': user.role_label === 'Admin',
                                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': user.role_label === 'Seller',
                                        'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200': user.role_label === 'Customer'
                                      }">
                                    {{ user.role_label }}
                                </span>
                            </TableCell>
                            <TableCell class="text-center">
                                <div class="flex justify-center space-x-2">
                                    <Link :href="users.edit(user.id).url" 
                                          class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 hover:underline">
                                        Edit
                                    </Link>
                                    <span class="text-gray-300 dark:text-gray-600">|</span>
                                    <DeleteConfirmationDialog 
                                        :url="users.destroy(user.id).url" 
                                        title="Delete User?" 
                                        description="This user will be deleted permanently!" 
                                        confirm-text="Delete User">
                                        <template #trigger>
                                            <button class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 hover:underline">
                                                Delete
                                            </button>
                                        </template>
                                    </DeleteConfirmationDialog>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="props.users.data.length === 0">
                            <TableCell colspan="5" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                No users found.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination - Using meta.links -->
            <div v-if="props.users.meta && props.users.meta.links && props.users.meta.links.length > 3" class="mt-6 flex justify-center gap-1">
                <Link v-for="(link, linkIndex) in props.users.meta.links" 
                     :key="linkIndex" 
                     :href="link.url ?? ''" 
                     v-html="link.label" 
                     class="px-3 py-1 border rounded text-sm transition-colors"
                     :class="{
                        'bg-gray-100 text-gray-500 cursor-not-allowed dark:bg-gray-800 dark:text-gray-500': !link.url,
                        'bg-blue-600 text-white border-blue-600': link.active,
                        'hover:bg-gray-50 border-gray-300 dark:hover:bg-gray-800 dark:border-gray-700': link.url && !link.active,
                     }"
                     :disabled="!link.url"
                     preserve-scroll />
            </div>

            <!-- Results summary - Using meta properties -->
            <div v-if="props.users.meta && props.users.meta.total" class="mt-4 text-gray-600 dark:text-gray-400 text-sm flex justify-center items-center gap-4">
                <div>
                    Showing {{ ((props.users.meta.current_page - 1) * props.users.meta.per_page) + 1 }} 
                    to {{ Math.min((props.users.meta.current_page * props.users.meta.per_page), props.users.meta.total) }} 
                    of {{ props.users.meta.total }} users
                </div>
                <div v-if="search || selectedRole" class="text-blue-600 dark:text-blue-400">
                    Filtered results
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.stat-item {
    transition: all 0.2s ease;
}

.stat-item:active {
    transform: scale(0.98);
}

.role-stats {
    border: 1px solid #e5e7eb;
    transition: all 0.2s ease;
}

.dark .role-stats {
    border-color: #374151;
}
</style>