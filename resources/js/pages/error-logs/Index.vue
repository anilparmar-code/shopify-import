<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { AlertCircle, AlertTriangle, Info, Terminal, ChevronDown, ChevronUp, RefreshCw, ArrowLeft } from '@lucide/vue';
import ErrorLogController from '@/actions/App/Http/Controllers/ErrorLogController';
import UploadController from '@/actions/App/Http/Controllers/UploadController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type ErrorLogRecord = {
    id: number;
    level: 'info' | 'warning' | 'error';
    event: string;
    message: string;
    context: any;
    loggable_type: string | null;
    loggable_id: number | null;
    created_at: string;
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
    logs: PaginatedData<ErrorLogRecord>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Error Logs',
                href: ErrorLogController.index.url(),
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
    <Head title="System Logs" />

    <div class="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                title="System Logs"
                description="Review activities, validation errors, and Shopify sync logs."
            />
            <div class="flex items-center gap-2">
                <Button as-child variant="outline">
                    <Link :href="UploadController.index.url()" class="flex items-center gap-2">
                        <ArrowLeft class="h-4 w-4" />
                        <span>Back to Uploads</span>
                    </Link>
                </Button>
                <Button as-child>
                    <Link :href="ErrorLogController.index.url()" class="flex items-center gap-2">
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
                            <th class="p-4 w-20">ID</th>
                            <th class="p-4 w-32">Level</th>
                            <th class="p-4 w-44">Event</th>
                            <th class="p-4">Message</th>
                            <th class="p-4 w-40">Created At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y text-sm">
                        <tr v-if="logs.data.length === 0">
                            <td colspan="6" class="p-8 text-center text-muted-foreground">
                                <div class="flex flex-col items-center justify-center space-y-3">
                                    <Terminal class="h-8 w-8 text-muted-foreground/60" />
                                    <p>No system logs found.</p>
                                </div>
                            </td>
                        </tr>
                        <template v-for="log in logs.data" :key="log.id">
                            <!-- Main Row -->
                            <tr
                                @click="toggleRow(log.id)"
                                class="hover:bg-muted/40 transition-colors cursor-pointer"
                                :class="{ 'bg-muted/20': expandedRows[log.id] }"
                            >
                                <td class="p-4 text-center">
                                    <ChevronDown v-if="!expandedRows[log.id]" class="h-4 w-4 text-muted-foreground transition-transform" />
                                    <ChevronUp v-else class="h-4 w-4 text-muted-foreground transition-transform" />
                                </td>
                                <td class="p-4 font-mono text-xs text-muted-foreground">#{{ log.id }}</td>
                                <td class="p-4">
                                    <Badge
                                        v-if="log.level === 'error'"
                                        class="bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-500/20 font-semibold"
                                        variant="outline"
                                    >
                                        <AlertCircle class="h-3 w-3 mr-1" />
                                        Error
                                    </Badge>
                                    <Badge
                                        v-else-if="log.level === 'warning'"
                                        class="bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/20 font-semibold"
                                        variant="outline"
                                    >
                                        <AlertTriangle class="h-3 w-3 mr-1" />
                                        Warning
                                    </Badge>
                                    <Badge
                                        v-else
                                        class="bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-500/20 font-semibold"
                                        variant="outline"
                                    >
                                        <Info class="h-3 w-3 mr-1" />
                                        Info
                                    </Badge>
                                </td>
                                <td class="p-4">
                                    <code class="px-2 py-0.5 rounded bg-muted font-mono text-xs text-foreground/90 border border-border/50">
                                        {{ log.event }}
                                    </code>
                                </td>
                                <td class="p-4 font-medium max-w-md truncate" :title="log.message">
                                    {{ log.message }}
                                </td>
                                <td class="p-4 text-muted-foreground text-xs font-mono">
                                    {{ log.created_at }}
                                </td>
                            </tr>

                            <!-- Context / Details Row -->
                            <tr v-if="expandedRows[log.id]">
                                <td colspan="6" class="p-4 bg-muted/20 border-t border-b border-border/30">
                                    <div class="space-y-3 px-4 py-2">
                                        <div class="flex items-center gap-4 text-xs text-muted-foreground">
                                            <span v-if="log.loggable_type">
                                                <strong>Loggable:</strong> {{ log.loggable_type }} (#{{ log.loggable_id }})
                                            </span>
                                        </div>
                                        <div class="space-y-1">
                                            <span class="text-xs font-semibold text-muted-foreground flex items-center gap-1">
                                                <Terminal class="h-3.5 w-3.5" />
                                                <span>Context Data</span>
                                            </span>
                                            <pre class="p-4 rounded-lg bg-zinc-950 text-zinc-100 font-mono text-xs overflow-x-auto border border-zinc-800 shadow-inner max-h-96 leading-relaxed">{{ JSON.stringify(log.context, null, 2) }}</pre>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Pagination block -->
            <div v-if="logs.total > 0" class="flex flex-col items-center justify-between gap-4 border-t p-4 sm:flex-row">
                <span class="text-xs text-muted-foreground sm:text-sm">
                    Showing <span class="font-medium">{{ logs.from }}</span> to <span class="font-medium">{{ logs.to }}</span> of <span class="font-medium">{{ logs.total }}</span> entries
                </span>
                <nav class="flex items-center justify-center space-x-1" aria-label="Pagination">
                    <Link
                        v-for="(link, index) in logs.links"
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
