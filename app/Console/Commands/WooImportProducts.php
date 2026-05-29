<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Product;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Support\Str;

class WooImportProducts extends Command
{
    protected $signature = 'woo:import-products';

    protected $description = 'Import WooCommerce Products';

    public function handle()
    {
        ini_set('max_execution_time', 0);

        $this->info('Starting Import...');

        $url = rtrim(env('WOOCOMMERCE_URL'), '/');

        /* ================= FETCH ALL CATEGORIES FROM WOOCOMMERCE ================= */

        $this->info('Fetching Categories from WooCommerce...');

        $catResponse = Http::withBasicAuth(
            env('WOOCOMMERCE_CK'),
            env('WOOCOMMERCE_CS')
        )
        ->timeout(300)
        ->retry(3, 5000)
        ->get($url . '/wp-json/wc/v3/products/categories', [
            'per_page' => 100
        ]);

        if (!$catResponse->successful()) {
            $this->error('Failed to fetch categories');
            return;
        }

        $wooCategories = $catResponse->json();
        
        // Create a map of category ID to category data for parent lookup
        $categoryDataMap = [];
        foreach ($wooCategories as $cat) {
            $categoryDataMap[$cat['id']] = $cat;
        }
        
        // Separate parent categories and child categories
        $parentCategories = [];
        $childCategories = [];
        
        foreach ($wooCategories as $cat) {
            if ($cat['parent'] == 0) {
                $parentCategories[] = $cat;
            } else {
                $childCategories[] = $cat;
            }
        }
        
        $this->info('Found ' . count($parentCategories) . ' parent categories');
        $this->info('Found ' . count($childCategories) . ' child categories (will become subcategories)');
        
        /* ================= STORE PARENT CATEGORIES FIRST ================= */
        
        $categoryIdMap = []; // Maps Woo ID -> our Category ID
        
        foreach ($parentCategories as $cat) {
            $image = null;
            
            if (!empty($cat['image']['src'])) {
                $this->info("Downloading Category Image: " . $cat['name']);
                $image = $this->saveCategoryImage($cat['image']['src']);
            }
            
            $savedCategory = Category::updateOrCreate(
                ['slug' => $cat['slug']],
                [
                    'name'   => $cat['name'],
                    'status' => 1,
                    'image'  => $image
                ]
            );
            
            $categoryIdMap[$cat['id']] = $savedCategory->id;
            
            $this->info("Parent Category Saved: " . $cat['name'] . " (ID: " . $savedCategory->id . ")");
        }
        
        /* ================= STORE CHILD CATEGORIES AS SUBCATEGORIES ================= */
        
        $subCategoryIdMap = []; // Maps Woo ID -> our SubCategory ID
        
        foreach ($childCategories as $cat) {
            $parentWooId = $cat['parent'];
            
            // Check if parent exists in our system
            if (!isset($categoryIdMap[$parentWooId])) {
                $this->warn("Parent category not found for: " . $cat['name'] . " (parent Woo ID: " . $parentWooId . ")");
                continue;
            }
            
            $parentCategoryId = $categoryIdMap[$parentWooId];
            
            // Get parent name for display
            $parentName = isset($categoryDataMap[$parentWooId]) ? $categoryDataMap[$parentWooId]['name'] : 'Unknown';
            
            // Download subcategory image if exists
            $image = null;
            if (!empty($cat['image']['src'])) {
                $this->info("Downloading Subcategory Image: " . $cat['name']);
                $image = $this->saveSubCategoryImage($cat['image']['src']);
            }
            
            $savedSubCategory = SubCategory::updateOrCreate(
                ['slug' => $cat['slug']],
                [
                    'category_id' => $parentCategoryId,
                    'name'        => $cat['name'],
                    'status'      => 1,
                    'image'       => $image
                ]
            );
            
            $subCategoryIdMap[$cat['id']] = $savedSubCategory->id;
            
            $this->info("Subcategory Saved: " . $cat['name'] . " under " . $parentName);
        }
        
        /* ================= IMPORT PRODUCTS ================= */
        
        $page = 1;
        $perPage = 10;
        
        do {
            $this->info("Fetching Products - Page {$page}");
            
            $response = Http::withBasicAuth(
                env('WOOCOMMERCE_CK'),
                env('WOOCOMMERCE_CS')
            )
            ->timeout(300)
            ->retry(3, 5000)
            ->get($url . '/wp-json/wc/v3/products', [
                'per_page' => $perPage,
                'page'     => $page,
                'status'   => 'publish'
            ]);
            
            if (!$response->successful()) {
                $this->error("API failed on page {$page}");
                break;
            }
            
            $products = $response->json();
            
            if (empty($products)) {
                $this->info('No more products found.');
                break;
            }
            
            foreach ($products as $item) {
                $this->info("Importing Product: " . $item['name']);
                
                /* ================= DETERMINE CATEGORY & SUBCATEGORY ================= */
                
                $categoryId = null;
                $subCategoryId = null;
                
                $wooProductCategory = $item['categories'][0] ?? null;
                
                if (!$wooProductCategory) {
                    $this->error('No category found for product: ' . $item['name']);
                    continue;
                }
                
                $wooCatId = $wooProductCategory['id'];
                
                // Check if this Woo category is a parent or child
                if (isset($subCategoryIdMap[$wooCatId])) {
                    // This is a subcategory
                    $subCategoryId = $subCategoryIdMap[$wooCatId];
                    
                    // Get the parent category from the subcategory
                    $subCategory = SubCategory::find($subCategoryId);
                    if ($subCategory) {
                        $categoryId = $subCategory->category_id;
                    }
                } elseif (isset($categoryIdMap[$wooCatId])) {
                    // This is a parent category
                    $categoryId = $categoryIdMap[$wooCatId];
                } else {
                    $this->error("Category mapping not found for Woo ID: " . $wooCatId);
                    continue;
                }
                
                if (!$categoryId) {
                    $this->error("Could not determine category for product: " . $item['name']);
                    continue;
                }
                
                /* ================= PRICE ================= */
                $price = is_numeric($item['price'] ?? null) ? (float) $item['price'] : 0;
                $sale = is_numeric($item['sale_price'] ?? null) ? (float) $item['sale_price'] : $price;
                
                /* ================= IMAGES ================= */
                $thumbnail = null;
                $gallery = [];
                
                if (!empty($item['images'])) {
                    foreach ($item['images'] as $index => $img) {
                        if (empty($img['src'])) {
                            continue;
                        }
                        
                        $this->info("Downloading Product Image");
                        $savedImage = $this->saveProductImage($img['src']);
                        
                        if ($savedImage) {
                            if ($index == 0) {
                                $thumbnail = $savedImage;
                            } else {
                                $gallery[] = $savedImage;
                            }
                        }
                    }
                }
                
                /* ================= SPECIFICATION ================= */
                $specification = null;
                
                if (!empty($item['meta_data'])) {
                    foreach ($item['meta_data'] as $meta) {
                        if ($meta['key'] == '_pls_product_custom_tab_content') {
                            $specification = $meta['value'];
                            break;
                        }
                    }
                }
                
                if (!$specification && !empty($item['attributes'])) {
                    $specification = json_encode($item['attributes']);
                }
                
                /* ================= SAVE PRODUCT ================= */
                Product::updateOrCreate(
                    ['slug' => $item['slug']],
                    [
                        'category_id'    => $categoryId,
                        'subcategory_id' => $subCategoryId,
                        'name' => Str::limit(strip_tags($item['name']), 150),
                        'slug' => $item['slug'],
                        'sku' => $item['sku'] ?: 'SKU-' . rand(1000, 9999),
                        'price' => $price,
                        'selling_price' => $sale,
                        'description' => $item['description'] ?? null,
                        'details' => $item['short_description'] ?? null,
                        'specification' => $specification,
                        'quantity' => $item['stock_quantity'] ?? 0,
                        'thumbnail' => $thumbnail,
                        'gallery' => !empty($gallery) ? json_encode($gallery) : null,
                        'status' => 1
                    ]
                );
                
                $this->info("Imported Successfully: " . $item['name']);
            }
            
            $page++;
            
        } while (true);
        
        $this->info('Import Completed Successfully!');
        $this->info('Parent Categories: ' . count($parentCategories));
        $this->info('Subcategories: ' . count($childCategories));
    }
    
    /* ================= IMAGE SAVE FUNCTIONS ================= */
    
    private function saveProductImage($url)
    {
        try {
            $response = Http::timeout(60)->get($url);
            if (!$response->successful()) {
                return null;
            }
            
            $contents = $response->body();
            $name = time() . '_prod_' . rand(1000, 9999) . '.jpg';
            $path = public_path('backend/assets/images/product/' . $name);
            
            if (!file_exists(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }
            
            file_put_contents($path, $contents);
            return $name;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    private function saveCategoryImage($url)
    {
        try {
            $response = Http::timeout(60)->get($url);
            if (!$response->successful()) {
                return null;
            }
            
            $contents = $response->body();
            $name = time() . '_cat_' . rand(1000, 9999) . '.jpg';
            $path = public_path('backend/assets/images/category/' . $name);
            
            if (!file_exists(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }
            
            file_put_contents($path, $contents);
            return $name;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    private function saveSubCategoryImage($url)
    {
        try {
            $response = Http::timeout(60)->get($url);
            if (!$response->successful()) {
                return null;
            }
            
            $contents = $response->body();
            $name = time() . '_subcat_' . rand(1000, 9999) . '.jpg';
            $path = public_path('backend/assets/images/subcategory/' . $name);
            
            if (!file_exists(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }
            
            file_put_contents($path, $contents);
            return $name;
        } catch (\Exception $e) {
            return null;
        }
    }
}