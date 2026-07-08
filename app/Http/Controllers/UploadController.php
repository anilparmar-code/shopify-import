<?php

namespace App\Http\Controllers;

use App\Enums\Upload\UploadStatusEnum;
use App\Http\Requests\StoreImportUploadRequest;
use App\Jobs\ProcessCsvUpload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UploadController extends Controller
{
    /**
     * Display a listing of the uploads.
     */
    public function index(Request $request): Response
    {
        $uploads = $request->user()->uploads()
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($upload) => [
                'id' => $upload->id,
                'file_name' => $upload->original_file_name,
                'status' => $upload->status->value,
                'total_records' => $upload->total_records,
                'processed_records' => $upload->processed_records,
                'success_records' => $upload->success_records,
                'failed_records' => $upload->failed_records,
                'created_at' => $upload->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $upload->updated_at->format('Y-m-d H:i:s'),
            ]);

        return Inertia::render('imports/Index', [
            'uploads' => $uploads,
        ]);
    }

    /**
     * Show the import upload page.
     */
    public function create(): Response
    {
        return Inertia::render('imports/Create', [
            'maxFileSizeMb' => 5,
        ]);
    }

    /**
     * Store the uploaded CSV and dispatch async processing.
     */
    public function store(StoreImportUploadRequest $request): RedirectResponse
    {
        $csvFile = $request->file('csv_file');

        $upload = $request->user()->uploads()->create([
            'file_path' => $csvFile->store('imports/'),
            'original_file_name' => $csvFile->getClientOriginalName(),
            'status' => UploadStatusEnum::PENDING->value,
        ]);

        ProcessCsvUpload::dispatch(
            upload: $upload,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('CSV uploaded. Processing has started in the background.'),
        ]);

        return to_route('uploads.index');
    }
}
