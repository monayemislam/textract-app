<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UploadedFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'file_path',
        'original_name',
        'status',
        'extracted_data',
        'error_message'
    ];

    protected $casts = [
        'extracted_data' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
