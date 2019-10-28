<?php

namespace Viviniko\Media\Models;

use Viviniko\Support\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    protected $tableConfigKey = 'media.files_table';

    protected $fillable = ['disk', 'object', 'size', 'mime_type', 'md5', 'original_filename'];

    protected $hidden = ['content'];

    private $content;

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

    public function getContentAttribute()
    {
        if (!$this->content) {
            $this->content = Storage::disk($this->disk)->get($this->object);
        }
        return $this->content;
    }

    public function setContent($content)
    {
        Storage::disk($this->disk)->put($this->object, $content);
        $this->content = $content;

        return $this;
    }
}