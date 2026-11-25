<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlayFunSection;
use Illuminate\Http\Request;

class PlayFunSectionController extends Controller
{
    public function edit()
    {
        $section = PlayFunSection::firstOrCreate([], [
            'tagline' => 'Play. Learn. Grow.',
            'headline' => 'Fun-filled toys that spark creativity and imagination.',
            'button_text' => 'Explore Products',
            'button_url' => '#',
            'stats' => [
                ['value' => '5K+', 'label' => 'Happy Little Customers'],
                ['value' => '1000+', 'label' => 'Fun & Educational Toys'],
                ['value' => '23+', 'label' => 'Years Creating Happy Playtime'],
                ['value' => '25+', 'label' => 'Cities Served'],
            ],
        ]);

        return view('admin.play-fun-section.edit', compact('section'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'tagline' => 'nullable|string|max:255',
            'headline' => 'nullable|string',
            'button_text' => 'nullable|string|max:100',
            'button_url' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'stats' => 'nullable|array',
            'stats.*.value' => 'nullable|string|max:50',
            'stats.*.label' => 'nullable|string|max:255',
        ]);

        $section = PlayFunSection::firstOrCreate([]);

        $data = $request->only([
            'tagline',
            'headline',
            'button_text',
            'button_url',
        ]);

        $data['stats'] = array_values($request->input('stats', []));

        if ($request->hasFile('image')) {
            $dir = public_path('backend/assets/images/play-fun');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            if ($section->image && file_exists(public_path($section->image))) {
                @unlink(public_path($section->image));
            }

            $file = $request->file('image');
            $name = time() . '_playfun.' . $file->getClientOriginalExtension();
            $file->move($dir, $name);
            $data['image'] = 'backend/assets/images/play-fun/' . $name;
        }

        $section->update($data);

        return back()->with('success', 'Play Fun section updated successfully.');
    }
}
