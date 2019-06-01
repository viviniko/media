<?php

namespace Viviniko\Media\Models;

use Viviniko\Support\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    protected $tableConfigKey = 'media.files_table';

    protected $fillable = ['disk', 'object', 'size', 'mime_type', 'md5', 'original_filename'];

    public function getUrlAttribute()
    {
        return Storage::disk($this->disk)->url($this->object);
    }

    public function getReadableSizeAttribute()
    {
        $bytes = $this->attributes['size'];
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}