<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UploadedFile;
use App\Jobs\ProcessUploadedFile;
use Illuminate\Support\Facades\Storage;

class TextractController extends Controller
{
    public function showUploadForm()
    {
        return inertia('Upload');
    }

    public function processBulkUpload(Request $request)
    {
        $request->validate([
            'documents.*' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $files = $request->file('documents');
        $uploadedFiles = [];

        foreach ($files as $file) {
            // Store file in S3
            $path = Storage::disk('s3')->put('documents', $file);
            
            // Create database record
            $uploadedFile = UploadedFile::create([
                'user_id' => auth()->id(),
                'file_path' => $path,
                'status' => 'pending'
            ]);

            ProcessUploadedFile::dispatch($uploadedFile);
            $uploadedFiles[] = $uploadedFile;
        }

        return response()->json([
            'message' => 'Files uploaded successfully',
            'files' => $uploadedFiles
        ]);
    }
}
