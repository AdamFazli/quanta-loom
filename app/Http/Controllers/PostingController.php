<?php

namespace App\Http\Controllers;

use App\Models\Posting;
use App\Models\Image;
use App\Services\FileUploadService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PostingController extends Controller
{
    public function index(): View
    {
        $postings = Posting::with('images')->orderByDesc('created_at')->get();

        foreach ($postings as $posting) {
            foreach ($posting->images as $image) {
                if (str_contains($image->url, 's3.amazonaws.com') || str_contains($image->url, 's3.')) {
                    $fileUploadService = new FileUploadService();
                    $image->url = $fileUploadService->url($image->path, 's3');
                }
            }
        }

        return view('postings.index', compact('postings'));
    }

    public function create(): View
    {
        return view('postings.create');
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'images.*' => 'image|mimes:jpeg,png,gif,webp|max:5120',
            ]);

            $posting = Posting::create([
                'title' => $request->input('title'),
                'description' => $request->input('description', ''),
            ]);

            if ($request->hasFile('images')) {
                $fileUploadService = new FileUploadService();

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
                }
            }

            return redirect()->route('postings.index')
                ->with('success', 'Posting created successfully!');
        } catch (Exception $e) {
            return redirect()->route('postings.create')
                ->with('error', $e->getMessage());
        }
    }

    public function show(string $id): View
    {
        $posting = Posting::with('images')->findOrFail($id);

        foreach ($posting->images as $image) {
            if (str_contains($image->url, 's3.amazonaws.com') || str_contains($image->url, 's3.')) {
                $fileUploadService = new FileUploadService();
                $image->url = $fileUploadService->url($image->path, 's3');
            }
        }

        return view('postings.show', compact('posting'));
    }

    public function destroy(string $id): RedirectResponse
    {
        try {
            $posting = Posting::with('images')->findOrFail($id);

            foreach ($posting->images as $image) {
                Storage::disk('s3')->delete($image->path);
            }

            $posting->delete();

            return redirect()->route('postings.index')
                ->with('success', 'Posting deleted successfully!');
        } catch (Exception $e) {
            return redirect()->route('postings.index')
                ->with('error', 'Failed to delete posting: ' . $e->getMessage());
        }
    }

    public function storeApi(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'images.*' => 'image|mimes:jpeg,png,gif,webp|max:5120',
            ]);

            $posting = Posting::create([
                'title' => $request->input('title'),
                'description' => $request->input('description', ''),
            ]);

            $images = [];
            if ($request->hasFile('images')) {
                $fileUploadService = new FileUploadService();

                foreach ($request->file('images') as $file) {
                    $path = $fileUploadService->upload($file, 's3', 'images', $posting->id);
                    $url = $fileUploadService->url($path, 's3');

                    $image = Image::create([
                        'posting_id' => $posting->id,
                        'title' => $file->getClientOriginalName(),
                        'description' => '',
                        'path' => $path,
                        'url' => $url,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ]);

                    $images[] = [
                        'id' => $image->id,
                        'path' => $image->path,
                        'url' => $image->url,
                        'original_name' => $image->original_name,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Posting created successfully',
                'data' => [
                    'id' => $posting->id,
                    'title' => $posting->title,
                    'description' => $posting->description,
                    'images' => $images,
                ],
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function destroyApi(string $id): JsonResponse
    {
        try {
            $posting = Posting::with('images')->findOrFail($id);

            foreach ($posting->images as $image) {
                Storage::disk('s3')->delete($image->path);
            }

            $posting->delete();

            return response()->json([
                'success' => true,
                'message' => 'Posting deleted successfully',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete posting: ' . $e->getMessage()
            ], 422);
        }
    }

    public function addImages(Request $request, string $id): JsonResponse
    {
        try {
            $posting = Posting::findOrFail($id);

            $request->validate([
                'images.*' => 'required|image|mimes:jpeg,png,gif,webp|max:5120',
            ]);

            $images = [];
            if ($request->hasFile('images')) {
                $fileUploadService = new FileUploadService();

                foreach ($request->file('images') as $file) {
                    $path = $fileUploadService->upload($file, 's3', 'images', $posting->id);
                    $url = $fileUploadService->url($path, 's3');

                    $image = Image::create([
                        'posting_id' => $posting->id,
                        'title' => $file->getClientOriginalName(),
                        'description' => '',
                        'path' => $path,
                        'url' => $url,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ]);

                    $images[] = [
                        'id' => $image->id,
                        'path' => $image->path,
                        'url' => $image->url,
                        'original_name' => $image->original_name,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Images added successfully',
                'data' => [
                    'posting_id' => $posting->id,
                    'images' => $images,
                ],
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}