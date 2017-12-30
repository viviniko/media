<?php

namespace Viviniko\Media\Contracts;

interface ImageService
{
    public function search($keywords);
    /**
     * Save a new entity in repository
     *
     * @param mixed $file
     * @param int $width
     * @param int $height
     *
     * @return mixed
     */
    public function save($file, $width = null, $height = null);

    /**
     * Delete a entity in repository by id
     *
     * @param $id
     *
     * @return int
     */
    public function delete($id);
}