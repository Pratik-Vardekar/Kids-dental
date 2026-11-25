<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubCategory;
use App\Models\Tutorial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TutorialController extends Controller
{
    /**
     * Get all active Subcategories with tutorial count.
     */
    public function getTutorialSubcategories(): JsonResponse
    {
        $categories = SubCategory::with('category')
            ->where('status', 1)
            ->withCount(['tutorials' => function ($q) {
                $q->where('status', 1);
            }])
            ->orderBy('name')
            ->get()
            ->map(function ($sub) {
                return [
                    'id'              => $sub->id,
                    'title'           => $sub->name,
                    'slug'            => $sub->slug ?: Str::slug($sub->name),
                    'parent_category' => $sub->category?->name,
                    'tutorials_count' => (int) $sub->tutorials_count,
                    'status'          => (int) $sub->status,
                ];
            });

        return response()->json([
            'status'  => true,
            'message' => 'Tutorial subcategories fetched successfully',
            'data'    => $categories,
        ]);
    }

    /**
     * Get category-wise Tutorials by SubCategory.
     * Can receive subcategory slug either from URL route parameter /category-tutorials/{category_slug}
     * or via query parameters (?category_slug=..., ?slug=..., ?category=..., ?sub_category_id=...).
     * If no slug or category is specified, returns all active subcategories with their nested tutorials.
     */
    public function getCategoryTutorials(Request $request, $category_slug = null): JsonResponse
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
                    'status'  => false,
                    'message' => "Tutorial category '{$targetSlug}' not found",
                    'data'    => [],
                ], 404);
            }

            $tutorials = Tutorial::where('status', 1)
                ->where('sub_category_id', $subCategory->id)
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'asc')
                ->get()
                ->map(function ($tut) use ($subCategory) {
                    return [
                        'id'              => $tut->id,
                        'sub_category_id' => $subCategory->id,
                        'title'           => $tut->title,
                        'video_url'       => $tut->video_url,
                        'video_file'      => $tut->video_file ? asset('backend/assets/videos/tutorials/' . $tut->video_file) : null,
                        'is_direct_video' => $tut->is_direct_video,
                        'video_id'        => $tut->video_id ?: Tutorial::extractYoutubeId($tut->video_url),
                        'embed_url'       => $tut->embed_url,
                        'thumbnail'       => $tut->thumbnail_url,
                        'description'     => $tut->description,
                        'sort_order'      => $tut->sort_order,
                        'created_at'      => $tut->created_at?->toIso8601String(),
                    ];
                });

            return response()->json([
                'status'   => true,
                'message'  => "Tutorials for '{$subCategory->name}' fetched successfully",
                'category' => [
                    'id'              => $subCategory->id,
                    'title'           => $subCategory->name,
                    'slug'            => $subCategory->slug ?: Str::slug($subCategory->name),
                    'parent_category' => $subCategory->category?->name,
                    'tutorials_count' => $tutorials->count(),
                ],
                'data'     => $tutorials,
            ]);
        }

        // Return all active subcategories with their nested tutorials
        $all = SubCategory::with(['category', 'activeTutorials'])
            ->where('status', 1)
            ->orderBy('name')
            ->get()
            ->map(function ($sub) {
                return [
                    'id'              => $sub->id,
                    'title'           => $sub->name,
                    'slug'            => $sub->slug ?: Str::slug($sub->name),
                    'parent_category' => $sub->category?->name,
                    'tutorials_count' => $sub->activeTutorials->count(),
                    'tutorials'       => $sub->activeTutorials->map(function ($tut) {
                        return [
                            'id'              => $tut->id,
                            'title'           => $tut->title,
                            'video_url'       => $tut->video_url,
                            'video_file'      => $tut->video_file ? asset('backend/assets/videos/tutorials/' . $tut->video_file) : null,
                            'is_direct_video' => $tut->is_direct_video,
                            'video_id'        => $tut->video_id ?: Tutorial::extractYoutubeId($tut->video_url),
                            'embed_url'       => $tut->embed_url,
                            'thumbnail'       => $tut->thumbnail_url,
                            'description'     => $tut->description,
                            'sort_order'      => $tut->sort_order,
                        ];
                    }),
                ];
            });

        return response()->json([
            'status'  => true,
            'message' => 'All category tutorials fetched successfully',
            'data'    => $all,
        ]);
    }

    /**
     * Resolve SubCategory by ID, exact slug, or singular/plural slug/name.
     */
    protected function resolveSubCategory($identifier): ?SubCategory
    {
        if (empty($identifier)) {
            return null;
        }

        // 1. By primary key
        if (is_numeric($identifier)) {
            $found = SubCategory::with('category')->find($identifier);
            if ($found) {
                return $found;
            }
        }

        $cleanIdentifier = trim(urldecode((string) $identifier));
        $slugified = Str::slug($cleanIdentifier);

        // 2. Exact slug match
        $found = SubCategory::with('category')->where('slug', $slugified)->first();
        if ($found) {
            return $found;
        }

        // 3. Exact name match
        $found = SubCategory::with('category')
            ->where('name', $cleanIdentifier)
            ->orWhere('name', 'like', $cleanIdentifier)
            ->first();
        if ($found) {
            return $found;
        }

        // 4. Singular / Plural variation matching
        $singularSlug = Str::singular($slugified);
        $pluralSlug   = Str::plural($slugified);
        $trimmedSlug  = rtrim($slugified, 's');

        $candidates = array_unique(array_filter([$slugified, $singularSlug, $pluralSlug, $trimmedSlug]));

        $found = SubCategory::with('category')->where(function ($q) use ($candidates) {
            foreach ($candidates as $cand) {
                $q->orWhere('slug', $cand);
            }
        })->first();

        if ($found) {
            return $found;
        }

        // 5. Compare slugified subcategory names against candidate slugs
        $allSubs = SubCategory::with('category')->where('status', 1)->get();
        if ($allSubs->isEmpty()) {
            $allSubs = SubCategory::with('category')->get();
        }

        foreach ($allSubs as $sub) {
            $subSlug = Str::slug($sub->name);
            $subSingular = Str::singular($subSlug);
            $subPlural = Str::plural($subSlug);
            $subTrimmed = rtrim($subSlug, 's');

            if (in_array($subSlug, $candidates) ||
                in_array($subSingular, $candidates) ||
                in_array($subPlural, $candidates) ||
                in_array($subTrimmed, $candidates)) {
                return $sub;
            }
        }

        // 6. Loose token match
        $plainToken = strtolower(preg_replace('/[^a-z0-9]/i', '', $cleanIdentifier));
        if (strlen($plainToken) >= 3) {
            foreach ($allSubs as $sub) {
                $cleanedName = strtolower(preg_replace('/[^a-z0-9]/i', '', $sub->name));
                if ($cleanedName === $plainToken ||
                    str_contains($cleanedName, $plainToken) ||
                    str_contains($plainToken, $cleanedName)) {
                    return $sub;
                }
            }
        }

        return null;
    }
}
