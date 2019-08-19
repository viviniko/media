<?php

namespace Viviniko\Media;

use Exception;

class FileExistsException extends Exception
{
    private $existsFile;

    public function __construct($existsFile)
    {
        parent::__construct("Disk [$existsFile->disk] File exists:  $existsFile->object");
        $this->existsFile = $existsFile;
    }

    public function getExistsFile()
    {
        return $this->existsFile;
    }
}