<?php

namespace App\Http\Controllers;

use App\Models\Report;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Reports/Index',[
            'reports' => Report::with('images')->get()->map(function($report){
                return [
                    'id' => $report->id,
                    'title' => $report->title,
                    'description' => $report->description,
                    'status' => $report->status,
                    'latitude' => (float)$report->latitude,
                    'longitude' => (float)$report->longitude,
                    'images' => $report->images,
                    'can_edit' => auth()->user() ?->can('update', $report),
                    'can_delete' => auth()->user() ?->can('delete', $report),
                    'created_at' => $report->created_at->toDateTimeString(),
                ];
            })
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'images' => 'required|array',
            'images.*' => 'image|max:10240',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);   
        
        $report=$request->user()->reports()->create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'status' => 'pending',
            
        ]);


        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('reports', 'public');
                $report->images()->create(['image_path' => $path]);
            }
        }


        
        

      
        return redirect()->back()->with('message', 'Report submitted successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Report $report)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Report $report)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Report $report)
    {

    

        $validated = $request->validate([
            'images' => 'nullable|array',
            'images.*' => 'image|max:10240',
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
        ]);

        $report->update(Arr::except($validated, ['images']));
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('reports', 'public');
                $report->images()->create(['image_path' => $path]);
            }
        }
        
        return redirect()->back()->with('message', 'Report updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Report $report)
    {
        $report->delete();
        return redirect()->back()->with('message', 'Report deleted successfully!');
    }
}
