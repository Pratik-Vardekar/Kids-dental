<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubCategory;
use App\Models\Tutorial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class TutorialController extends Controller
{
    /**
     * Display a listing of Subcategory-wise Tutorials.
     */
    public function index(Request $request)
    {
        $subcategoryId = $request->input('sub_category_id');
        $search = trim((string) $request->input('search', ''));

        // Dynamically fetch all active subcategories
        $subcategories = SubCategory::with('category')
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        if ($subcategories->isEmpty()) {
            $subcategories = SubCategory::with('category')->orderBy('name')->get();
        }

        $selectedSubcategory = $subcategoryId ? $subcategories->firstWhere('id', (int) $subcategoryId) : null;

        $tutorialsQuery = Tutorial::with(['subCategory.category']);

        if (!empty($subcategoryId)) {
            $tutorialsQuery->where('sub_category_id', $subcategoryId);
        }

        if (!empty($search)) {
            $tutorialsQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('video_url', 'like', "%{$search}%");
            });
        }

        $tutorials = $tutorialsQuery->orderBy('sort_order', 'asc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Calculate counts per subcategory for filter
        $rawCounts = Tutorial::selectRaw('sub_category_id, count(*) as count')
            ->groupBy('sub_category_id')
            ->pluck('count', 'sub_category_id');

        $subcategoryCounts = [];
        foreach ($subcategories as $sub) {
            $subcategoryCounts[$sub->id] = $rawCounts[$sub->id] ?? 0;
        }

        $totalTutorialsCount = Tutorial::count();

        return view('admin.tutorials.index', compact(
            'tutorials',
            'subcategories',
            'selectedSubcategory',
            'subcategoryId',
            'search',
            'subcategoryCounts',
            'totalTutorialsCount'
        ));
    }

    /**
     * Show the form for creating a new Tutorial.
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

        $selectedSubcategoryId = $request->input('sub_category_id');

        return view('admin.tutorials.create', compact('subcategories', 'selectedSubcategoryId'));
    }

    /**
     * Store a newly created Tutorial in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'sub_category_id' => 'required|exists:sub_categories,id',
            'title'           => 'required|string|max:255',
            'video_url'       => 'nullable|string|max:1000',
            'video_file'      => 'nullable|file|mimes:mp4,mov,ogg,qt,webm,mkv|max:153600',
            'description'     => 'nullable|string',
            'thumbnail'       => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:4096',
            'sort_order'      => 'nullable|integer|min:0',
            'status'          => 'required|in:0,1',
        ]);

        if (!$request->filled('video_url') && !$request->hasFile('video_file')) {
            return back()
                ->withErrors(['video_url' => 'Please provide either a Video URL or choose a Video file to upload.'])
                ->withInput();
        }

        $videoFileName = null;
        if ($request->hasFile('video_file')) {
            $videoDestPath = public_path('backend/assets/videos/tutorials');
            if (!file_exists($videoDestPath)) {
                mkdir($videoDestPath, 0755, true);
            }
            $videoFileName = time() . '_' . uniqid() . '.' . $request->video_file->extension();
            $request->video_file->move($videoDestPath, $videoFileName);
        }

        $videoUrl = $request->video_url;
        if (empty($videoUrl) && !empty($videoFileName)) {
            $videoUrl = 'backend/assets/videos/tutorials/' . $videoFileName;
        }

        $thumbnailName = null;
        if ($request->hasFile('thumbnail')) {
            $destinationPath = public_path('backend/assets/images/tutorials');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            $thumbnailName = time() . '_' . uniqid() . '.' . $request->thumbnail->extension();
            $request->thumbnail->move($destinationPath, $thumbnailName);
        }

        $videoId = Tutorial::extractYoutubeId($videoUrl);

        $data = [
            'sub_category_id' => $request->sub_category_id,
            'title'           => $request->title,
            'video_url'       => $videoUrl,
            'video_file'      => $videoFileName,
            'video_id'        => $videoId,
            'thumbnail'       => $thumbnailName,
            'description'     => $request->description,
            'sort_order'      => $request->sort_order ?? 0,
            'status'          => $request->status,
        ];

        Tutorial::create($data);

        return redirect()
            ->route('admin.tutorials.index', ['sub_category_id' => $request->sub_category_id])
            ->with('success', 'Tutorial video added successfully.');
    }

    /**
     * Show the form for editing the specified Tutorial.
     */
    public function edit(Tutorial $tutorial)
    {
        $subcategories = SubCategory::with('category')
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        if ($subcategories->isEmpty()) {
            $subcategories = SubCategory::with('category')->orderBy('name')->get();
        }

        return view('admin.tutorials.edit', compact('tutorial', 'subcategories'));
    }

    /**
     * Update the specified Tutorial in storage.
     */
    public function update(Request $request, Tutorial $tutorial)
    {
        $request->validate([
            'sub_category_id' => 'required|exists:sub_categories,id',
            'title'           => 'required|string|max:255',
            'video_url'       => 'nullable|string|max:1000',
            'video_file'      => 'nullable|file|mimes:mp4,mov,ogg,qt,webm,mkv|max:153600',
            'description'     => 'nullable|string',
            'thumbnail'       => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:4096',
            'sort_order'      => 'nullable|integer|min:0',
            'status'          => 'required|in:0,1',
        ]);

        if (!$request->filled('video_url') && !$request->hasFile('video_file') && empty($tutorial->video_file) && empty($tutorial->video_url)) {
            return back()
                ->withErrors(['video_url' => 'Please provide either a Video URL or choose a Video file to upload.'])
                ->withInput();
        }

        $videoFileName = $tutorial->video_file;
        if ($request->hasFile('video_file')) {
            $videoDestPath = public_path('backend/assets/videos/tutorials');
            if (!file_exists($videoDestPath)) {
                mkdir($videoDestPath, 0755, true);
            }

            // Remove old video file if exists
            if (!empty($tutorial->video_file) && file_exists(public_path('backend/assets/videos/tutorials/' . $tutorial->video_file))) {
                @unlink(public_path('backend/assets/videos/tutorials/' . $tutorial->video_file));
            }

            $videoFileName = time() . '_' . uniqid() . '.' . $request->video_file->extension();
            $request->video_file->move($videoDestPath, $videoFileName);
        }

        $videoUrl = $request->video_url;
        if (empty($videoUrl)) {
            if (!empty($videoFileName)) {
                $videoUrl = 'backend/assets/videos/tutorials/' . $videoFileName;
            } else {
                $videoUrl = $tutorial->video_url;
            }
        }

        $thumbnailName = $tutorial->thumbnail;
        if ($request->hasFile('thumbnail')) {
            $destinationPath = public_path('backend/assets/images/tutorials');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            // Remove old thumbnail if exists
            if (!empty($tutorial->thumbnail) && file_exists(public_path('backend/assets/images/tutorials/' . $tutorial->thumbnail))) {
                @unlink(public_path('backend/assets/images/tutorials/' . $tutorial->thumbnail));
            }

            $thumbnailName = time() . '_' . uniqid() . '.' . $request->thumbnail->extension();
            $request->thumbnail->move($destinationPath, $thumbnailName);
        }

        $videoId = Tutorial::extractYoutubeId($videoUrl);

        $tutorial->update([
            'sub_category_id' => $request->sub_category_id,
            'title'           => $request->title,
            'video_url'       => $videoUrl,
            'video_file'      => $videoFileName,
            'video_id'        => $videoId,
            'thumbnail'       => $thumbnailName,
            'description'     => $request->description,
            'sort_order'      => $request->sort_order ?? 0,
            'status'          => $request->status,
        ]);

        return redirect()
            ->route('admin.tutorials.index', ['sub_category_id' => $request->sub_category_id])
            ->with('success', 'Tutorial video updated successfully.');
    }

    /**
     * Remove the specified Tutorial from storage.
     */
    public function destroy(Tutorial $tutorial)
    {
        $subcategoryId = $tutorial->sub_category_id;

        if (!empty($tutorial->video_file) && file_exists(public_path('backend/assets/videos/tutorials/' . $tutorial->video_file))) {
            @unlink(public_path('backend/assets/videos/tutorials/' . $tutorial->video_file));
        }

        if (!empty($tutorial->thumbnail) && file_exists(public_path('backend/assets/images/tutorials/' . $tutorial->thumbnail))) {
            @unlink(public_path('backend/assets/images/tutorials/' . $tutorial->thumbnail));
        }

        $tutorial->delete();

        return redirect()
            ->route('admin.tutorials.index', ['sub_category_id' => $subcategoryId])
            ->with('success', 'Tutorial video deleted successfully.');
    }
}
