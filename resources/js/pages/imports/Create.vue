<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import UploadController from '@/actions/App/Http/Controllers/UploadController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    maxFileSizeMb: number;
}>();

const clientError = ref<string>('');
const selectedFileName = ref<string>('');

function validateSelectedFile(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    clientError.value = '';
    selectedFileName.value = '';

    if (!file) {
        return;
    }

    const allowedMimeTypes = ['text/csv', 'application/vnd.ms-excel'];
    const hasValidExtension = file.name.toLowerCase().endsWith('.csv');
    const hasValidMimeType = allowedMimeTypes.includes(file.type);

    if (!hasValidExtension && !hasValidMimeType) {
        clientError.value = 'Please upload a valid CSV file.';
        input.value = '';
        return;
    }

    if (file.size > props.maxFileSizeMb * 1024 * 1024) {
        clientError.value = `File must be ${props.maxFileSizeMb}MB or smaller.`;
        input.value = '';
        return;
    }

    selectedFileName.value = file.name;
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Upload CSV',
                href: UploadController.create.url(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Upload CSV" />

    <div class="mx-auto w-full max-w-3xl space-y-6 p-4 md:p-6">
        <Heading
            title="CSV product upload"
            description="Upload a Shopify-compatible CSV file and process it in the background queue."
        />

        <div class="rounded-xl border bg-card p-6 shadow-sm">
            <Form
                v-bind="UploadController.store.form()"
                :options="{ preserveScroll: true }"
                reset-on-success
                class="space-y-5"
                enctype="multipart/form-data"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="csv_file">CSV file</Label>
                    <Input
                        id="csv_file"
                        name="csv_file"
                        type="file"
                        accept=".csv,text/csv"
                        required
                        @change="validateSelectedFile"
                    />
                    <p class="text-xs text-muted-foreground">
                        Maximum file size: {{ maxFileSizeMb }}MB.
                    </p>
                    <p v-if="selectedFileName" class="text-xs text-muted-foreground">
                        Selected: {{ selectedFileName }}
                    </p>
                    <InputError :message="clientError || errors.csv_file" />
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <Button type="submit" :disabled="processing || !!clientError">
                        {{ processing ? 'Uploading...' : 'Upload and start import' }}
                    </Button>
                    <p class="text-sm text-muted-foreground">
                        Processing runs asynchronously after upload.
                    </p>
                </div>
            </Form>
        </div>
    </div>
</template>
