<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubCategoryController extends Controller
{
   public function index(Request $request)
{
    $query = SubCategory::with('category');

    // Search
    if ($request->search) {

        $query->where(function ($q) use ($request) {

            $q->where('name', 'LIKE', '%' . $request->search . '%')
              ->orWhere('slug', 'LIKE', '%' . $request->search . '%');

        });
    }

    // Category Filter - FIXED: changed from $request->category to $request->category_id
    if ($request->category_id) {

        $query->where('category_id', $request->category_id);
    }

    // Status Filter
    if ($request->status !== null && $request->status !== '') {

        $query->where('status', $request->status);
    }

    // Date Filter
    if ($request->date) {

        $query->whereDate('created_at', $request->date);
    }

    $subcategories = $query
        ->latest()
        ->paginate(30);

    $categories = Category::where('status', 1)->get();

    return view(
        'admin.subcategory.index',
        compact('subcategories', 'categories')
    );
}

public function export(Request $request)
{
    $query = SubCategory::with('category');

    // Search
    if ($request->search) {

        $query->where(function ($q) use ($request) {

            $q->where('name', 'LIKE', '%' . $request->search . '%')
              ->orWhere('slug', 'LIKE', '%' . $request->search . '%');

        });
    }

    // Category Filter - FIXED: changed from $request->category to $request->category_id
    if ($request->category_id) {

        $query->where('category_id', $request->category_id);
    }

    // Status Filter
    if ($request->status !== null && $request->status !== '') {

        $query->where('status', $request->status);
    }

    // Date Filter
    if ($request->date) {

        $query->whereDate('created_at', $request->date);
    }

    $subcategories = $query->latest()->get();

    $fileName = 'subcategories.csv';

    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename=$fileName",
    ];

    $callback = function () use ($subcategories) {

        $file = fopen('php://output', 'w');

        fputcsv($file, [
            'ID',
            'Category',
            'Name',
            'Slug',
            'Status',
            'Created At'
        ]);

        foreach ($subcategories as $subcategory) {

            fputcsv($file, [
                $subcategory->id,
                $subcategory->category->name ?? '-',
                $subcategory->name,
                $subcategory->slug,
                $subcategory->status == 1 ? 'Active' : 'Inactive',
                $subcategory->created_at->format('d-m-Y'),
            ]);
        }

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

    public function create()
    {
        $categories = Category::where('status', 1)->get();

        return view('admin.subcategory.add', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required',
            'name'        => 'required',
            'slug'        => 'required|unique:sub_categories,slug',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'status'      => 'required'
        ]);

        $imageName = null;

        if ($request->hasFile('image')) {

            $imageName = time() . '.' . $request->image->extension();

            $request->image->move(
                public_path('backend/assets/images/subcategory'),
                $imageName
            );
        }

        SubCategory::create([
            'category_id' => $request->category_id,
            'name'        => $request->name,
            'slug'        => Str::slug($request->slug),
            'image'       => $imageName,
            'status'      => $request->status,
        ]);

        return redirect()
            ->route('subcategory.index')
            ->with('success', 'Sub Category Added Successfully');
    }

    public function edit(SubCategory $subcategory)
    {
        $categories = Category::where('status', 1)->get();

        return view(
            'admin.subcategory.edit',
            compact('subcategory', 'categories')
        );
    }

    public function update(Request $request, SubCategory $subcategory)
    {
        $request->validate([
            'category_id' => 'required',
            'name'        => 'required',
            'slug'        => 'required|unique:sub_categories,slug,' . $subcategory->id,
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'status'      => 'required'
        ]);

        $imageName = $subcategory->image;

        if ($request->hasFile('image')) {

            if (
                $subcategory->image &&
                file_exists(public_path('backend/assets/images/subcategory/' . $subcategory->image))
            ) {
                unlink(public_path('backend/assets/images/subcategory/' . $subcategory->image));
            }

            $imageName = time() . '.' . $request->image->extension();

            $request->image->move(
                public_path('backend/assets/images/subcategory'),
                $imageName
            );
        }

        $subcategory->update([
            'category_id' => $request->category_id,
            'name'        => $request->name,
            'slug'        => Str::slug($request->slug),
            'image'       => $imageName,
            'status'      => $request->status,
        ]);

        return redirect()
            ->route('subcategory.index')
            ->with('success', 'Sub Category Updated Successfully');
    }

    public function destroy(SubCategory $subcategory)
    {
        if (
            $subcategory->image &&
            file_exists(public_path('backend/assets/images/subcategory/' . $subcategory->image))
        ) {
            unlink(public_path('backend/assets/images/subcategory/' . $subcategory->image));
        }

        $subcategory->delete();

        return redirect()
            ->route('subcategory.index')
            ->with('success', 'Sub Category Deleted Successfully');
    }
    
    public function status($id)
    {
        $subcategory = SubCategory::findOrFail($id);

        $subcategory->status = $subcategory->status == 1 ? 0 : 1;

        $subcategory->save();

        return redirect()->back()
            ->with('success', 'Sub category status updated successfully');
    }
}