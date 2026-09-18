<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Slider;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SliderController extends Controller
{
    public function index()
    {
        $sliders = Slider::latest()->paginate(15)->withQueryString();

        return Inertia::render('Admin/Sliders/Index', compact('sliders'));
    }

    public function create()
    {
        return Inertia::render('Admin/Sliders/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'sub_title' => 'nullable|string|max:255',
            'button_text' => 'nullable|string|max:255',
            'button_link' => 'nullable|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $file = $request->file('image');
        $filename = time().'_'.$file->getClientOriginalName();
        $file->move(public_path('images/sliders'), $filename);
        $validated['image'] = 'images/sliders/'.$filename;

        Slider::create($validated);

        return redirect()->route('admin.sliders.index')
            ->with('success', 'Hero slider added successfully.');
    }

    public function edit(Slider $slider)
    {
        return Inertia::render('Admin/Sliders/Edit', compact('slider'));
    }

    public function update(Request $request, Slider $slider)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'sub_title' => 'nullable|string|max:255',
            'button_text' => 'nullable|string|max:255',
            'button_link' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($slider->image && file_exists(public_path($slider->image))) {
                unlink(public_path($slider->image));
            }
            $file = $request->file('image');
            $filename = time().'_'.$file->getClientOriginalName();
            $file->move(public_path('images/sliders'), $filename);
            $validated['image'] = 'images/sliders/'.$filename;
        } else {
            unset($validated['image']);
        }

        $slider->update($validated);

        return redirect()->route('admin.sliders.index')
            ->with('success', 'Hero slider updated successfully.');
    }

    public function destroy(Slider $slider)
    {
        if ($slider->image && file_exists(public_path($slider->image))) {
            unlink(public_path($slider->image));
        }

        $slider->delete();

        return redirect()->route('admin.sliders.index')
            ->with('success', 'Hero slider deleted successfully.');
    }
}
