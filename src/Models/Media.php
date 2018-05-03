<?php

namespace Viviniko\Media\Models;

use Viviniko\Support\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    protected $tableConfigKey = 'media.medias_table';

    protected $fillable = ['filename', 'size', 'disk', 'mime_type', 'sha1', 'group'];

    protected $appends = ['url'];

    public function getUrlAttribute()
    {
        return Storage::disk($this->disk)->url($this->filename);
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