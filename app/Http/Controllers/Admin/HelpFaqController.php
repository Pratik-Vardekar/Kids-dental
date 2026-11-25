<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HelpCategory;
use App\Models\HelpFaq;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class HelpFaqController extends Controller
{
    /**
     * Display a listing of Category FAQs dynamically fetched by SubCategory.
     */
    public function index(Request $request)
    {
        $subcategoryId = $request->input('sub_category_id') ?: $request->input('category_id');
        $search = trim((string) $request->input('search', ''));

        // Dynamically fetch all subcategories from the store catalog
        $subcategories = SubCategory::with('category')
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        if ($subcategories->isEmpty()) {
            $subcategories = SubCategory::with('category')->orderBy('name')->get();
        }

        $selectedSubcategory = $subcategoryId ? $subcategories->firstWhere('id', (int) $subcategoryId) : null;

        $faqsQuery = HelpFaq::with(['subCategory.category', 'category']);

        if (!empty($subcategoryId)) {
            $faqsQuery->where(function ($q) use ($subcategoryId, $selectedSubcategory) {
                $q->where('sub_category_id', $subcategoryId);

                // Fallback for legacy rows linked by help_category_id
                if ($selectedSubcategory) {
                    $q->orWhere(function ($sq) use ($selectedSubcategory) {
                        $sq->whereNull('sub_category_id')
                           ->whereHas('category', function ($cq) use ($selectedSubcategory) {
                               $cq->where('title', 'like', '%' . $selectedSubcategory->name . '%');
                           });
                    });
                }
            });
        }

        if (!empty($search)) {
            $faqsQuery->where(function ($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                  ->orWhere('answer', 'like', "%{$search}%");
            });
        }

        $faqs = $faqsQuery->orderBy('sort_order')->orderBy('id')->paginate(15)->withQueryString();

        // Calculate counts per subcategory for filter pills
        $rawCounts = HelpFaq::selectRaw('sub_category_id, count(*) as count')
            ->whereNotNull('sub_category_id')
            ->groupBy('sub_category_id')
            ->pluck('count', 'sub_category_id');

        $subcategoryCounts = [];
        foreach ($subcategories as $sub) {
            $subcategoryCounts[$sub->id] = $rawCounts[$sub->id] ?? 0;
        }

        $totalFaqsCount = HelpFaq::count();

        return view('admin.help-faqs.index', compact(
            'subcategories',
            'selectedSubcategory',
            'subcategoryId',
            'search',
            'faqs',
            'subcategoryCounts',
            'totalFaqsCount'
        ));
    }

    /**
     * Show the form for creating a new Category FAQ.
     */
    public function create(Request $request)
    {
        $subcategories = SubCategory::with('category')
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        if ($subcategories->isEmpty()) {
            $subcategories = SubCategory::with('category')->orderBy('name')->get();
        }

        $selectedSubcategoryId = $request->input('sub_category_id') ?: $request->input('category_id');

        return view('admin.help-faqs.create', compact('subcategories', 'selectedSubcategoryId'));
    }

    /**
     * Store a newly created Category FAQ in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'sub_category_id' => 'required|exists:sub_categories,id',
            'question' => 'required|string|max:1000',
            'answer' => 'required|string',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|in:0,1',
        ]);

        $subCategory = SubCategory::find($request->sub_category_id);

        // Ensure help_category_id is provided if required by existing DB schema foreign keys
        $helpCategoryId = null;
        if (Schema::hasTable('help_categories')) {
            $helpCat = HelpCategory::where('title', 'like', '%' . ($subCategory?->name ?? '') . '%')->first();
            if (!$helpCat && $subCategory) {
                $helpCat = HelpCategory::firstOrCreate(
                    ['title' => $subCategory->name],
                    [
                        'slug' => Str::slug($subCategory->name),
                        'color' => 'teal',
                        'sort_order' => 1,
                        'status' => 1,
                    ]
                );
            }
            $helpCategoryId = $helpCat?->id ?: HelpCategory::value('id');
        }

        $data = [
            'question' => $request->question,
            'answer' => $request->answer,
            'sort_order' => $request->sort_order ?? 0,
            'status' => $request->status,
        ];

        if (Schema::hasColumn('help_faqs', 'sub_category_id')) {
            $data['sub_category_id'] = $request->sub_category_id;
        }

        if (Schema::hasColumn('help_faqs', 'help_category_id') && $helpCategoryId) {
            $data['help_category_id'] = $helpCategoryId;
        }

        HelpFaq::create($data);

        return redirect()
            ->route('admin.help-faqs.index', ['sub_category_id' => $request->sub_category_id])
            ->with('success', 'Question & Answer added successfully.');
    }

    /**
     * Show the form for editing the specified Category FAQ.
     */
    public function edit(HelpFaq $helpFaq)
    {
        $subcategories = SubCategory::with('category')
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        if ($subcategories->isEmpty()) {
            $subcategories = SubCategory::with('category')->orderBy('name')->get();
        }

        return view('admin.help-faqs.edit', compact('helpFaq', 'subcategories'));
    }

    /**
     * Update the specified Category FAQ in storage.
     */
    public function update(Request $request, HelpFaq $helpFaq)
    {
        $request->validate([
            'sub_category_id' => 'required|exists:sub_categories,id',
            'question' => 'required|string|max:1000',
            'answer' => 'required|string',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|in:0,1',
        ]);

        $subCategory = SubCategory::find($request->sub_category_id);

        $helpCategoryId = $helpFaq->help_category_id;
        if (Schema::hasTable('help_categories') && $subCategory) {
            $helpCat = HelpCategory::where('title', 'like', '%' . $subCategory->name . '%')->first();
            if ($helpCat) {
                $helpCategoryId = $helpCat->id;
            }
        }

        $data = [
            'question' => $request->question,
            'answer' => $request->answer,
            'sort_order' => $request->sort_order ?? 0,
            'status' => $request->status,
        ];

        if (Schema::hasColumn('help_faqs', 'sub_category_id')) {
            $data['sub_category_id'] = $request->sub_category_id;
        }

        if ($helpCategoryId) {
            $data['help_category_id'] = $helpCategoryId;
        }

        $helpFaq->update($data);

        return redirect()
            ->route('admin.help-faqs.index', ['sub_category_id' => $request->sub_category_id])
            ->with('success', 'Question & Answer updated successfully.');
    }

    /**
     * Remove the specified Category FAQ from storage.
     */
    public function destroy(HelpFaq $helpFaq)
    {
        $subCatId = $helpFaq->sub_category_id;
        $helpFaq->delete();

        return redirect()
            ->route('admin.help-faqs.index', ['sub_category_id' => $subCatId])
            ->with('success', 'Question & Answer deleted successfully.');
    }
}
