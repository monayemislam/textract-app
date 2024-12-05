<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UploadedFile;
use App\Jobs\ProcessUploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class TextractController extends Controller
{
    public function showUploadForm()
    {
        return Inertia::render('Upload');
    }

    public function processBulkUpload(Request $request)
    {
        try {
            $request->validate([
                'documents.*' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
            ]);

            if (!$request->hasFile('documents')) {
                return back()->withErrors([
                    'documents' => 'Please select at least one file.'
                ]);
            }

            $files = $request->file('documents');
            $uploadedFiles = [];

            foreach ($files as $file) {
                try {
                    $originalName = $file->getClientOriginalName();
                    $path = Storage::disk('s3')->put('textract_resources', $file, 'public');
                    
                    if (!$path) {
                        throw new \Exception('Failed to upload file to S3');
                    }
            
                    \Log::info('File uploaded to S3', [
                        'original_name' => $originalName,
                        's3_path' => $path,
                        'exists' => Storage::disk('s3')->exists($path)
                    ]);
                    
                    $uploadedFile = UploadedFile::create([
                        'user_id' => auth()->id(),
                        'file_path' => $path,
                        'original_name' => $originalName,
                        'status' => 'pending'
                    ]);
            
                    ProcessUploadedFile::dispatch($uploadedFile);
                    $uploadedFiles[] = $uploadedFile;
                } catch (\Exception $e) {
                    \Log::error('File upload failed', [
                        'file' => $file->getClientOriginalName(),
                        'error' => $e->getMessage()
                    ]);
                    
                    return back()->withErrors([
                        'documents' => 'File upload failed: ' . $e->getMessage()
                    ]);
                }
            }

            return back()->with([
                'success' => 'Files uploaded successfully',
                'files' => $uploadedFiles
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (\Exception $e) {
            \Log::error('Bulk upload failed: ' . $e->getMessage());
            return back()->withErrors([
                'documents' => 'Upload failed: ' . $e->getMessage()
            ]);
        }
    }

    public function getUploadedFiles()
    {
        $files = UploadedFile::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Results', [
            'files' => $files
        ]);
    }
}
