<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use Illuminate\Support\Facades\Log;

use App\Services\AuditService;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        Log::info('Fetching all services');
        
        // If 'active_only' query param is present, filter by active status
        if ($request->has('active_only') && $request->active_only == 'true') {
            $services = Service::where('is_active', true)->get();
        } else {
            $services = Service::all();
        }
        
        return response()->json(['success' => true, 'data' => $services]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'category' => 'required|string',
            'price' => 'required|numeric',
            'duration' => 'required|string',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_active' => 'boolean',
        ]);

        $data = $request->all();

        if ($request->hasFile('image')) {
            try {
                $file = $request->file('image');
                // Use Laravel Storage to maintain consistency with Cleaner uploads
                // Path: uploads/services/{filename}
                $path = $file->store('uploads/services', 'public');
                
                Log::info("Service image uploaded successfully: {$path}");
                
                // Save path compatible with storage link
                // The processImageUrl helper in CleanerDashboardController handles /storage/ prefix
                // so we can store it with or without. Storing WITH /storage/ is easier for direct frontend usage.
                $data['image'] = '/storage/' . $path;
            } catch (\Exception $e) {
                Log::error('Image upload failed: ' . $e->getMessage());
                return response()->json(['success' => false, 'message' => 'Image upload failed'], 500);
            }
        } else {
            // Explicitly set image to null if no file is uploaded during creation
            // Or use a default placeholder path
            $data['image'] = null;
        }

        $service = Service::create($data);
        
        AuditService::log('created', 'Service', $service->id, $service->toArray());

        return response()->json(['success' => true, 'data' => $service], 201);
    }

    public function show($id)
    {
        $service = Service::find($id);
        if (!$service) {
            return response()->json(['success' => false, 'message' => 'Service not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $service]);
    }

    public function update(Request $request, $id)
    {
        $service = Service::find($id);
        if (!$service) {
            return response()->json(['success' => false, 'message' => 'Service not found'], 404);
        }

        $request->validate([
            'title' => 'sometimes|string',
            'category' => 'sometimes|string',
            'price' => 'sometimes|numeric',
            'duration' => 'sometimes|string',
            'description' => 'nullable|string',
            'image' => 'nullable',
            'is_active' => 'boolean',
        ]);

        $data = $request->all();

        if ($request->hasFile('image')) {
            $request->validate([
                'image' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            ]);
            
            try {
                // Delete old image if it exists and is in storage
                if ($service->image && str_contains($service->image, '/storage/')) {
                    $oldPath = str_replace('/storage/', '', $service->image);
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
                }
                
                $file = $request->file('image');
                $path = $file->store('uploads/services', 'public');
                $data['image'] = '/storage/' . $path;

            } catch (\Exception $e) {
                Log::error('Image upload failed during update: ' . $e->getMessage());
                return response()->json(['success' => false, 'message' => 'Image upload failed'], 500);
            }
        } else {
            // If no new image is uploaded, do NOT overwrite the existing image with null/empty.
            // Remove 'image' from data to preserve the current value in DB.
            unset($data['image']);
        }

        $oldData = $service->toArray();
        $service->update($data);
        
        AuditService::log('updated', 'Service', $service->id, ['old' => $oldData, 'new' => $service->toArray()]);
        Log::info('Service updated: ' . $service->id);
        return response()->json(['success' => true, 'data' => $service]);
    }

    public function destroy($id)
    {
        $service = Service::find($id);
        if (!$service) {
            Log::warning('Service not found for deletion: ' . $id);
            return response()->json(['success' => false, 'message' => 'Service not found'], 404);
        }

        $oldData = $service->toArray();
        $service->delete();
        
        AuditService::log('deleted', 'Service', $id, $oldData);
        Log::info('Service deleted: ' . $id);
        return response()->json(['success' => true, 'message' => 'Service deleted']);
    }
}
