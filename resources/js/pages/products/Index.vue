<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ShoppingBag, RefreshCw, CheckCircle2, AlertCircle, Info, Package } from '@lucide/vue';
import ProductController from '@/actions/App/Http/Controllers/ProductController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type ProductRecord = {
    id: number;
    shopify_product_id: string | null;
    shopify_variant_id: string | null;
    status: 'pending' | 'processing' | 'successful' | 'failed';
    handle: string;
    title: string;
    vendor: string;
    product_type: string;
    variant_sku: string | null;
    variant_price: number;
    variant_inventory_qty: number;
    image: string | null;
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
    products: PaginatedData<ProductRecord>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Products',
                href: ProductController.index.url(),
            },
        ],
    },
});

const getNumericId = (gid: string | null): string => {
    if (!gid) return '';
    const parts = gid.split('/');
    return parts[parts.length - 1] || '';
};
</script>

<template>
    <Head title="Products" />

    <div class="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                title="Synced Products"
                description="List of imported products synchronized with your Shopify store."
            />
            <div class="flex items-center gap-2">
                <Button as-child>
                    <Link :href="ProductController.index.url()" class="flex items-center gap-2">
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
                            <th class="p-4 w-20">Image</th>
                            <th class="p-4">Product details</th>
                            <th class="p-4 w-32">Status</th>
                            <th class="p-4 w-44">Vendor & Type</th>
                            <th class="p-4">SKU / Inventory</th>
                            <th class="p-4">Price</th>
                            <th class="p-4 w-44">Shopify IDs</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y text-sm">
                        <tr v-if="products.data.length === 0">
                            <td colspan="7" class="p-8 text-center text-muted-foreground">
                                <div class="flex flex-col items-center justify-center space-y-3">
                                    <ShoppingBag class="h-8 w-8 text-muted-foreground/60" />
                                    <p>No products synced to the local database yet.</p>
                                </div>
                            </td>
                        </tr>
                        <tr v-for="product in products.data" :key="product.id" class="hover:bg-muted/30 transition-colors">
                            <td class="p-4">
                                <div class="h-10 w-10 rounded-lg overflow-hidden border bg-muted flex items-center justify-center">
                                    <img v-if="product.image" :src="product.image" :alt="product.title" class="h-full w-full object-cover" />
                                    <Package v-else class="h-5 w-5 text-muted-foreground/60" />
                                </div>
                            </td>
                            <td class="p-4">
                                <div class="flex flex-col">
                                    <span class="font-semibold text-foreground max-w-xs truncate" :title="product.title">
                                        {{ product.title }}
                                    </span>
                                    <span class="text-xs text-muted-foreground font-mono truncate max-w-xs">
                                        {{ product.handle }}
                                    </span>
                                </div>
                            </td>
                            <td class="p-4">
                                <Badge
                                    v-if="product.status === 'successful'"
                                    class="bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/20 font-semibold"
                                    variant="outline"
                                >
                                    <CheckCircle2 class="h-3 w-3 mr-1" />
                                    Successful
                                </Badge>
                                <Badge
                                    v-else-if="product.status === 'failed'"
                                    class="bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-500/20 font-semibold"
                                    variant="outline"
                                >
                                    <AlertCircle class="h-3 w-3 mr-1" />
                                    Failed
                                </Badge>
                                <Badge
                                    v-else-if="product.status === 'processing'"
                                    class="bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-500/20 animate-pulse font-semibold"
                                    variant="outline"
                                >
                                    <RefreshCw class="h-3 w-3 mr-1 animate-spin" />
                                    Processing
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
                            <td class="p-4 text-muted-foreground">
                                <div class="flex flex-col text-xs space-y-0.5">
                                    <span>Vendor: <strong class="text-foreground/90">{{ product.vendor }}</strong></span>
                                    <span>Type: <strong class="text-foreground/90">{{ product.product_type }}</strong></span>
                                </div>
                            </td>
                            <td class="p-4 text-xs">
                                <div class="flex flex-col space-y-0.5 font-mono">
                                    <span>SKU: {{ product.variant_sku || '-' }}</span>
                                    <span>Qty: {{ product.variant_inventory_qty }}</span>
                                </div>
                            </td>
                            <td class="p-4 font-semibold">
                                {{ product.variant_price ? `$${parseFloat(product.variant_price.toString()).toFixed(2)}` : '-' }}
                            </td>
                            <td class="p-4">
                                <div class="flex flex-col text-[11px] font-mono text-muted-foreground space-y-0.5">
                                    <span v-if="product.shopify_product_id" class="flex items-center gap-1">
                                        Product: {{ getNumericId(product.shopify_product_id) }}
                                    </span>
                                    <span v-if="product.shopify_variant_id" class="flex items-center gap-1">
                                        Variant: {{ getNumericId(product.shopify_variant_id) }}
                                    </span>
                                    <span v-if="!product.shopify_product_id && !product.shopify_variant_id">-</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination block -->
            <div v-if="products.total > 0" class="flex flex-col items-center justify-between gap-4 border-t p-4 sm:flex-row">
                <span class="text-xs text-muted-foreground sm:text-sm">
                    Showing <span class="font-medium">{{ products.from }}</span> to <span class="font-medium">{{ products.to }}</span> of <span class="font-medium">{{ products.total }}</span> entries
                </span>
                <nav class="flex items-center justify-center space-x-1" aria-label="Pagination">
                    <Link
                        v-for="(link, index) in products.links"
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
