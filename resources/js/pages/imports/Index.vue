<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus, RefreshCw, Upload, CheckCircle, AlertTriangle, Loader } from '@lucide/vue';
import UploadController from '@/actions/App/Http/Controllers/UploadController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type UploadRecord = {
    id: number;
    file_name: string;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    total_records: number;
    processed_records: number;
    success_records: number;
    failed_records: number;
    created_at: string;
    updated_at: string;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedData<T> = {
    data: T[];
    links: PaginationLink[];
    total: number;
    from: number;
    to: number;
    current_page: number;
};

defineProps<{
    uploads: PaginatedData<UploadRecord>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Uploads',
                href: UploadController.index.url(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Uploads" />

    <div class="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                title="Uploads"
                description="Monitor progress, statistics, and status of your Shopify CSV uploads."
            />
            <div class="space-x-2">
                <Button as-child>
                    <Link :href="UploadController.index.url()" class="flex items-center gap-2">
                        <RefreshCw class="h-4 w-4" />
                        <span>Reload</span>
                    </Link>
                </Button>
                <Button as-child>
                    <Link :href="UploadController.create.url()" class="flex items-center gap-2">
                        <Plus class="h-4 w-4" />
                        <span>Upload CSV</span>
                    </Link>
                </Button>
            </div>
        </div>

        <div class="rounded-xl border bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b bg-muted/40 text-xs font-medium text-muted-foreground uppercase tracking-wider">
                            <th class="p-4">ID</th>
                            <th class="p-4">File Name</th>
                            <th class="p-4">Status</th>
                            <th class="p-4 text-center">Progress (Rows)</th>
                            <th class="p-4 text-center">Success / Failed</th>
                            <th class="p-4">Uploaded At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y text-sm">
                        <tr v-if="uploads.data.length === 0">
                            <td colspan="6" class="p-8 text-center text-muted-foreground">
                                <div class="flex flex-col items-center justify-center space-y-3">
                                    <Upload class="h-8 w-8 text-muted-foreground/60" />
                                    <p>No CSV uploads found. Start by uploading your first CSV file!</p>
                                    <Button as-child variant="outline" size="sm">
                                        <Link :href="UploadController.create.url()">
                                            Upload Now
                                        </Link>
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-for="upload in uploads.data" :key="upload.id" class="hover:bg-muted/30 transition-colors">
                            <td class="p-4 font-medium">#{{ upload.id }}</td>
                            <td class="p-4 max-w-[240px] truncate underline" :title="upload.file_name">
                                <Link
                                    :href="UploadController.importRecords.url({ upload: upload.id })"
                                    class="text-primary hover:underline font-medium"
                                >
                                    {{ upload.file_name }}
                                </Link>
                            </td>
                            <td class="p-4">
                                <Badge
                                    v-if="upload.status === 'completed'"
                                    class="bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/20"
                                    variant="outline"
                                >
                                    <CheckCircle class="h-3 w-3 mr-1" />
                                    Completed
                                </Badge>
                                <Badge
                                    v-else-if="upload.status === 'processing'"
                                    class="bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-500/20 animate-pulse"
                                    variant="outline"
                                >
                                    <Loader class="h-3 w-3 mr-1 animate-spin" />
                                    Processing
                                </Badge>
                                <Badge
                                    v-else-if="upload.status === 'failed'"
                                    class="bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-500/20"
                                    variant="outline"
                                >
                                    <AlertTriangle class="h-3 w-3 mr-1" />
                                    Failed
                                </Badge>
                                <Badge
                                    v-else
                                    class="bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/20"
                                    variant="outline"
                                >
                                    <RefreshCw class="h-3 w-3 mr-1 animate-spin" />
                                    Pending
                                </Badge>
                            </td>
                            <td class="p-4 text-center">
                                <div class="flex flex-col items-center justify-center space-y-1">
                                    <span class="font-semibold">{{ upload.processed_records }} / {{ upload.total_records }}</span>
                                    <span class="text-xs text-muted-foreground" v-if="upload.total_records > 0">
                                        {{ Math.round((upload.processed_records / upload.total_records) * 100) }}% complete
                                    </span>
                                    <span class="text-xs text-muted-foreground" v-else>
                                        Pending parse...
                                    </span>
                                </div>
                            </td>
                            <td class="p-4 text-center">
                                <div class="flex items-center justify-center space-x-2">
                                    <span class="text-emerald-600 dark:text-emerald-400 font-medium" title="Success count">
                                        ✓ {{ upload.success_records }}
                                    </span>
                                    <span class="text-muted-foreground">/</span>
                                    <span class="text-rose-600 dark:text-rose-400 font-medium" title="Failed count">
                                        ✗ {{ upload.failed_records }}
                                    </span>
                                </div>
                            </td>
                            <td class="p-4 text-muted-foreground">
                                {{ upload.created_at }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination block -->
            <div v-if="uploads.total > 0" class="flex flex-col items-center justify-between gap-4 border-t p-4 sm:flex-row">
                <span class="text-xs text-muted-foreground sm:text-sm">
                    Showing <span class="font-medium">{{ uploads.from }}</span> to <span class="font-medium">{{ uploads.to }}</span> of <span class="font-medium">{{ uploads.total }}</span> entries
                </span>
                <nav class="flex items-center justify-center space-x-1" aria-label="Pagination">
                    <Link
                        v-for="(link, index) in uploads.links"
                        :key="index"
                        :href="link.url ?? '#'"
                        :class="[
                            'inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors h-9 px-3',
                            link.active ? 'bg-primary text-primary-foreground focus-visible:ring-1 focus-visible:ring-ring' : 'hover:bg-muted border border-transparent',
                            !link.url ? 'pointer-events-none opacity-40' : ''
                        ]"

                    >
                        <span v-html="link.label"></span>
                    </Link>
                </nav>
            </div>
        </div>
    </div>
</template>
