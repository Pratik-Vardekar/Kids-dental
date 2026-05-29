<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
  public function index(Request $request)
{
    $query = Category::query();

    // Search
    if ($request->search) {

        $query->where(function ($q) use ($request) {

            $q->where('name', 'LIKE', '%' . $request->search . '%')
              ->orWhere('slug', 'LIKE', '%' . $request->search . '%');

        });
    }

    // Status Filter
    if ($request->status !== null && $request->status !== '') {

        $query->where('status', $request->status);
    }

    // Date Filter
    if ($request->date) {

        $query->whereDate('created_at', $request->date);
    }

    $categories = $query->latest()->paginate(10);

    return view('admin.category.index', compact('categories'));
}
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.category.add');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    $request->validate([
        'name'   => 'required|string|max:255',
        'slug'   => 'nullable|string|max:255|unique:categories,slug',
        'image'  => 'nullable|image|mimes:jpg,jpeg,png,webp',
        'status' => 'required|in:0,1',
    ],[
        'slug.unique' => 'Category slug already exists',
    ]);

    $imageName = null;

    if ($request->hasFile('image')) {

        $imageName = time() . '.' . $request->image->extension();

        $request->image->move(
            public_path('backend/assets/images/category'),
            $imageName
        );
    }

    Category::create([
        'name'   => $request->name,
        'slug'   => $request->slug ?? Str::slug($request->name),
        'image'  => $imageName,
        'status' => $request->status,
    ]);

    return redirect()
        ->route('category.index')
        ->with('success', 'Category added successfully');
}

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        return view('admin.category.show', compact('category'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        return view('admin.category.edit', compact('category'));
    }

    /**
     * Update the specified resource in storage.
     */
   public function update(Request $request, Category $category)
{
    $request->validate([
        'name'   => 'required|string|max:255',
        'slug'   => 'nullable|string|max:255|unique:categories,slug,' . $category->id,
        'image'  => 'nullable|image|mimes:jpg,jpeg,png,webp',
        'status' => 'required|in:0,1',
    ],[
        'slug.unique' => 'Category slug already exists',
    ]);

    $imageName = $category->image;

    if ($request->hasFile('image')) {

        if (
            $category->image &&
            file_exists(public_path('backend/assets/images/category/' . $category->image))
        ) {
            unlink(public_path('backend/assets/images/category/' . $category->image));
        }

        $imageName = time() . '.' . $request->image->extension();

        $request->image->move(
            public_path('backend/assets/images/category'),
            $imageName
        );
    }

    $category->update([
        'name'   => $request->name,
        'slug'   => $request->slug ?? Str::slug($request->name),
        'image'  => $imageName,
        'status' => $request->status,
    ]);

    return redirect()
        ->route('category.index')
        ->with('success', 'Category updated successfully');
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        if ($category->image && file_exists(public_path('backend/assets/images/category/' . $category->image))) {
            unlink(public_path('backend/assets/images/category/' . $category->image));
        }

        $category->delete();

        return redirect()
            ->route('category.index')
            ->with('success', 'Category deleted successfully');
    }
    public function status($id)
{
    $category = Category::findOrFail($id);

    $category->status = $category->status == 1 ? 0 : 1;

    $category->save();

    return redirect()->back()
        ->with('success', 'Category status updated successfully');
}
public function export(Request $request)
{
    $query = Category::query();

    // Search
    if ($request->search) {

        $query->where('name', 'LIKE', '%' . $request->search . '%')
              ->orWhere('slug', 'LIKE', '%' . $request->search . '%');
    }

    // Status
    if ($request->status != '') {

        $query->where('status', $request->status);
    }

    // Date
    if ($request->date) {

        $query->whereDate('created_at', $request->date);
    }

$categories = $query->latest()->paginate(10);
    $fileName = 'categories.csv';

    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename=$fileName",
    ];

    $callback = function () use ($categories) {

        $file = fopen('php://output', 'w');

        fputcsv($file, [
            'ID',
            'Name',
            'Slug',
            'Status',
            'Created At'
        ]);

        foreach ($categories as $category) {

            fputcsv($file, [
                $category->id,
                $category->name,
                $category->slug,
                $category->status == 1 ? 'Active' : 'Inactive',
                $category->created_at->format('d-m-Y'),
            ]);
        }

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}
}