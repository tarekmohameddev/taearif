<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User\Region;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    /**
     * Display a listing of regions.
     */
    public function index()
    {
        // Load all regions with their governorates
        $regions = Region::with('governorates')->get();
        return view('user.regions.index', compact('regions'));
    }

    /**
     * Show the form for creating a new region.
     */
    public function create()
    {
        return view('user.regions.create');
    }

    /**
     * Store a newly created region in storage.
     */
    public function store(Request $request)
    {
        // Validate request
        $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
        ]);

        Region::create($request->only('name_en', 'name_ar'));

        return redirect()->route('user.regions.index')
                         ->with('success', 'Region created successfully');
    }

    /**
     * Display the specified region.
     */
    public function show(Region $region)
    {
        return view('user.regions.show', compact('region'));
    }

    /**
     * Show the form for editing the specified region.
     */
    public function edit(Region $region)
    {
        return view('user.regions.edit', compact('region'));
    }

    /**
     * Update the specified region in storage.
     */
    public function update(Request $request, Region $region)
    {
        $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
        ]);

        $region->update($request->only('name_en', 'name_ar'));

        return redirect()->route('user.regions.index')
                         ->with('success', 'Region updated successfully');
    }

    /**
     * Remove the specified region from storage.
     */
    public function destroy(Region $region)
    {
        $region->delete();

        return redirect()->route('user.regions.index')
                         ->with('success', 'Region deleted successfully');
    }

    public function getGovernorates($region_id)
    {
        $governorates = \App\Models\User\Governorate::where('region_id', $region_id)->get();
        return response()->json($governorates);
    }

}
