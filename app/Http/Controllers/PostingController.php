<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddImagesRequest;
use App\Http\Requests\StorePostingRequest;
use App\Http\Requests\UpdatePostingRequest;
use App\Models\Image;
use App\Models\Posting;
use App\Services\FileUploadService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PostingController extends Controller
{
    protected FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    public function index(): View
    {
        $postings = Posting::with('images')->orderByDesc('created_at')->get();
        
        // Refresh URLs for all images
        foreach ($postings as $posting) {
            Image::refreshUrls($posting->images);
        }

        return view('postings.index', compact('postings'));
    }

    public function create(): View
    {
        return view('postings.create');
    }

    public function store(StorePostingRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $posting = Posting::create([
                'title' => $request->input('title'),
                'description' => $request->input('description', ''),
            ]);

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
                }
            }

            DB::commit();

            return redirect()->route('postings.index')
                ->with('success', 'Posting created successfully!');
        } catch (ValidationException $e) {
            DB::rollBack();
            return redirect()->route('postings.create')
                ->withErrors($e->errors())
                ->withInput();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Posting creation failed: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->except(['images']),
            ]);

            return redirect()->route('postings.create')
                ->with('error', 'Failed to create posting. Please try again.');
        }
    }

    public function show(Posting $posting): View
    {
        $posting->loadWithFreshUrls();

        return view('postings.show', compact('posting'));
    }

    public function edit(Posting $posting): View
    {
        $posting->loadWithFreshUrls();

        return view('postings.edit', compact('posting'));
    }

    public function update(UpdatePostingRequest $request, Posting $posting): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $posting->update([
                'title' => $request->input('title'),
                'description' => $request->input('description', ''),
            ]);

            // Delete selected images
            if ($request->has('delete_images')) {
                $imagesToDelete = Image::whereIn('id', $request->input('delete_images'))
                    ->where('posting_id', $posting->id)
                    ->get();

                $pathsToDelete = $imagesToDelete->pluck('path')->filter()->toArray();
                
                // Batch delete from S3
                if (!empty($pathsToDelete)) {
                    $this->fileUploadService->deleteMany($pathsToDelete, 's3');
                }
                
                // Delete from database
                $imagesToDelete->each->delete();
            }

            // Add new images
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
                }
            }

            DB::commit();

            return redirect()->route('postings.show', $posting->id)
                ->with('success', 'Posting updated successfully!');
        } catch (ValidationException $e) {
            DB::rollBack();
            return redirect()->route('postings.edit', $posting->id)
                ->withErrors($e->errors())
                ->withInput();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Posting update failed: ' . $e->getMessage(), [
                'exception' => $e,
                'posting_id' => $posting->id,
                'request_data' => $request->except(['images']),
            ]);

            return redirect()->route('postings.edit', $posting->id)
                ->with('error', 'Failed to update posting. Please try again.');
        }
    }

    public function destroy(Posting $posting): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $posting->load('images');
            
            // Batch delete all images from S3
            $pathsToDelete = $posting->images->pluck('path')->filter()->toArray();
            if (!empty($pathsToDelete)) {
                $this->fileUploadService->deleteMany($pathsToDelete, 's3');
            }

            // Delete posting (images will be cascade deleted)
            $posting->delete();

            DB::commit();

            return redirect()->route('postings.index')
                ->with('success', 'Posting deleted successfully!');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Posting deletion failed: ' . $e->getMessage(), [
                'exception' => $e,
                'posting_id' => $posting->id,
            ]);

            return redirect()->route('postings.index')
                ->with('error', 'Failed to delete posting. Please try again.');
        }
    }

    public function storeApi(StorePostingRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $posting = Posting::create([
                'title' => $request->input('title'),
                'description' => $request->input('description', ''),
            ]);

            $images = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $path = $this->fileUploadService->upload($file, 's3', 'images', $posting->id);
                    $url = $this->fileUploadService->url($path, 's3', 1440);

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

            DB::commit();

            return $this->successResponse([
                'id' => $posting->id,
                'title' => $posting->title,
                'description' => $posting->description,
                'images' => $images,
            ], 'Posting created successfully', 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->errorResponse('Validation failed', $e->errors(), 422);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('API Posting creation failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return $this->errorResponse('Failed to create posting', [], 500);
        }
    }

    public function destroyApi(Posting $posting): JsonResponse
    {
        DB::beginTransaction();
        try {
            $posting->load('images');
            
            // Batch delete all images from S3
            $pathsToDelete = $posting->images->pluck('path')->filter()->toArray();
            if (!empty($pathsToDelete)) {
                $this->fileUploadService->deleteMany($pathsToDelete, 's3');
            }

            $posting->delete();

            DB::commit();

            return $this->successResponse(null, 'Posting deleted successfully', 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('API Posting deletion failed: ' . $e->getMessage(), [
                'exception' => $e,
                'posting_id' => $posting->id,
            ]);

            return $this->errorResponse('Failed to delete posting', [], 500);
        }
    }

    public function addImages(AddImagesRequest $request, Posting $posting): JsonResponse
    {
        DB::beginTransaction();
        try {
            $images = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $path = $this->fileUploadService->upload($file, 's3', 'images', $posting->id);
                    $url = $this->fileUploadService->url($path, 's3', 1440);

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

            DB::commit();

            return $this->successResponse([
                'posting_id' => $posting->id,
                'images' => $images,
            ], 'Images added successfully', 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->errorResponse('Validation failed', $e->errors(), 422);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('API Add images failed: ' . $e->getMessage(), [
                'exception' => $e,
                'posting_id' => $posting->id,
            ]);

            return $this->errorResponse('Failed to add images', [], 500);
        }
    }

    /**
     * Return a successful JSON response
     */
    protected function successResponse($data, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Return an error JSON response
     */
    protected function errorResponse(string $message, array $errors = [], int $code = 422): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }
}
