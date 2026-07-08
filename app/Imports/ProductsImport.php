<?php

namespace App\Imports;

use App\Enums\Upload\UploadStatusEnum;
use App\Models\ImportRecord;
use App\Models\Product;
use App\Models\Upload;
use App\Notifications\UploadCompletedNotification;
use App\Services\ShopifyService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToCollection, WithHeadingRow
{
    public function __construct(public readonly Upload $upload) {}

    public function collection(Collection $rows): void
    {
        $shopifyService = app(ShopifyService::class);
        $upload = $this->upload;

        $upload->update([
            'status' => UploadStatusEnum::PROCESSING->value,
            'total_records' => $rows->count(),
            'processed_records' => 0,
            'success_records' => 0,
            'failed_records' => 0,
        ]);

        $processed = 0;
        $success = 0;
        $failed = 0;

        foreach ($rows as $row) {
            $rowData = is_array($row) ? $row : $row->toArray();

            // Validate row data before creating ImportRecord
            $validator = Validator::make($rowData, [
                'handle' => ['required', 'string'],
                'title' => ['required', 'string'],
                'body_html' => ['required', 'string'],
                'vendor' => ['required', 'string'],
                'product_type' => ['required', 'string'],
                'tags' => ['required', 'string'],
                'published' => ['required', 'boolean'],
                'variant_sku' => ['required', 'string'],
                'variant_price' => ['required', 'numeric', 'min:0'],
                'variant_compare_at_price' => ['required', 'numeric', 'min:0'],
                'variant_requires_shipping' => ['required', 'boolean'],
                'variant_taxable' => ['required', 'boolean'],
                'variant_inventory_tracker' => ['required', 'string'],
                'variant_inventory_qty' => ['required', 'integer', 'min:0'],
                'variant_inventory_policy' => ['required', 'string'],
                'variant_fulfillment_service' => ['required', 'string'],
                'variant_weight' => ['required', 'numeric', 'min:0'],
                'variant_weight_unit' => ['required', 'string'],
                'image_src' => ['required', 'url'],
                'image_position' => ['nullable', 'integer', 'min:1'],
                'image_alt_text' => ['nullable', 'string'],
            ]);

            if ($validator->fails()) {
                $failed++;
                $processed++;

                $importRecord = ImportRecord::query()->create([
                    'upload_id' => $upload->id,
                    'data' => $rowData,
                    'status' => 'invalid',
                ]);

                $importRecord->errorLogs()->create([
                    'level' => 'error',
                    'event' => 'row_processed',
                    'message' => 'Validation failed: '.implode(', ', $validator->errors()->all()),
                    'context' => [
                        'row' => $rowData,
                    ],
                ]);

                // Create a failed product record if handle is present, to track it
                if (! empty($rowData['handle'])) {
                    Product::query()->updateOrCreate(
                        [
                            'handle' => $rowData['handle'],
                        ],
                        [
                            'upload_id' => $upload->id,
                            'import_record_id' => $importRecord->id,
                            'status' => 'failed',
                            'title' => $rowData['title'] ?? '',
                            'vendor' => $rowData['vendor'] ?? '',
                            'product_type' => $rowData['product_type'] ?? '',
                            'body_html' => $rowData['body_html'] ?? null,
                            'tags' => $rowData['tags'] ?? null,
                            'published' => isset($rowData['published']) ? filter_var($rowData['published'], FILTER_VALIDATE_BOOLEAN) : true,
                            'variant_sku' => $rowData['variant_sku'] ?? null,
                            'variant_price' => isset($rowData['variant_price']) ? floatval($rowData['variant_price']) : 0.00,
                            'variant_compare_at_price' => isset($rowData['variant_compare_at_price']) ? floatval($rowData['variant_compare_at_price']) : null,
                            'variant_weight' => isset($rowData['variant_weight']) ? floatval($rowData['variant_weight']) : null,
                            'variant_weight_unit' => $rowData['variant_weight_unit'] ?? null,
                            'variant_inventory_qty' => isset($rowData['variant_inventory_qty']) ? intval($rowData['variant_inventory_qty']) : 0,
                            'variant_inventory_policy' => $rowData['variant_inventory_policy'] ?? null,
                            'variant_inventory_tracker' => $rowData['variant_inventory_tracker'] ?? null,
                            'variant_requires_shipping' => isset($rowData['variant_requires_shipping']) ? filter_var($rowData['variant_requires_shipping'], FILTER_VALIDATE_BOOLEAN) : true,
                            'variant_fulfillment_service' => $rowData['variant_fulfillment_service'] ?? null,
                            'image' => $rowData['image_src'] ?? null,
                            'image_alt_text' => $rowData['image_alt_text'] ?? null,
                            'image_position' => isset($rowData['image_position']) ? intval($rowData['image_position']) : null,
                        ]
                    );
                }

                $upload->update([
                    'processed_records' => $processed,
                    'failed_records' => $failed,
                ]);

                continue;
            }

            // Create Import Record in pending status first (validation passed)
            $importRecord = ImportRecord::query()->create([
                'upload_id' => $upload->id,
                'data' => $rowData,
                'status' => 'validated',
            ]);

            // Create/update local product in processing status
            $product = Product::query()->updateOrCreate(
                [
                    'handle' => $rowData['handle'],
                ],
                [
                    'upload_id' => $upload->id,
                    'import_record_id' => $importRecord->id,
                    'status' => 'processing',
                    'title' => $rowData['title'],
                    'vendor' => $rowData['vendor'],
                    'product_type' => $rowData['product_type'],
                    'body_html' => $rowData['body_html'] ?? null,
                    'tags' => $rowData['tags'] ?? null,
                    'published' => isset($rowData['published']) ? filter_var($rowData['published'], FILTER_VALIDATE_BOOLEAN) : true,
                    'variant_sku' => $rowData['variant_sku'] ?? null,
                    'variant_price' => isset($rowData['variant_price']) ? floatval($rowData['variant_price']) : 0.00,
                    'variant_compare_at_price' => isset($rowData['variant_compare_at_price']) ? floatval($rowData['variant_compare_at_price']) : null,
                    'variant_weight' => isset($rowData['variant_weight']) ? floatval($rowData['variant_weight']) : null,
                    'variant_weight_unit' => $rowData['variant_weight_unit'] ?? null,
                    'variant_inventory_qty' => isset($rowData['variant_inventory_qty']) ? intval($rowData['variant_inventory_qty']) : 0,
                    'variant_inventory_policy' => $rowData['variant_inventory_policy'] ?? null,
                    'variant_inventory_tracker' => $rowData['variant_inventory_tracker'] ?? null,
                    'variant_requires_shipping' => isset($rowData['variant_requires_shipping']) ? filter_var($rowData['variant_requires_shipping'], FILTER_VALIDATE_BOOLEAN) : true,
                    'variant_fulfillment_service' => $rowData['variant_fulfillment_service'] ?? null,
                    'image' => $rowData['image_src'] ?? null,
                    'image_alt_text' => $rowData['image_alt_text'] ?? null,
                    'image_position' => isset($rowData['image_position']) ? intval($rowData['image_position']) : null,
                ]
            );

            try {
                // Sync product with Shopify
                $syncResult = $shopifyService->syncProduct($rowData);

                $shopify_product_id = Str::afterLast($syncResult['product_id'], '/');
                $shopify_variant_id = Str::afterLast($syncResult['variant_id'], '/');

                // Update product status to successful
                $product->update([
                    'status' => 'successful',
                    'shopify_product_id' => $shopify_product_id,
                    'shopify_variant_id' => $shopify_variant_id,
                ]);

                $importRecord->errorLogs()->create([
                    'level' => 'info',
                    'event' => 'product_created',
                    'message' => 'Successfully created/updated product in Shopify.',
                    'context' => [
                        'shopify_product_id' => $shopify_product_id,
                        'shopify_variant_id' => $shopify_variant_id,
                    ],
                ]);

                $success++;
            } catch (\Exception $e) {
                // Update product status to failed
                $product->update([
                    'status' => 'failed',
                ]);

                $importRecord->update([
                    'status' => 'invalid',
                ]);

                $product->errorLogs()->create([
                    'level' => 'error',
                    'event' => 'row_processed',
                    'message' => $e->getMessage(),
                    'context' => [
                        'row' => $rowData,
                    ],
                ]);

                $failed++;
            }

            $processed++;

            $upload->update([
                'processed_records' => $processed,
                'success_records' => $success,
                'failed_records' => $failed,
            ]);
        }

        $upload->update([
            'status' => UploadStatusEnum::COMPLETED->value,
        ]);

        $user = $upload->user;
        if ($user) {
            $user->notify(new UploadCompletedNotification(
                fileName: $upload->original_file_name,
                totalRecords: $upload->total_records,
                successRecords: $upload->success_records,
                failedRecords: $upload->failed_records
            ));
        }
    }
}
