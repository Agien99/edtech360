<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileAttachmentVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'file_attachment_id',
        'version_number',
        'uploaded_by',
        'verification_status',
        'verified_at',
        'original_filename',
        'storage_disk',
        'storage_path',
        'mime_type',
        'file_size',
        'checksum_sha256',
        'change_note',
    ];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'file_size' => 'integer',
            'verified_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function attachment(): BelongsTo
    {
        return $this->belongsTo(
            FileAttachment::class,
            'file_attachment_id'
        );
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}