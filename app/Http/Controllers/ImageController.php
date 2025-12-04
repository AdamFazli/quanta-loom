<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\Posting;
use App\Services\FileUploadService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ImageController extends Controller
{
    public function index(): View
    {
        $images = Image::orderByDesc('created_at')->get();

        foreach ($images as $image) {
            if (str_contains($image->url, 's3.amazonaws.com') || str_contains($image->url, 's3.')) {
                $fileUploadService = new FileUploadService();
                $image->url = $fileUploadService->url($image->path, 's3');
            }
        }

        return view('images.index', compact('images'));
    }

    public function create(): View
    {
        return view('images.create');
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'images' => 'required|array|min:1',
                'images.*' => 'image|mimes:jpeg,png,gif,webp|max:5120',
            ]);

            $posting = Posting::create([
                'title' => $request->input('title'),
                'description' => $request->input('description', ''),
            ]);

            $fileUploadService = new FileUploadService();
            $uploadedCount = 0;

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $path = $fileUploadService->upload($file, 's3', 'images', $posting->id);
                    $url = $fileUploadService->url($path, 's3');

                    Image::create([
                        'posting_id' => $posting->id,
                        'title' => $file->getClientOriginalName(),
                        'description' => '',
                        'path' => $path,
                        'url' => $url,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ]);
                    $uploadedCount++;
                }
            }

            $message = $uploadedCount === 1 
                ? 'Posting with 1 image created successfully!' 
                : "Posting with {$uploadedCount} images created successfully!";

            return redirect()->route('postings.index')
                ->with('success', $message);
        } catch (Exception $e) {
            return redirect()->route('images.create')
                ->with('error', $e->getMessage());
        }
    }

    public function show(string $id): View
    {
        $image = Image::findOrFail($id);

        if (str_contains($image->url, 's3.amazonaws.com') || str_contains($image->url, 's3.')) {
            $fileUploadService = new FileUploadService();
            $image->url = $fileUploadService->url($image->path, 's3');
        }

        return view('images.show', compact('image'));
    }

    public function storeApi(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'image' => 'required|image|mimes:jpeg,png,gif,webp|max:5120',
            ]);

            $uploadedFile = $request->file('image');
            $fileUploadService = new FileUploadService();

            $path = $fileUploadService->upload($uploadedFile, 's3', 'images');
            $url = $fileUploadService->url($path, 's3');

            $imageData = [
                'title' => $request->input('title'),
                'description' => $request->input('description', ''),
                'path' => $path,
                'url' => $url,
                'original_name' => $uploadedFile->getClientOriginalName(),
                'mime_type' => $uploadedFile->getMimeType(),
                'size' => $uploadedFile->getSize(),
            ];

            $image = Image::create($imageData);

            return response()->json([
                'success' => true,
                'message' => 'Image uploaded successfully',
                'data' => [
                    'id' => $image->id,
                    'title' => $image->title,
                    'description' => $image->description,
                    'path' => $image->path,
                    'url' => $image->url,
                    'original_name' => $image->original_name,
                ],
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function destroy(string $id): RedirectResponse
    {
        try {
            $image = Image::findOrFail($id);

            Storage::disk('s3')->delete($image->path);

            $image->delete();

            return redirect()->route('images.index')
                ->with('success', 'Image deleted successfully!');
        } catch (Exception $e) {
            return redirect()->route('images.index')
                ->with('error', 'Failed to delete image: ' . $e->getMessage());
        }
    }

    public function destroyApi(string $id): JsonResponse
    {
        try {
            $image = Image::findOrFail($id);

            Storage::disk('s3')->delete($image->path);

            $image->delete();

            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image: ' . $e->getMessage()
            ], 422);
        }
    }

    public function destroyFromPosting(string $postingId, string $imageId): JsonResponse
    {
        try {
            $posting = Posting::findOrFail($postingId);
            $image = Image::where('id', $imageId)
                ->where('posting_id', $postingId)
                ->firstOrFail();

            Storage::disk('s3')->delete($image->path);
            
            $image->delete();

            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image: ' . $e->getMessage()
            ], 422);
        }
    }

    public function destroyFromPostingWeb(string $postingId, string $imageId): RedirectResponse
    {
        try {
            $posting = Posting::findOrFail($postingId);
            $image = Image::where('id', $imageId)
                ->where('posting_id', $postingId)
                ->firstOrFail();

            Storage::disk('s3')->delete($image->path);
            
            $image->delete();

            return redirect()->route('postings.show', $postingId)
                ->with('success', 'Image deleted successfully!');
        } catch (Exception $e) {
            return redirect()->route('postings.show', $postingId)
                ->with('error', 'Failed to delete image: ' . $e->getMessage());
        }
    }

    public function destroyAllFromPosting(string $postingId): JsonResponse
    {
        try {
            $posting = Posting::with('images')->findOrFail($postingId);

            foreach ($posting->images as $image) {
                Storage::disk('s3')->delete($image->path);
                $image->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'All images deleted successfully',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete images: ' . $e->getMessage()
            ], 422);
        }
    }
}
