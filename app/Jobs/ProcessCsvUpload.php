<?php

namespace App\Jobs;

use App\Enums\Upload\UploadStatusEnum;
use App\Models\Upload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCsvUpload implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly Upload $upload
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Processing CSV upload', [
            'upload_id' => $this->upload->id,
            'file_path' => $this->upload->file_path,
        ]);

        // Here you would implement the logic to process the CSV file.
        // For example, you might read the file, validate its contents,
        // and update the upload record's status and counts accordingly.

        // For demonstration purposes, we'll just mark it as completed.
        $this->upload->update([
            'status' => UploadStatusEnum::COMPLETED->value,
            'processed_records' => 100, // Example value
            'success_records' => 95,     // Example value
            'failed_records' => 5,       // Example value
        ]);

        Log::info('CSV upload processing completed', [
            'upload_id' => $this->upload->id,
            'status' => $this->upload->status,
        ]);
    }
}
