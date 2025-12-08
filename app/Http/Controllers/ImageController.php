<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostingRequest;
use App\Models\Image;
use App\Models\Posting;
use App\Services\FileUploadService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ImageController extends Controller
{
    protected FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    public function index(): View
    {
        $images = Image::orderByDesc('created_at')->get();
        Image::refreshUrls($images);

        return view('images.index', compact('images'));
    }

    public function create(): View
    {
        return view('images.create');
    }

    public function store(StorePostingRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $posting = Posting::create([
                'title' => $request->input('title'),
                'description' => $request->input('description', ''),
            ]);

            $uploadedCount = 0;
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $path = $this->fileUploadService->upload($file, 's3', 'images', $posting->id);
                    $url = $this->fileUploadService->url($path, 's3', 1440);

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

            DB::commit();

            $message = $uploadedCount === 1 
                ? 'Posting with 1 image created successfully!' 
                : "Posting with {$uploadedCount} images created successfully!";

            return redirect()->route('postings.index')
                ->with('success', $message);
        } catch (ValidationException $e) {
            DB::rollBack();
            return redirect()->route('images.create')
                ->withErrors($e->errors())
                ->withInput();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Image creation failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return redirect()->route('images.create')
                ->with('error', 'Failed to create posting. Please try again.');
        }
    }

    public function show(Image $image): View
    {
        // Refresh URL if needed
        if ($image->path && (str_contains($image->url, 's3.amazonaws.com') || str_contains($image->url, 's3.'))) {
            $image->url = $image->fresh_url;
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

    public function destroy(Image $image): RedirectResponse
    {
        DB::beginTransaction();
        try {
            if ($image->path) {
                $this->fileUploadService->delete($image->path, 's3');
            }

            $image->delete();

            DB::commit();

            return redirect()->route('images.index')
                ->with('success', 'Image deleted successfully!');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Image deletion failed: ' . $e->getMessage(), [
                'exception' => $e,
                'image_id' => $image->id,
            ]);

            return redirect()->route('images.index')
                ->with('error', 'Failed to delete image. Please try again.');
        }
    }

    public function destroyApi(Image $image): JsonResponse
    {
        DB::beginTransaction();
        try {
            if ($image->path) {
                $this->fileUploadService->delete($image->path, 's3');
            }

            $image->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully',
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('API Image deletion failed: ' . $e->getMessage(), [
                'exception' => $e,
                'image_id' => $image->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image'
            ], 500);
        }
    }

    public function destroyFromPosting(Posting $posting, Image $image): JsonResponse
    {
        DB::beginTransaction();
        try {
            // Verify image belongs to posting
            if ($image->posting_id !== $posting->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Image does not belong to this posting'
                ], 403);
            }

            if ($image->path) {
                $this->fileUploadService->delete($image->path, 's3');
            }
            
            $image->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully',
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Delete image from posting failed: ' . $e->getMessage(), [
                'exception' => $e,
                'posting_id' => $posting->id,
                'image_id' => $image->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image'
            ], 500);
        }
    }

    public function destroyFromPostingWeb(Posting $posting, Image $image): RedirectResponse
    {
        DB::beginTransaction();
        try {
            // Verify image belongs to posting
            if ($image->posting_id !== $posting->id) {
                return redirect()->route('postings.show', $posting->id)
                    ->with('error', 'Image does not belong to this posting.');
            }

            if ($image->path) {
                $this->fileUploadService->delete($image->path, 's3');
            }
            
            $image->delete();

            DB::commit();

            return redirect()->route('postings.show', $posting->id)
                ->with('success', 'Image deleted successfully!');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Delete image from posting (web) failed: ' . $e->getMessage(), [
                'exception' => $e,
                'posting_id' => $posting->id,
                'image_id' => $image->id,
            ]);

            return redirect()->route('postings.show', $posting->id)
                ->with('error', 'Failed to delete image. Please try again.');
        }
    }

    public function destroyAllFromPosting(Posting $posting): JsonResponse
    {
        DB::beginTransaction();
        try {
            $posting->load('images');
            
            // Batch delete all images from S3
            $pathsToDelete = $posting->images->pluck('path')->filter()->toArray();
            if (!empty($pathsToDelete)) {
                $this->fileUploadService->deleteMany($pathsToDelete, 's3');
            }

            // Delete all images from database
            $posting->images()->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'All images deleted successfully',
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Delete all images from posting failed: ' . $e->getMessage(), [
                'exception' => $e,
                'posting_id' => $posting->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete images'
            ], 500);
        }
    }
}
