<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category as ProductCategory;
use App\Models\HelpCategory;
use App\Models\HelpFaq;
use App\Models\HomeFaq;
use App\Models\HomeFaqSection;
use App\Models\SubCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FaqController extends Controller
{
    /**
     * Get all active FAQ categories dynamically from SubCategories.
     */
    public function getFaqCategories(): JsonResponse
    {
        $categories = SubCategory::with('category')
            ->where('status', 1)
            ->withCount(['faqs' => function ($q) {
                $q->where('status', 1);
            }])
            ->orderBy('name')
            ->get()
            ->map(function ($sub) {
                return [
                    'id' => $sub->id,
                    'title' => $sub->name,
                    'slug' => $sub->slug ?: Str::slug($sub->name),
                    'parent_category' => $sub->category?->name,
                    'faqs_count' => (int) $sub->faqs_count,
                    'status' => (int) $sub->status,
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'FAQ categories fetched successfully',
            'data' => $categories,
        ]);
    }

    /**
     * Get category-wise FAQs by SubCategory.
     * Can receive subcategory slug either from URL route parameter /category-faqs/{category_slug}
     * or via query parameters (?category_slug=..., ?slug=..., ?category=..., ?sub_category_id=...).
     * If no slug or category is specified, returns all active subcategories with their nested FAQs.
     */
    public function getCategoryFaqs(Request $request, $category_slug = null): JsonResponse
    {
        $targetSlug = $category_slug
            ?: ($request->input('category_slug')
                ?: ($request->input('slug')
                    ?: ($request->input('category')
                        ?: ($request->input('subcategory')
                            ?: ($request->input('category_title')
                                ?: $request->input('title'))))));

        $subCategoryId = $request->input('sub_category_id')
            ?: ($request->input('category_id') ?: $request->input('id'));

        // If a specific subcategory was requested
        if (!empty($targetSlug) || !empty($subCategoryId)) {
            $subCategory = $this->resolveSubCategory($subCategoryId ?: $targetSlug);

            if (!$subCategory) {
                return response()->json([
                    'status' => false,
                    'message' => "FAQ Category '{$targetSlug}' not found",
                    'data' => [],
                ], 404);
            }

            $faqs = HelpFaq::where('status', 1)
                ->where(function ($q) use ($subCategory) {
                    $q->where('sub_category_id', $subCategory->id);

                    // Fallback for legacy rows linked by help_category_id
                    $q->orWhere(function ($sq) use ($subCategory) {
                        $sq->whereNull('sub_category_id')
                           ->whereHas('category', function ($cq) use ($subCategory) {
                               $cq->where('title', 'like', '%' . $subCategory->name . '%');
                           });
                    });
                })
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(function ($faq) use ($subCategory) {
                    return [
                        'id' => $faq->id,
                        'sub_category_id' => $subCategory->id,
                        'question' => $faq->question,
                        'answer' => $faq->answer,
                        'sort_order' => $faq->sort_order,
                    ];
                });

            return response()->json([
                'status' => true,
                'message' => "FAQs for '{$subCategory->name}' fetched successfully",
                'category' => [
                    'id' => $subCategory->id,
                    'title' => $subCategory->name,
                    'slug' => $subCategory->slug ?: Str::slug($subCategory->name),
                    'parent_category' => $subCategory->category?->name,
                    'faqs_count' => $faqs->count(),
                ],
                'data' => $faqs,
            ]);
        }

        // When no specific slug is provided, return all active subcategories with their nested FAQs
        $subcategoriesWithFaqs = SubCategory::with(['category', 'faqs' => function ($q) {
            $q->where('status', 1)->orderBy('sort_order')->orderBy('id');
        }])
            ->where('status', 1)
            ->orderBy('name')
            ->get()
            ->map(function ($sub) {
                return [
                    'id' => $sub->id,
                    'title' => $sub->name,
                    'slug' => $sub->slug ?: Str::slug($sub->name),
                    'parent_category' => $sub->category?->name,
                    'faqs_count' => $sub->faqs->count(),
                    'faqs' => $sub->faqs->map(function ($faq) use ($sub) {
                        return [
                            'id' => $faq->id,
                            'sub_category_id' => $sub->id,
                            'question' => $faq->question,
                            'answer' => $faq->answer,
                            'sort_order' => $faq->sort_order,
                        ];
                    })->values(),
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'Category-wise FAQs fetched successfully',
            'data' => $subcategoriesWithFaqs,
        ]);
    }

    /**
     * Resolve a SubCategory by numeric ID, slug, name, singular/plural variants, or prefix.
     */
    protected function resolveSubCategory($identifier)
    {
        if (empty($identifier)) {
            return null;
        }

        // 1. Direct Numeric ID
        if (is_numeric($identifier)) {
            $sub = SubCategory::with('category')->find($identifier);
            if ($sub) {
                return $sub;
            }
        }

        // 2. Direct Slug match
        $sub = SubCategory::with('category')
            ->where('slug', $identifier)
            ->first();
        if ($sub) {
            return $sub;
        }

        $allSubcategories = SubCategory::with('category')->get();
        if ($allSubcategories->isEmpty()) {
            return null;
        }

        $targetSlug = Str::slug($identifier);
        $targetSingular = Str::singular($targetSlug);
        $targetPlural = Str::plural($targetSlug);
        $targetTrimmed = rtrim($targetSlug, 's');

        // 3. Exact slug comparison in PHP
        foreach ($allSubcategories as $sub) {
            $cSlug = Str::slug($sub->slug ?: $sub->name);
            if ($cSlug === $targetSlug) {
                return $sub;
            }
        }

        // 4. Singular / Plural variant match (e.g. 'bioflx-crown' <=> 'Bioflx Crowns')
        foreach ($allSubcategories as $sub) {
            $cSlug = Str::slug($sub->slug ?: $sub->name);
            $cSingular = Str::singular($cSlug);
            $cPlural = Str::plural($cSlug);
            $cTrimmed = rtrim($cSlug, 's');

            if (
                $cSingular === $targetSingular ||
                $cPlural === $targetPlural ||
                $cTrimmed === $targetTrimmed ||
                $cSlug === $targetPlural ||
                $cSlug === $targetSingular
            ) {
                return $sub;
            }
        }

        // 5. Prefix match (e.g. 'bioflx-crown' starts 'bioflx-crowns')
        foreach ($allSubcategories as $sub) {
            $cSlug = Str::slug($sub->slug ?: $sub->name);
            if (str_starts_with($cSlug, $targetSlug) || str_starts_with($targetSlug, $cSlug)) {
                return $sub;
            }
        }

        // 6. Substring match
        foreach ($allSubcategories as $sub) {
            $cSlug = Str::slug($sub->slug ?: $sub->name);
            if (str_contains($cSlug, $targetSlug) || str_contains($targetSlug, $cSlug)) {
                return $sub;
            }
        }

        // 7. Exact or LIKE name match
        $sub = SubCategory::with('category')
            ->where('name', 'like', "%{$identifier}%")
            ->first();
        if ($sub) {
            return $sub;
        }

        // 8. Match by parent product Category
        try {
            $parentCat = ProductCategory::where('slug', $identifier)
                ->orWhere('name', $identifier)
                ->first();

            if ($parentCat) {
                $sub = SubCategory::with('category')->where('category_id', $parentCat->id)->first();
                if ($sub) {
                    return $sub;
                }
            }
        } catch (\Throwable $e) {
            // Silently ignore
        }

        return null;
    }

    /**
     * Get home page FAQs.
     */
    public function getHomeFaqs(): JsonResponse
    {
        $faqs = HomeFaq::where('status', 1)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'question', 'answer', 'sort_order']);

        $section = HomeFaqSection::first();

        return response()->json([
            'status' => true,
            'message' => 'Home FAQs fetched successfully',
            'section' => $section ? [
                'id' => $section->id,
                'image' => $section->image ? url('backend/assets/images/' . $section->image) : null,
            ] : null,
            'data' => $faqs,
        ]);
    }
}
