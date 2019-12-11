<?php

namespace Viviniko\Media\Services;

interface FileService
{
    /**
     * Change disk.
     *
     * @param $disk
     * @return $this
     */
    public function disk($disk);

    /**
     * Object Exists.
     *
     * @param string $object
     * @param mixed $disk
     * @return boolean
     */
    public function has($object, $disk = null);

    /**
     * Save a new entity in repository
     *
     * @param mixed $source
     * @param string $target
     * @return \Viviniko\Media\Models\File
     */
    public function put($source, $target);

    /**
     * Get Object.
     *
     * @param string $object
     * @param mixed $disk
     * @return boolean
     */
    public function get($object, $disk = null);

    /**
     * Delete a entity in repository by id
     *
     * @param $id
     *
     * @return int
     */
    public function delete($id);
}