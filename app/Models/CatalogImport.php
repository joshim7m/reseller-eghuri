<?php

namespace App\Models;

use Database\Factories\CatalogImportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogImport extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    /** @use HasFactory<CatalogImportFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'original_name',
        'file_path',
        'total_rows',
        'processed',
        'imported',
        'skipped',
        'errors',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'errors' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
