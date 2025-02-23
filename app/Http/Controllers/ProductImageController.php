<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{

    function index(Request $request, Product $product)
    {
        return response()->json(['images' => $product->images]);
    }
    public function attach(Request $request, Product $product)
    {
        $request->validate([
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $uploadedImages = [];

        foreach ($request->file('images') as $image) {
            // Store the image in S3
            $path = $image->store('public/product_images');

            // Attach the image to the product
            $productImage = $product->images()->create([
                'path' => $path,
            ]);

            $uploadedImages[] = $productImage;
        }

        return response()->json(['images' => $uploadedImages, 'message' => 'Imagenes correctamente guardadas' ], 201);
    }
    public function detach(Product $product, ProductImage $image)
    {
        // Check if the image belongs to the product
        if ($product->id !== $image->product_id) {
            return response()->json(['error' => 'Image does not belong to this product'], 403);
        }

        // Delete the image from S3
        Storage::delete($image->path);

        // Delete the image record from the database
        $image->delete();

        return response()->json(['message' => 'Image detached successfully'], 200);
    }
    public function setFeatured(Product $product, ProductImage $image)
    {
        // Ensure the image belongs to the product
        if ($product->id !== $image->product_id) {
            return response()->json(['error' => 'Image does not belong to this product'], 403);
        }

        // Reset featured status for other images of this product
        $product->images()->update(['is_featured' => false]);

        // Set the selected image as featured
        $image->update(['is_featured' => true]);

        return response()->json(['message' => 'Featured image set successfully'], 200);
    }
}
