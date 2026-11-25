<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\HandlesCsv;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\ChildCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChildCategoryController extends Controller
{
    use HandlesCsv;


    private function resolveView(string $viewName): string
    {
        if (view()->exists('admin.child_category.' . $viewName)) {
            return 'admin.child_category.' . $viewName;
        }

        return 'admin.childcategory.' . $viewName;
    }

    private function childCategoryExportHeaders(): array
    {
        return ['Category', 'Sub Category', 'Name', 'Slug', 'Status'];
    }

    /**
     * Display child categories.
     */
    public function index(Request $request)
    {
        $query = ChildCategory::with(['subcategory.category']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('slug', 'LIKE', '%' . $request->search . '%')
                    ->orWhereHas('subcategory', function ($subQuery) use ($request) {
                        $subQuery->where('name', 'LIKE', '%' . $request->search . '%')
                            ->orWhereHas('category', function ($catQuery) use ($request) {
                                $catQuery->where('name', 'LIKE', '%' . $request->search . '%');
                            });
                    });
            });
        }

        if ($request->category_id) {
            $query->whereHas('subcategory', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        if ($request->sub_category_id) {
            $query->where('sub_category_id', $request->sub_category_id);
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->date) {
            $query->whereDate('created_at', $request->date);
        }

        $childCategories = $query->latest()->paginate(10)->withQueryString();

        $categories = Category::where('status', 1)->get();
        $subcategories = SubCategory::where('status', 1)->get();

        return view(
            $this->resolveView('index'),
            compact('childCategories', 'categories', 'subcategories')
        );
    }

    /**
     * Create form.
     */
    public function create()
    {
        $categories = Category::where('status', 1)->get();

        return view($this->resolveView('add'), compact('categories'));
    }

    /**
     * Store child category.
     */
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'sub_category_id' => 'required|exists:sub_categories,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:child_categories,slug',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'status' => 'required|in:0,1',
        ]);

        // Ensure selected subcategory belongs to selected category
        $subcategory = SubCategory::where('id', $request->sub_category_id)
            ->where('category_id', $request->category_id)
            ->firstOrFail();

        $imageName = null;

        if ($request->hasFile('image')) {
            $imageName = time() . '.' . $request->image->extension();

            $request->image->move(
                public_path('backend/assets/images/childcategory'),
                $imageName
            );
        }

        ChildCategory::create([
            'sub_category_id' => $subcategory->id,
            'name' => $request->name,
            'slug' => $request->slug
                ? Str::slug($request->slug)
                : Str::slug($request->name),
            'image' => $imageName,
            'status' => $request->status,
        ]);

        return redirect()
            ->route('childcategory.index')
            ->with('success', 'Child Category added successfully');
    }

    /**
     * Edit form.
     */
    public function edit(ChildCategory $childcategory)
    {
        $categories = Category::where('status', 1)->get();

        $subcategories = SubCategory::where(
            'category_id',
            $childcategory->subcategory->category_id
        )->where('status', 1)->get();

        return view(
            $this->resolveView('edit'),
            compact('childcategory', 'categories', 'subcategories')
        );
    }

    /**
     * Update child category.
     */
    public function update(Request $request, ChildCategory $childcategory)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'sub_category_id' => 'required|exists:sub_categories,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:child_categories,slug,' . $childcategory->id,
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'status' => 'required|in:0,1',
        ]);

        $subcategory = SubCategory::where('id', $request->sub_category_id)
            ->where('category_id', $request->category_id)
            ->firstOrFail();

        $imageName = $childcategory->image;

        if ($request->hasFile('image')) {
            if (
                $imageName &&
                file_exists(public_path('backend/assets/images/childcategory/' . $imageName))
            ) {
                unlink(public_path('backend/assets/images/childcategory/' . $imageName));
            }

            $imageName = time() . '.' . $request->image->extension();

            $request->image->move(
                public_path('backend/assets/images/childcategory'),
                $imageName
            );
        }

        $childcategory->update([
            'sub_category_id' => $subcategory->id,
            'name' => $request->name,
            'slug' => $request->slug
                ? Str::slug($request->slug)
                : Str::slug($request->name),
            'image' => $imageName,
            'status' => $request->status,
        ]);

        return redirect()
            ->route('childcategory.index')
            ->with('success', 'Child Category updated successfully');
    }

    /**
     * Delete child category.
     */
    public function destroy(ChildCategory $childcategory)
    {
        if ($childcategory->products()->exists()) {
            return redirect()
                ->route('childcategory.index')
                ->with('error', 'Cannot delete child category with associated products.');
        }

        if (
            $childcategory->image &&
            file_exists(public_path('backend/assets/images/childcategory/' . $childcategory->image))
        ) {
            unlink(public_path('backend/assets/images/childcategory/' . $childcategory->image));
        }

        $childcategory->delete();

        return redirect()
            ->route('childcategory.index')
            ->with('success', 'Child Category deleted successfully');
    }

    /**
     * Toggle status.
     */
    public function status($id)
    {
        $childcategory = ChildCategory::findOrFail($id);

        $childcategory->status = $childcategory->status == 1 ? 0 : 1;
        $childcategory->save();

        return redirect()->back()
            ->with('success', 'Child Category status updated successfully');
    }

    /**
     * Export CSV.
     */
    public function export(Request $request)
    {
        $query = ChildCategory::with(['subcategory.category']);

        if ($request->search) {
            $query->where('name', 'LIKE', '%' . $request->search . '%')
                ->orWhere('slug', 'LIKE', '%' . $request->search . '%');
        }

        if ($request->category_id) {
            $query->whereHas(
                'subcategory',
                fn($q) =>
                $q->where('category_id', $request->category_id)
            );
        }

        if ($request->sub_category_id) {
            $query->where('sub_category_id', $request->sub_category_id);
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->date) {
            $query->whereDate('created_at', $request->date);
        }

        $childCategories = $query->latest()->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=child_categories.csv',
        ];

        $callback = function () use ($childCategories) {
            $file = fopen('php://output', 'w');

            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'ID',
                'Category',
                'Sub Category',
                'Name',
                'Slug',
                'Status',
                'Created At'
            ]);

            foreach ($childCategories as $child) {
                fputcsv($file, [
                    $child->id,
                    $child->subcategory->category->name ?? '-',
                    $child->subcategory->name ?? '-',
                    $child->name,
                    $child->slug,
                    $child->status == 1 ? 'Active' : 'Inactive',
                    $child->created_at->format('d-m-Y H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Sample CSV.
     */
    public function importSample()
    {
        return $this->streamCsvDownload(
            'child_categories_import_sample.csv',
            $this->childCategoryExportHeaders(),
            function ($file) {
                fputcsv($file, [
                    'Seeds',
                    'Vegetable Seeds',
                    'Tomato Seeds',
                    'tomato-seeds',
                    'Active'
                ]);

                fputcsv($file, [
                    'Seeds',
                    'Flower Seeds',
                    'Rose Seeds',
                    'rose-seeds',
                    'Active'
                ]);
            }
        );
    }

    /**
     * Import CSV.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $parsed = $this->parseCsvFile($request->file('file'));

        if (empty($parsed['rows'])) {
            return back()->with('error', 'CSV file is empty or invalid.');
        }

        $imported = 0;
        $updated = 0;
        $errors = [];

        foreach ($parsed['rows'] as $index => $row) {
            $rowNumber = $index + 2;

            try {
                $categoryName = $this->csvValue($row, 'category');
                $subcategoryName = $this->csvValue($row, 'sub category')
                    ?: $this->csvValue($row, 'subcategory');
                $name = $this->csvValue($row, 'name');

                if (!$categoryName || !$subcategoryName || !$name) {
                    throw new \RuntimeException(
                        "Row {$rowNumber}: Category, Sub Category and Name are required."
                    );
                }

                $category = Category::where('name', $categoryName)
                    ->orWhere('slug', Str::slug($categoryName))
                    ->first();

                if (!$category) {
                    throw new \RuntimeException(
                        "Row {$rowNumber}: Category '{$categoryName}' not found."
                    );
                }

                $subcategory = SubCategory::where('category_id', $category->id)
                    ->where(function ($q) use ($subcategoryName) {
                        $q->where('name', $subcategoryName)
                            ->orWhere('slug', Str::slug($subcategoryName));
                    })->first();

                if (!$subcategory) {
                    throw new \RuntimeException(
                        "Row {$rowNumber}: Subcategory '{$subcategoryName}' not found."
                    );
                }

                $slug = $this->csvValue($row, 'slug') ?: Str::slug($name);
                $status = $this->normalizeStatusValue(
                    $this->csvValue($row, 'status'),
                    1
                );

                $existing = ChildCategory::where('slug', $slug)->first();

                $data = [
                    'sub_category_id' => $subcategory->id,
                    'name' => $name,
                    'slug' => $slug,
                    'status' => $status,
                ];

                if ($existing) {
                    $existing->update($data);
                    $updated++;
                } else {
                    ChildCategory::create($data);
                    $imported++;
                }
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }

        return redirect()
            ->route('childcategory.index')
            ->with('success', "Import complete: {$imported} created, {$updated} updated.")
            ->with('import_errors', $errors);
    }

    /**
     * Get subcategories by category for dependent dropdown.
     */
    public function getSubcategories($categoryId)
    {
        $subcategories = SubCategory::where('category_id', $categoryId)
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($subcategories);
    }
}
