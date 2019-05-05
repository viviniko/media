<?php

namespace Viviniko\Media\Events;

use Viviniko\Media\Models\File;

class FileEvent
{
    public $file;

    public function __construct(File $file)
    {
        $this->file = $file;
    }
}