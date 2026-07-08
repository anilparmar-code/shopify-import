<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { AlertCircle, AlertTriangle, Info, Terminal, ChevronDown, ChevronUp, RefreshCw, ArrowLeft, CheckCircle2 } from '@lucide/vue';
import UploadController from '@/actions/App/Http/Controllers/UploadController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type ErrorLog = {
    id: number;
    level: 'info' | 'warning' | 'error';
    event: string;
    message: string;
    context: any;
};

type ImportRecord = {
    id: number;
    data: any;
    status: 'pending' | 'validated' | 'invalid' | 'warning';
    created_at: string;
    error_logs: ErrorLog[];
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
    upload: {
        id: number;
        file_name: string;
        status: string;
    };
    importRecords: PaginatedData<ImportRecord>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Uploads',
                href: UploadController.index.url(),
            },
            {
                title: 'Import Records',
                href: '#',
            },
        ],
    },
});

const expandedRows = ref<Record<number, boolean>>({});

const toggleRow = (id: number) => {
    expandedRows.value[id] = !expandedRows.value[id];
};
</script>

<template>
    <Head title="Import Records" />

    <div class="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                :title="`Upload Records: #${upload.id}`"
                :description="`Viewing rows and logs parsed from file: ${upload.file_name}`"
            />
            <div class="flex items-center gap-2">
                <Button as-child variant="outline">
                    <Link :href="UploadController.index.url()" class="flex items-center gap-2">
                        <ArrowLeft class="h-4 w-4" />
                        <span>Back to Uploads</span>
                    </Link>
                </Button>
                <Button as-child>
                    <Link :href="UploadController.importRecords.url({ upload: upload.id })" class="flex items-center gap-2">
                        <RefreshCw class="h-4 w-4" />
                        <span>Reload</span>
                    </Link>
                </Button>
            </div>
        </div>

        <div class="rounded-xl border bg-card/60 backdrop-blur-sm shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b bg-muted/40 text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                            <th class="p-4 w-12"></th>
                            <th class="p-4 w-20">Row ID</th>
                            <th class="p-4 w-32">Status</th>
                            <th class="p-4 w-64">Product Info</th>
                            <th class="p-4">SKU / Price</th>
                            <th class="p-4 w-44">Logs</th>
                            <th class="p-4 w-40">Processed At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y text-sm">
                        <tr v-if="importRecords.data.length === 0">
                            <td colspan="7" class="p-8 text-center text-muted-foreground">
                                <div class="flex flex-col items-center justify-center space-y-3">
                                    <Terminal class="h-8 w-8 text-muted-foreground/60" />
                                    <p>No import records found for this upload.</p>
                                </div>
                            </td>
                        </tr>
                        <template v-for="record in importRecords.data" :key="record.id">
                            <!-- Main Row -->
                            <tr
                                @click="toggleRow(record.id)"
                                class="hover:bg-muted/40 transition-colors cursor-pointer"
                                :class="{ 'bg-muted/20': expandedRows[record.id] }"
                            >
                                <td class="p-4 text-center">
                                    <ChevronDown v-if="!expandedRows[record.id]" class="h-4 w-4 text-muted-foreground transition-transform" />
                                    <ChevronUp v-else class="h-4 w-4 text-muted-foreground transition-transform" />
                                </td>
                                <td class="p-4 font-mono text-xs text-muted-foreground">#{{ record.id }}</td>
                                <td class="p-4">
                                    <Badge
                                        v-if="record.status === 'validated'"
                                        class="bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/20 font-semibold"
                                        variant="outline"
                                    >
                                        <CheckCircle2 class="h-3 w-3 mr-1" />
                                        Success
                                    </Badge>
                                    <Badge
                                        v-else-if="record.status === 'invalid'"
                                        class="bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-500/20 font-semibold"
                                        variant="outline"
                                    >
                                        <AlertCircle class="h-3 w-3 mr-1" />
                                        Failed
                                    </Badge>
                                    <Badge
                                        v-else
                                        class="bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/20 font-semibold"
                                        variant="outline"
                                    >
                                        <Info class="h-3 w-3 mr-1" />
                                        Pending
                                    </Badge>
                                </td>
                                <td class="p-4">
                                    <div class="flex flex-col">
                                        <span class="font-medium text-foreground max-w-[240px] truncate" :title="record.data.title || record.data.handle">
                                            {{ record.data.title || 'Untitled' }}
                                        </span>
                                        <span class="text-xs text-muted-foreground font-mono truncate max-w-[240px]">
                                            {{ record.data.handle }}
                                        </span>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <div class="flex flex-col">
                                        <span class="font-medium text-foreground">
                                            {{ record.data.variant_price ? `$${parseFloat(record.data.variant_price).toFixed(2)}` : '-' }}
                                        </span>
                                        <span class="text-xs text-muted-foreground font-mono">
                                            SKU: {{ record.data.variant_sku || '-' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <span v-if="record.error_logs.length > 0" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded bg-muted text-foreground font-semibold">
                                            {{ record.error_logs.length }}
                                        </span>
                                        <span>Log(s)</span>
                                    </span>
                                    <span v-else class="text-xs text-muted-foreground/60">-</span>
                                </td>
                                <td class="p-4 text-muted-foreground text-xs font-mono">
                                    {{ record.created_at }}
                                </td>
                            </tr>

                            <!-- Collapsible Row details -->
                            <tr v-if="expandedRows[record.id]">
                                <td colspan="7" class="p-4 bg-muted/20 border-t border-b border-border/30">
                                    <div class="space-y-4 px-4 py-2">
                                        <!-- Error Logs block -->
                                        <div v-if="record.error_logs.length > 0" class="space-y-2">
                                            <span class="text-xs font-semibold text-muted-foreground flex items-center gap-1">
                                                <AlertTriangle class="h-3.5 w-3.5 text-rose-500" />
                                                <span>Import Execution Logs</span>
                                            </span>
                                            <div class="space-y-2">
                                                <div
                                                    v-for="log in record.error_logs"
                                                    :key="log.id"
                                                    class="p-3 rounded-lg border bg-card/80 text-xs shadow-inner flex flex-col space-y-1"
                                                >
                                                    <div class="flex items-center justify-between">
                                                        <Badge
                                                            :class="[
                                                                log.level === 'error' ? 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-500/20' : 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-500/20'
                                                            ]"
                                                            variant="outline"
                                                        >
                                                            {{ log.level.toUpperCase() }} - {{ log.event }}
                                                        </Badge>
                                                    </div>
                                                    <p class="font-medium text-foreground/95 leading-relaxed">{{ log.message }}</p>
                                                    <pre v-if="log.context && Object.keys(log.context).length > 0" class="mt-2 p-2 rounded bg-zinc-950 text-zinc-100 font-mono text-[10px] overflow-x-auto max-h-40 border border-zinc-800">{{ JSON.stringify(log.context, null, 2) }}</pre>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Raw Row Data -->
                                        <div class="space-y-1">
                                            <span class="text-xs font-semibold text-muted-foreground flex items-center gap-1">
                                                <Terminal class="h-3.5 w-3.5" />
                                                <span>Raw CSV Data</span>
                                            </span>
                                            <pre class="p-4 rounded-lg bg-zinc-950 text-zinc-100 font-mono text-xs overflow-x-auto border border-zinc-800 shadow-inner max-h-96 leading-relaxed">{{ JSON.stringify(record.data, null, 2) }}</pre>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Pagination block -->
            <div v-if="importRecords.total > 0" class="flex flex-col items-center justify-between gap-4 border-t p-4 sm:flex-row">
                <span class="text-xs text-muted-foreground sm:text-sm">
                    Showing <span class="font-medium">{{ importRecords.from }}</span> to <span class="font-medium">{{ importRecords.to }}</span> of <span class="font-medium">{{ importRecords.total }}</span> entries
                </span>
                <nav class="flex items-center justify-center space-x-1" aria-label="Pagination">
                    <Link
                        v-for="(link, index) in importRecords.links"
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
