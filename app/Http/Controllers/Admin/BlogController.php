<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $query = Blog::query();

        // Search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('category', 'like', '%' . $request->search . '%')
                  ->orWhere('author', 'like', '%' . $request->search . '%')
                  ->orWhere('short_description', 'like', '%' . $request->search . '%');
            });
        }

        // Category Filter
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Status Filter
        if ($request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Author Filter
        if ($request->filled('author')) {
            $query->where('author', 'like', '%' . $request->author . '%');
        }

        // Date Filter
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $blogs = $query->latest()->paginate(10);

        return view('admin.blog.index', compact('blogs'));
    }

    public function create()
    {
        return view('admin.blog.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'             => 'required',
            'slug'              => 'required|unique:blogs,slug',
            'category'          => 'nullable',
            'author'            => 'nullable',
            'blog_date'         => 'nullable|date',
            'short_description' => 'nullable',
            'description'       => 'nullable',
            'image'             => 'required|image|mimes:jpg,jpeg,png,webp',
            'status'            => 'required',
        ]);

        $image = '';

        if ($request->hasFile('image')) {
            $imageName = time() . '.' . $request->image->extension();
            $request->image->move(
                public_path('backend/assets/images/blog'),
                $imageName
            );
            $image = $imageName;
        }

        Blog::create([
            'title'             => $request->title,
            'slug'              => Str::slug($request->slug),
            'category'          => $request->category,
            'author'            => $request->author ?? auth()->user()->name ?? 'Admin',
            'blog_date'         => $request->blog_date ?? now(),
            'short_description' => $request->short_description,
            'description'       => $request->description,
            'image'             => $image,
            'status'            => $request->status,
        ]);

        return redirect()
            ->route('blog.index')
            ->with('success', 'Blog Added Successfully');
    }

    public function show(Blog $blog)
    {
        return view('admin.blog.show', compact('blog'));
    }

    public function edit(Blog $blog)
    {
        return view('admin.blog.edit', compact('blog'));
    }

    public function update(Request $request, Blog $blog)
    {
        $request->validate([
            'title'             => 'required',
            'slug'              => 'required|unique:blogs,slug,' . $blog->id,
            'category'          => 'nullable',
            'author'            => 'nullable',
            'blog_date'         => 'nullable|date',
            'short_description' => 'nullable',
            'description'       => 'nullable',
            'image'             => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'status'            => 'required',
        ]);

        $image = $blog->image;

        if ($request->hasFile('image')) {
            $oldPath = public_path('backend/assets/images/blog/' . $blog->image);
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
            $imageName = time() . '.' . $request->image->extension();
            $request->image->move(
                public_path('backend/assets/images/blog'),
                $imageName
            );
            $image = $imageName;
        }

        $blog->update([
            'title'             => $request->title,
            'slug'              => Str::slug($request->slug),
            'category'          => $request->category,
            'author'            => $request->author,
            'blog_date'         => $request->blog_date,
            'short_description' => $request->short_description,
            'description'       => $request->description,
            'image'             => $image,
            'status'            => $request->status,
        ]);

        return redirect()
            ->route('blog.index')
            ->with('success', 'Blog Updated Successfully');
    }

    public function destroy(Blog $blog)
    {
        $path = public_path('backend/assets/images/blog/' . $blog->image);
        if (file_exists($path)) {
            unlink($path);
        }

        $blog->delete();

        return redirect()
            ->route('blog.index')
            ->with('success', 'Blog Deleted Successfully');
    }

    public function status(Blog $blog)
    {
        $blog->status = $blog->status == 1 ? 0 : 1;
        $blog->save();

        return redirect()
            ->back()
            ->with('success', 'Blog status updated successfully');
    }

    public function export(Request $request)
    {
        $query = Blog::query();

        // Search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('category', 'like', '%' . $request->search . '%')
                  ->orWhere('author', 'like', '%' . $request->search . '%');
            });
        }

        // Category Filter
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Status Filter
        if ($request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Author Filter
        if ($request->filled('author')) {
            $query->where('author', 'like', '%' . $request->author . '%');
        }

        // Date Filter
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $blogs = $query->latest()->get();

        $fileName = 'blogs_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
        ];

        $callback = function () use ($blogs) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel compatibility
            fputs($file, "\xEF\xBB\xBF");
            
            fputcsv($file, [
                'ID',
                'Title',
                'Slug',
                'Category',
                'Author',
                'Blog Date',
                'Short Description',
                'Status',
                'Created At',
                'Updated At'
            ]);

            foreach ($blogs as $blog) {
                fputcsv($file, [
                    $blog->id,
                    $blog->title,
                    $blog->slug,
                    $blog->category ?? '-',
                    $blog->author ?? '-',
                    $blog->blog_date ? date('d-m-Y', strtotime($blog->blog_date)) : '-',
                    strip_tags($blog->short_description ?? '-'),
                    $blog->status == 1 ? 'Active' : 'Inactive',
                    $blog->created_at->format('d-m-Y H:i:s'),
                    $blog->updated_at->format('d-m-Y H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}