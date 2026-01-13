<?php

namespace App\Http\Controllers;

use App\Models\Report;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Reports/Index',[
            'reports' => Report::with('images')->latest()->get()
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
            'image' => 'required|image|max:10240',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);   
        
        $report=$request->user()->reports()->create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'status' => 'pending',
            'user_id' => auth()->id() ?? 1,
        ]);


        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('reports', 'public');
            // Ensure your Report model has: public function images() { return $this->hasMany(ReportImage::class); }
            $report->images()->create(['image_path' => $path]);
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
            'image' => 'nullable|image|max:10240',
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
        ]);

        $data = $request->only('title', 'description');
       

        if($request->hasFile('image')){
            $path = $request->file('image')->store('reports', 'public');
            $report->images()->create(['image_path' => $path]);
        }
        
        $report->update($data);
        
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
