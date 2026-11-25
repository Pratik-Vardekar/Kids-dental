<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HelpCategoryController extends Controller
{
    /**
     * Redirect directly to Category FAQs since categories are dynamically fetched from store SubCategories.
     */
    public function index()
    {
        return redirect()->route('admin.help-faqs.index')
            ->with('info', 'FAQ categories are dynamically fetched from your store SubCategories. You do not need to create categories manually.');
    }

    public function create()
    {
        return redirect()->route('admin.help-faqs.create');
    }

    public function store(Request $request)
    {
        return redirect()->route('admin.help-faqs.index');
    }

    public function edit($id)
    {
        return redirect()->route('admin.help-faqs.index');
    }

    public function update(Request $request, $id)
    {
        return redirect()->route('admin.help-faqs.index');
    }

    public function destroy($id)
    {
        return redirect()->route('admin.help-faqs.index');
    }

    public function autoSeedDefaultCategories(): void
    {
        // Not needed since subcategories are dynamically loaded
    }
}
