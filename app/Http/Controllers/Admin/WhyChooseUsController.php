<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhyChooseUs;
use Illuminate\Http\Request;

class WhyChooseUsController extends Controller
{
    public function index()
    {
        $items = WhyChooseUs::orderBy('sort_order', 'asc')->orderBy('id', 'asc')->get();
        return view('admin.why-choose-us.index', compact('items'));
    }

    public function create()
    {
        return view('admin.why-choose-us.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:5120',
            'sort_order'  => 'nullable|integer|min:0',
            'status'      => 'required|in:0,1',
        ]);

        $imageName = null;
        if ($request->hasFile('image')) {
            $destinationPath = public_path('backend/assets/images/why-choose-us');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            $imageName = time() . '_' . uniqid() . '.' . $request->image->extension();
            $request->image->move($destinationPath, $imageName);
        }

        WhyChooseUs::create([
            'title'       => $request->title,
            'description' => $request->description,
            'image'       => $imageName,
            'sort_order'  => $request->sort_order ?? 0,
            'status'      => $request->status,
        ]);

        return redirect()
            ->route('admin.why-choose-us.index')
            ->with('success', 'Why Choose Us card added successfully.');
    }

    public function edit($id)
    {
        $item = WhyChooseUs::findOrFail($id);
        return view('admin.why-choose-us.edit', compact('item'));
    }

    public function update(Request $request, $id)
    {
        $item = WhyChooseUs::findOrFail($id);

        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:5120',
            'sort_order'  => 'nullable|integer|min:0',
            'status'      => 'required|in:0,1',
        ]);

        $imageName = $item->image;

        if ($request->hasFile('image')) {
            // Delete old image
            if ($item->image) {
                $oldPath = public_path('backend/assets/images/why-choose-us/' . $item->image);
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $destinationPath = public_path('backend/assets/images/why-choose-us');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            $imageName = time() . '_' . uniqid() . '.' . $request->image->extension();
            $request->image->move($destinationPath, $imageName);
        }

        $item->update([
            'title'       => $request->title,
            'description' => $request->description,
            'image'       => $imageName,
            'sort_order'  => $request->sort_order ?? 0,
            'status'      => $request->status,
        ]);

        return redirect()
            ->route('admin.why-choose-us.index')
            ->with('success', 'Why Choose Us card updated successfully.');
    }

    public function destroy($id)
    {
        $item = WhyChooseUs::findOrFail($id);

        if ($item->image) {
            $path = public_path('backend/assets/images/why-choose-us/' . $item->image);
            if (file_exists($path)) {
                @unlink($path);
            }
        }

        $item->delete();

        return redirect()
            ->route('admin.why-choose-us.index')
            ->with('success', 'Why Choose Us card deleted successfully.');
    }

    public function status($id)
    {
        $item = WhyChooseUs::findOrFail($id);
        $item->status = $item->status == 1 ? 0 : 1;
        $item->save();

        return redirect()
            ->back()
            ->with('success', 'Status updated successfully.');
    }
}
