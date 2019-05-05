<?php

namespace Viviniko\Media\Services;

interface ImageService
{
    /**
     * Change disk.
     *
     * @param $disk
     * @return $this
     */
    public function disk($disk);

    /**
     * Save a new entity in repository
     *
     * @param mixed $source
     * @param string $target
     * @param null $width
     * @param null $height
     * @param int $quality
     * @return mixed
     */
    public function save($source, $target, $width = null, $height = null, $quality = 75);

    /**
     * Get media public url.
     *
     * @param $id
     * @return mixed
     */
    public function getUrl($id);

    /**
     * @param $id
     * @param $width
     * @param $height
     * @param null $x
     * @param null $y
     * @return mixed
     */
    public function crop($id, $width, $height, $x = null, $y = null);

    /**
     * Delete a entity in repository by id
     *
     * @param $id
     *
     * @return int
     */
    public function delete($id);
}