<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Benefit;
use Illuminate\Http\Request;

class BenefitController extends Controller
{

    public function index()
    {
        $benefits = Benefit::latest()->get();
        return view('admin.benefits.index', compact('benefits'));
    }

    public function edit($id)
    {
        $benefit = Benefit::findOrFail($id);
        return view('admin.benefits.edit', compact('benefit'));
    }

    public function update(Request $request, $id)
    {
        $benefit = Benefit::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'svg_logo' => 'nullable|string',
            // Extension check only — SVG MIME types vary (image/svg+xml, text/plain, etc.)
            'svg_logo_file' => 'nullable|file|extensions:svg|max:2048',
            'status' => 'required|in:0,1',
        ]);

        $svgLogo = $request->svg_logo;

        // Prefer uploaded SVG file contents when provided
        if ($request->hasFile('svg_logo_file')) {
            $file = $request->file('svg_logo_file');
            $svgLogo = file_get_contents($file->getRealPath());

            if (!str_contains(strtolower($svgLogo), '<svg')) {
                return back()
                    ->withInput()
                    ->withErrors(['svg_logo_file' => 'The uploaded file does not contain valid SVG markup.']);
            }
        }

        // Keep existing SVG if neither textarea nor file was provided
        if ($svgLogo === null || trim((string) $svgLogo) === '') {
            $svgLogo = $benefit->svg_logo;
        }

        $benefit->update([
            'title' => $request->title,
            'description' => $request->description,
            'svg_logo' => $svgLogo,
            'status' => $request->status,
        ]);

        return redirect()
            ->route('benefits.index')
            ->with('success', 'Benefit Updated');
    }
}
