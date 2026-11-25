<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Slider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SliderController extends Controller
{
    /**
     * Ensure mobile_image and sort_order columns exist on the sliders table.
     */
    protected function ensureColumnsExist(): void
    {
        if (!Schema::hasColumn('sliders', 'mobile_image') || !Schema::hasColumn('sliders', 'sort_order')) {
            Schema::table('sliders', function (Blueprint $table) {
                if (!Schema::hasColumn('sliders', 'mobile_image')) {
                    $table->string('mobile_image')->nullable()->after('image');
                }
                if (!Schema::hasColumn('sliders', 'sort_order')) {
                    $table->integer('sort_order')->default(0)->after('status');
                }
            });
        }
    }

    public function index(Request $request)
    {
        $this->ensureColumnsExist();

        $query = Slider::query();

        // Search
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('description', 'LIKE', '%' . $request->search . '%');
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

        $sliders = $query->orderBy('sort_order', 'asc')->latest()->paginate(10);

        return view('admin.slider.index', compact('sliders'));
    }

    public function create()
    {
        $this->ensureColumnsExist();
        return view('admin.slider.add');
    }

    public function store(Request $request)
    {
        $this->ensureColumnsExist();

        $request->validate([
            'title'        => 'nullable|string|max:255',
            'link'         => 'nullable|url',
            'image'        => 'required|image|mimes:jpg,jpeg,png,webp',
            'mobile_image' => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'sort_order'   => 'nullable|integer',
            'status'       => 'required',
        ]);

        $imageName = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . rand(111, 999) . '.' . $image->extension();
            $image->move(
                public_path('backend/assets/images/slider'),
                $imageName
            );
        }

        $mobileImageName = null;
        if ($request->hasFile('mobile_image')) {
            $mobileImage = $request->file('mobile_image');
            $mobileImageName = time() . '_mob_' . rand(111, 999) . '.' . $mobileImage->extension();
            $mobileImage->move(
                public_path('backend/assets/images/slider'),
                $mobileImageName
            );
        }

        Slider::create([
            'title'        => $request->title,
            'description'  => $request->description,
            'link'         => $request->link,
            'image'        => $imageName,
            'mobile_image' => $mobileImageName,
            'sort_order'   => $request->sort_order ?? 0,
            'status'       => $request->status,
        ]);

        return redirect()
            ->route('slider.index')
            ->with('success', 'Slider Added Successfully');
    }

    public function edit(Slider $slider)
    {
        $this->ensureColumnsExist();
        return view('admin.slider.edit', compact('slider'));
    }

    public function update(Request $request, Slider $slider)
    {
        $this->ensureColumnsExist();

        $request->validate([
            'title'        => 'nullable|string|max:255',
            'link'         => 'nullable|url',
            'image'        => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'mobile_image' => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'sort_order'   => 'nullable|integer',
            'status'       => 'required',
        ]);

        $imageName = $slider->image;

        if ($request->hasFile('image')) {
            if ($slider->image) {
                $oldPath = public_path('backend/assets/images/slider/' . $slider->image);
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $image = $request->file('image');
            $imageName = time() . rand(111, 999) . '.' . $image->extension();
            $image->move(
                public_path('backend/assets/images/slider'),
                $imageName
            );
        }

        $mobileImageName = $slider->mobile_image;

        if ($request->hasFile('mobile_image')) {
            if ($slider->mobile_image) {
                $oldMobilePath = public_path('backend/assets/images/slider/' . $slider->mobile_image);
                if (file_exists($oldMobilePath)) {
                    @unlink($oldMobilePath);
                }
            }

            $mobileImage = $request->file('mobile_image');
            $mobileImageName = time() . '_mob_' . rand(111, 999) . '.' . $mobileImage->extension();
            $mobileImage->move(
                public_path('backend/assets/images/slider'),
                $mobileImageName
            );
        }

        $slider->update([
            'title'        => $request->title,
            'description'  => $request->description,
            'link'         => $request->link,
            'image'        => $imageName,
            'mobile_image' => $mobileImageName,
            'sort_order'   => $request->sort_order ?? 0,
            'status'       => $request->status,
        ]);

        return redirect()
            ->route('slider.index')
            ->with('success', 'Slider Updated Successfully');
    }

    public function destroy(Slider $slider)
    {
        if ($slider->image) {
            $path = public_path('backend/assets/images/slider/' . $slider->image);
            if (file_exists($path)) {
                @unlink($path);
            }
        }

        if ($slider->mobile_image) {
            $mobPath = public_path('backend/assets/images/slider/' . $slider->mobile_image);
            if (file_exists($mobPath)) {
                @unlink($mobPath);
            }
        }

        $slider->delete();

        return redirect()
            ->route('slider.index')
            ->with('success', 'Slider Deleted Successfully');
    }

    public function toggleStatus(Slider $slider)
    {
        $slider->status = $slider->status == 1 ? 0 : 1;
        $slider->save();

        return back()->with('success', 'Slider status updated successfully');
    }

    public function duplicate($slider)
    {
        $this->ensureColumnsExist();

        $slider = $slider instanceof Slider ? $slider : Slider::findOrFail($slider);

        $newSlider = $slider->replicate();

        if ($slider->title) {
            $newSlider->title = $slider->title . ' (Copy)';
        }

        // Duplicate the desktop image file if exists
        if ($slider->image) {
            $sourcePath = public_path('backend/assets/images/slider/' . $slider->image);
            if (file_exists($sourcePath)) {
                $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
                $newImageName = time() . rand(111, 999) . '.' . $extension;
                $destinationPath = public_path('backend/assets/images/slider/' . $newImageName);
                if (copy($sourcePath, $destinationPath)) {
                    $newSlider->image = $newImageName;
                }
            }
        }

        // Duplicate the mobile image file if exists
        if ($slider->mobile_image) {
            $sourceMobPath = public_path('backend/assets/images/slider/' . $slider->mobile_image);
            if (file_exists($sourceMobPath)) {
                $mobExtension = pathinfo($sourceMobPath, PATHINFO_EXTENSION);
                $newMobImageName = time() . '_mob_' . rand(111, 999) . '.' . $mobExtension;
                $destMobPath = public_path('backend/assets/images/slider/' . $newMobImageName);
                if (copy($sourceMobPath, $destMobPath)) {
                    $newSlider->mobile_image = $newMobImageName;
                }
            }
        }

        $newSlider->save();

        return redirect()->back()->with('success', 'Slider duplicated successfully!');
    }

    public function export(Request $request)
    {
        $this->ensureColumnsExist();

        $query = Slider::query();

        // Search
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('description', 'LIKE', '%' . $request->search . '%');
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

        $sliders = $query->orderBy('sort_order', 'asc')->latest()->get();

        $fileName = 'sliders.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
        ];

        $callback = function () use ($sliders) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'ID',
                'Title',
                'Desktop Image',
                'Mobile Image',
                'Sort Order',
                'Status',
                'Created At'
            ]);

            foreach ($sliders as $slider) {
                fputcsv($file, [
                    $slider->id,
                    $slider->title ?? '-',
                    $slider->image,
                    $slider->mobile_image ?? '-',
                    $slider->sort_order ?? 0,
                    $slider->status == 1 ? 'Active' : 'Inactive',
                    $slider->created_at->format('d-m-Y'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
