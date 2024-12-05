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
            \Log::info('Starting ProcessUploadedFile job', [
                'file_id' => $this->uploadedFile->id,
                'file_path' => $this->uploadedFile->file_path,
                'aws_region' => env('AWS_DEFAULT_REGION'),
                'aws_bucket' => env('AWS_BUCKET')
            ]);

            $this->uploadedFile->update(['status' => 'processing']);

            // Initialize AWS Textract client with explicit configuration
            $textract = new TextractClient([
                'version' => 'latest',
                'region'  => 'us-east-1',
                'credentials' => [
                    'key'    => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                ],
                'http'    => [
                    'verify' => false // Only for testing, remove in production
                ]
            ]);

            // Log the document parameters
            \Log::info('Analyzing document with parameters', [
                'bucket' => env('AWS_BUCKET'),
                'file_path' => $this->uploadedFile->file_path,
                'full_path' => 'textract_resources/' . basename($this->uploadedFile->file_path)
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

            \Log::info('Textract analysis completed', [
                'result' => $result->toArray()
            ]);

            $this->uploadedFile->update([
                'status' => 'completed',
                'extracted_data' => json_encode($result->toArray())
            ]);

        } catch (\Exception $e) {
            \Log::error('Textract processing failed', [
                'file_id' => $this->uploadedFile->id,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'file_path' => $this->uploadedFile->file_path,
                'trace' => $e->getTraceAsString()
            ]);

            $this->uploadedFile->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
