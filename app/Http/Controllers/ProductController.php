<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    /**
     * Display a listing of the local products.
     */
    public function index(Request $request): Response
    {
        $products = Product::query()
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($product) => [
                'id' => $product->id,
                'shopify_product_id' => $product->shopify_product_id,
                'shopify_variant_id' => $product->shopify_variant_id,
                'status' => $product->status,
                'handle' => $product->handle,
                'title' => $product->title,
                'vendor' => $product->vendor,
                'product_type' => $product->product_type,
                'variant_sku' => $product->variant_sku,
                'variant_price' => $product->variant_price,
                'variant_inventory_qty' => $product->variant_inventory_qty,
                'image' => $product->image,
                'created_at' => $product->created_at->format('Y-m-d H:i:s'),
            ]);

        return Inertia::render('products/Index', [
            'products' => $products,
        ]);
    }
}
