<?php

namespace Viviniko\Media;

use Exception;
use Throwable;

class FileExistsException extends Exception
{
    private $file;

    public function __construct($file)
    {
        parent::__construct("Disk [$file->disk] File exists:  $file->object");
        $this->file = $file;
    }
}