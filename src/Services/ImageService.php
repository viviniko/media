<?php

namespace Viviniko\Media\Services;

interface ImageService extends FileService
{
    /**
     * Save a new entity in repository
     *
     * @param mixed $source
     * @param string $target
     * @param null $width
     * @param null $height
     * @param int $quality
     * @return \Viviniko\Media\Models\File
     */
    public function put($source, $target, $width = null, $height = null, $quality = 75);

    /**
     * @param $id
     * @param $width
     * @param $height
     * @param null $x
     * @param null $y
     * @return mixed
     */
    public function crop($id, $width, $height, $x = null, $y = null);
}