<?php

namespace Viviniko\Media\Contracts;

interface ImageService
{
    public function search($keywords);

    public function getUrl($id);

    /**
     * Save a new entity in repository
     *
     * @param $file
     * @param string $dir
     * @param null $width
     * @param null $height
     * @param int $quality
     * @return mixed
     */
    public function save($file, $dir = 'default', $width = null, $height = null, $quality = 75);

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