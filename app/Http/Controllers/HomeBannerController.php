<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeBanner;
use Illuminate\Http\Request;

class HomeBannerController extends Controller
{
    public function index()
    {
        $banners = HomeBanner::orderBy('sort_order')->get();

        return view('admin.home-banner.index', compact('banners'));
    }

    public function update(Request $request)
    {
        foreach ($request->banner_ids as $id) {

            $banner = HomeBanner::find($id);

            if ($request->hasFile("image.$id")) {

                $image = $request->file("image.$id");

                $filename = time().'_'.$id.'.'.$image->getClientOriginalExtension();

                $image->move(
                    public_path('uploads/home-banners'),
                    $filename
                );

                $banner->image = 'uploads/home-banners/'.$filename;
                $banner->save();
            }
        }

        return redirect()->back()
            ->with('success', 'Home banners updated successfully.');
    }
}