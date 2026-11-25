<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BlogCategory;
use Illuminate\Support\Str;

class BlogCategoryController extends Controller
{
    public function index()
    {
        $categories = BlogCategory::latest()->paginate(10);
        return view('admin.blog_category.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.blog_category.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);

       BlogCategory::create([
    'name' => $request->name,
    'slug' => Str::slug($request->name),
    'status' => $request->status, // ✅ IMPORTANT FIX
]);

        return redirect()->route('blog-category.index')
            ->with('success', 'Category created successfully');
    }

    public function edit(BlogCategory $blogCategory)
    {
        return view('admin.blog_category.edit', compact('blogCategory'));
    }

    public function update(Request $request, BlogCategory $blogCategory)
    {
        $request->validate([
            'name' => 'required',
        ]);

       $blogCategory->update([
    'name' => $request->name,
    'slug' => Str::slug($request->name),
    'status' => $request->status, // ✅ IMPORTANT FIX
]);

        return redirect()->route('blog-category.index')
            ->with('success', 'Category updated successfully');
    }

    public function destroy(BlogCategory $blogCategory)
    {
        $blogCategory->delete();

        return back()->with('success', 'Category deleted successfully');
    }

    public function status(BlogCategory $blogCategory)
    {
        $blogCategory->status = !$blogCategory->status;
        $blogCategory->save();

        return back()->with('success', 'Status updated');
    }
}