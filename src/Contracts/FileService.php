<?php

namespace Viviniko\Media\Contracts;

use Illuminate\Http\UploadedFile;

interface FileService
{
    /**
     * Generate dir info
     * @param $path
     * @return mixed
     */
    public function dirInfo($path);

    /**
     * Upload file
     * @param UploadedFile $file
     * @return mixed
     */
    public function upload(UploadedFile $file, $path = null);

    /**
     * Create folder
     * @param $name
     * @param null $path
     * @return mixed
     */
    public function mkdir($name, $path = null);

    /**
     * Delete folder
     * @param $path
     * @return mixed
     */
    public function rmdir($path);

    /**
     * Delete file
     * @param $path
     * @return mixed
     */
    public function delete($path);

    /**
     * Rename file
     * @param $oldName
     * @param $newName
     * @param null $path
     * @return mixed
     */
    public function rename($oldName, $newName, $path = null);
}