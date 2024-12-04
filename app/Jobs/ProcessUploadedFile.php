<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Aws\Textract\TextractClient;
use App\Models\UploadedFile;
use Exception;
use Log;

class ProcessUploadedFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    public function __construct(public UploadedFile $uploadedFile)
    {
    }

    public function handle()
    {
        try {
            $this->uploadedFile->update(['status' => 'processing']);

            $textract = new TextractClient([
                'version' => 'latest',
                'region'  => env('AWS_DEFAULT_REGION'),
                'credentials' => [
                    'key'    => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                ],
            ]);

            $result = $textract->analyzeDocument([
                'Document' => [
                    'S3Object' => [
                        'Bucket' => env('AWS_BUCKET'),
                        'Name' => $this->uploadedFile->file_path,
                    ],
                ],
                'FeatureTypes' => ['TABLES', 'FORMS'],
            ]);

            // Store the extracted data
            $this->uploadedFile->update([
                'status' => 'completed',
                'extracted_data' => json_encode($result->toArray())
            ]);

        } catch (Exception $e) {
            Log::error('Textract processing failed: ' . $e->getMessage(), [
                'file_id' => $this->uploadedFile->id,
                'error' => $e->getMessage()
            ]);

            $this->uploadedFile->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
