<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ShopifyService
{
    protected string $shopDomain;

    protected string $accessToken;

    protected string $apiVersion;

    protected string $graphqlUrl;

    public function __construct()
    {
        $storeUrl = config('services.shopify.store_url', '');

        // Normalize shop domain (handle scheme, raw host names, trailing slashes)
        $host = parse_url($storeUrl, PHP_URL_HOST);
        $this->shopDomain = $host ? rtrim($host, '/') : rtrim($storeUrl, '/');

        $this->accessToken = config('services.shopify.api_key', '');
        $this->apiVersion = '2026-07';

        $this->graphqlUrl = "https://{$this->shopDomain}/admin/api/{$this->apiVersion}/graphql.json";
    }

    /**
     * Send a raw GraphQL request to Shopify with explicit best-practice timeouts.
     */
    public function query(string $query, array $variables = []): array
    {
        if (empty($this->shopDomain) || empty($this->accessToken)) {
            throw new \RuntimeException('Shopify API credentials or store URL are not configured.');
        }

        $payload = [
            'query' => $query,
        ];

        if (! empty($variables)) {
            $payload['variables'] = $variables;
        }

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->accessToken,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])
            ->connectTimeout(5)
            ->timeout(15)
            ->post($this->graphqlUrl, $payload);

        if ($response->failed()) {
            Log::error('Shopify API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'query' => $query,
                'variables' => $variables,
            ]);
            throw new \RuntimeException('Shopify API Error: '.$response->body());
        }

        $data = $response->json();

        if (isset($data['errors'])) {
            Log::error('Shopify GraphQL returned errors', [
                'errors' => $data['errors'],
                'query' => $query,
                'variables' => $variables,
            ]);
            throw new \RuntimeException('Shopify GraphQL Error: '.json_encode($data['errors']));
        }

        return $data;
    }

    /**
     * Get the default/first location ID to update inventory targets.
     */
    public function getFirstLocationId(): ?string
    {
        try {
            $query = '
                    query {
                        locations(first: 1) {
                            edges {
                                node {
                                    id
                                }
                            }
                        }
                    }
                ';

            $result = $this->query($query);

            return $result['data']['locations']['edges'][0]['node']['id'] ?? null;
        } catch (\Exception $e) {
            Log::warning('Could not fetch Shopify location ID: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Lookup a product by its handle and fetch IDs of is main elements (Product, Variant, InventoryItem).
     */
    public function getProductByHandle(string $handle): ?array
    {
        $query = '
            query($query: String!) {
                products(first: 1, query: $query) {
                    edges {
                        node {
                            id
                            handle
                            title
                            variants(first: 1) {
                                edges {
                                    node {
                                        id
                                        sku
                                        inventoryItem {
                                            id
                                        }
                                    }
                                }
                            }
                            images(first: 1) {
                                edges {
                                    node {
                                        id
                                        url
                                    }
                                }
                            }
                        }
                    }
                }
            }
        ';

        $result = $this->query($query, ['query' => "handle:{$handle}"]);
        $edges = $result['data']['products']['edges'] ?? [];

        if (empty($edges)) {
            return null;
        }

        $node = $edges[0]['node'];
        $variantNode = $node['variants']['edges'][0]['node'] ?? null;
        $imageNode = $node['images']['edges'][0]['node'] ?? null;

        return [
            'id' => $node['id'],
            'handle' => $node['handle'],
            'title' => $node['title'],
            'variant_id' => $variantNode ? $variantNode['id'] : null,
            'variant_sku' => $variantNode ? $variantNode['sku'] : null,
            'inventory_item_id' => $variantNode && isset($variantNode['inventoryItem']['id']) ? $variantNode['inventoryItem']['id'] : null,
            'has_image' => ! empty($imageNode),
        ];
    }

    /**
     * Create or update a product matching the payload details.
     */
    public function syncProduct(array $data): array
    {
        $handle = $data['handle'] ?? null;
        if (empty($handle)) {
            throw new \InvalidArgumentException('Product handle is required for Shopify sync.');
        }

        $existingProduct = $this->getProductByHandle($handle);

        if ($existingProduct) {
            return $this->updateProduct($existingProduct, $data);
        } else {
            return $this->createProduct($data);
        }
    }

    /**
     * Build the standard input array from row data.
     */
    protected function mapProductInput(array $data): array
    {
        $tags = isset($data['tags'])
            ? array_map('trim', explode(',', $data['tags']))
            : [];

        // Map published flag to active/draft status
        $status = filter_var($data['published'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'ACTIVE' : 'DRAFT';

        return [
            'title' => $data['title'] ?? '',
            'handle' => $data['handle'] ?? '',
            'descriptionHtml' => $data['body_html'] ?? '',
            'vendor' => $data['vendor'] ?? '',
            'productType' => $data['product_type'] ?? '',
            'tags' => $tags,
            'status' => $status,
            'productOptions' => [
                [
                    'name' => 'Title',
                    'values' => [
                        ['name' => 'Default Title'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Build standard physical parameters for variant objects.
     */
    protected function mapVariantInput(array $data, ?string $id = null): array
    {
        $variantInput = [
            'sku' => $data['variant_sku'] ?? null,
            'price' => isset($data['variant_price']) ? floatval($data['variant_price']) : null,
            'compareAtPrice' => isset($data['variant_compare_at_price']) ? floatval($data['variant_compare_at_price']) : null,
            'taxable' => isset($data['variant_taxable']) ? filter_var($data['variant_taxable'], FILTER_VALIDATE_BOOLEAN) : null,
            'optionValues' => [
                [
                    'optionName' => 'Title',
                    'name' => 'Default Title',
                ],
            ],
        ];

        if ($id) {
            $variantInput['id'] = $id;
        }

        // Set up inventoryItem fields
        $inventoryItem = [];

        if (isset($data['variant_requires_shipping'])) {
            $inventoryItem['requiresShipping'] = filter_var($data['variant_requires_shipping'], FILTER_VALIDATE_BOOLEAN);
        }

        // Standardize weight units
        if (isset($data['variant_weight'])) {
            $weightUnit = strtoupper($data['variant_weight_unit'] ?? 'KG');
            if ($weightUnit === 'KG') {
                $weightUnit = 'KILOGRAMS';
            } elseif ($weightUnit === 'G') {
                $weightUnit = 'GRAMS';
            } elseif ($weightUnit === 'LB' || $weightUnit === 'LBS') {
                $weightUnit = 'POUNDS';
            } elseif ($weightUnit === 'OZ') {
                $weightUnit = 'OUNCES';
            }

            $inventoryItem['measurement'] = [
                'weight' => [
                    'value' => floatval($data['variant_weight']),
                    'unit' => $weightUnit,
                ],
            ];
        }

        // Tracker setup
        $tracker = strtolower($data['variant_inventory_tracker'] ?? '');
        if ($tracker === 'shopify') {
            $inventoryItem['tracked'] = true;

            $policy = strtolower($data['variant_inventory_policy'] ?? 'deny');
            $variantInput['inventoryPolicy'] = $policy === 'continue' ? 'CONTINUE' : 'DENY';
        }

        if (! empty($inventoryItem)) {
            $variantInput['inventoryItem'] = $inventoryItem;
        }

        return $variantInput;
    }

    /**
     * Perform Shopify productSet GraphQL mutation.
     */
    public function createProduct(array $data): array
    {
        $productInput = $this->mapProductInput($data);
        $variantInput = $this->mapVariantInput($data);

        // Include nested image on create if image_src is set
        if (! empty($data['image_src'])) {
            $productInput['files'] = [
                [
                    'originalSource' => $data['image_src'],
                    'contentType' => 'IMAGE',
                    'alt' => $data['image_alt_text'] ?? null,
                ],
            ];
        }

        $productInput['variants'] = [$variantInput];

        $mutation = '
            mutation productSet($input: ProductSetInput!) {
                productSet(input: $input) {
                    product {
                        id
                        handle
                        variants(first: 1) {
                            edges {
                                node {
                                    id
                                    sku
                                    inventoryItem {
                                        id
                                    }
                                }
                            }
                        }
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        ';

        $result = $this->query($mutation, ['input' => $productInput]);
        $userErrors = $result['data']['productSet']['userErrors'] ?? [];

        if (! empty($userErrors)) {
            Log::warning('Shopify productSet user errors', ['errors' => $userErrors]);
            throw new \RuntimeException('Shopify product creation failed: '.json_encode($userErrors));
        }

        $createdProduct = $result['data']['productSet']['product'];
        $createdVariant = $createdProduct['variants']['edges'][0]['node'] ?? null;

        Log::info('Shopify product created successfully', ['product_id' => $createdProduct['id']]);

        // Connect collection
        $this->joinCollection($createdProduct['id']);

        // Update inventory level if needed
        $tracker = strtolower($data['variant_inventory_tracker'] ?? '');
        if ($tracker === 'shopify' && $createdVariant && isset($data['variant_inventory_qty'])) {
            $this->setInventoryQuantity(
                $createdVariant['inventoryItem']['id'],
                intval($data['variant_inventory_qty'])
            );
        }

        return [
            'product_id' => $createdProduct['id'],
            'variant_id' => $createdVariant ? $createdVariant['id'] : null,
            'status' => 'created',
        ];
    }

    /**
     * Perform Shopify productSet GraphQL mutation to update.
     */
    public function updateProduct(array $existingProduct, array $data): array
    {
        $productId = $existingProduct['id'];
        $variantId = $existingProduct['variant_id'];

        $productInput = $this->mapProductInput($data);
        $productInput['id'] = $productId;

        // If no images are present on the product and image_src is set, allow attaching it
        if (! $existingProduct['has_image'] && ! empty($data['image_src'])) {
            $productInput['files'] = [
                [
                    'originalSource' => $data['image_src'],
                    'contentType' => 'IMAGE',
                    'alt' => $data['image_alt_text'] ?? null,
                ],
            ];
        }

        $variantInput = $this->mapVariantInput($data, $variantId);
        $productInput['variants'] = [$variantInput];

        $mutation = '
            mutation productSet($input: ProductSetInput!) {
                productSet(input: $input) {
                    product {
                        id
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        ';

        $result = $this->query($mutation, ['input' => $productInput]);
        $userErrors = $result['data']['productSet']['userErrors'] ?? [];

        if (! empty($userErrors)) {
            Log::warning('Shopify productSet update user errors', ['errors' => $userErrors]);
            throw new \RuntimeException('Shopify product update failed: '.json_encode($userErrors));
        }

        Log::info('Shopify product updated successfully', ['product_id' => $productId]);

        // Join collection
        $this->joinCollection($productId);

        // Update inventory level if needed
        $tracker = strtolower($data['variant_inventory_tracker'] ?? '');
        if ($tracker === 'shopify' && ! empty($existingProduct['inventory_item_id']) && isset($data['variant_inventory_qty'])) {
            $this->setInventoryQuantity(
                $existingProduct['inventory_item_id'],
                intval($data['variant_inventory_qty'])
            );
        }

        return [
            'product_id' => $productId,
            'variant_id' => $variantId,
            'status' => 'updated',
        ];
    }

    /**
     * Associate product to target collection if configured.
     */
    public function joinCollection(string $productId): void
    {
        $collectionId = config('services.shopify.collection_id');
        if (empty($collectionId)) {
            return;
        }

        // Normalize Collection ID to GID format
        $collectionGid = str_starts_with($collectionId, 'gid://')
            ? $collectionId
            : "gid://shopify/Collection/{$collectionId}";

        $mutation = '
            mutation collectionAddProducts($id: ID!, $productIds: [ID!]!) {
                collectionAddProducts(id: $id, productIds: $productIds) {
                    collection {
                        id
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        ';

        try {
            $result = $this->query($mutation, [
                'id' => $collectionGid,
                'productIds' => [$productId],
            ]);

            $userErrors = $result['data']['collectionAddProducts']['userErrors'] ?? [];
            if (! empty($userErrors)) {
                Log::warning('Shopify collectionAddProducts user errors', [
                    'product_id' => $productId,
                    'collection_id' => $collectionGid,
                    'errors' => $userErrors,
                ]);
            } else {
                Log::info('Associated product to Shopify collection', [
                    'product_id' => $productId,
                    'collection_id' => $collectionGid,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to link product to collection', [
                'product_id' => $productId,
                'collection_id' => $collectionGid,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Set explicit quantities on matching inventory item matching local levels.
     */
    public function setInventoryQuantity(string $inventoryItemId, int $quantity): void
    {
        $locationId = $this->getFirstLocationId();
        if (empty($locationId)) {
            Log::warning('Cannot update inventory. Default location ID could not be found.');

            return;
        }

        $mutation = '
            mutation inventorySetQuantities($input: InventorySetQuantitiesInput!, $key: String!) {
                inventorySetQuantities(input: $input) @idempotent(key: $key) {
                    inventoryAdjustmentGroup {
                        createdAt
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        ';

        $variables = [
            'input' => [
                'name' => 'available',
                'reason' => 'correction',
                'quantities' => [
                    [
                        'inventoryItemId' => $inventoryItemId,
                        'locationId' => $locationId,
                        'quantity' => $quantity,
                        'changeFromQuantity' => null,
                    ],
                ],
            ],
            'key' => (string) Str::uuid(),
        ];

        try {
            $result = $this->query($mutation, $variables);
            $userErrors = $result['data']['inventorySetQuantities']['userErrors'] ?? [];

            if (! empty($userErrors)) {
                Log::warning('Shopify inventorySetQuantities user errors', [
                    'inventory_item_id' => $inventoryItemId,
                    'errors' => $userErrors,
                ]);
            } else {
                Log::info('Shopify inventory quantity updated', [
                    'inventory_item_id' => $inventoryItemId,
                    'quantity' => $quantity,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to set Shopify inventory quantity', [
                'inventory_item_id' => $inventoryItemId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
