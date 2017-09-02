<?php

namespace Viviniko\Media\Contracts;

use Illuminate\Http\UploadedFile;

interface ImageService
{
    public function search($keywords);
    /**
     * Save a new entity in repository
     *
     * @param UploadedFile $file
     * @param int $width
     * @param int $height
     *
     * @return mixed
     */
    public function save(UploadedFile $file, $width = null, $height = null);

    /**
     * Delete a entity in repository by id
     *
     * @param $id
     *
     * @return int
     */
    public function delete($id);
}