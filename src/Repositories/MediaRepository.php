<?php

namespace Viviniko\Media\Repositories;

interface MediaRepository
{
    /**
     * @param $id
     * @param $columns
     * @return mixed
     */
    public function find($id, $columns = ['*']);

    /**
     * @param $column
     * @param null $value
     * @param array $columns
     * @return mixed
     */
    public function findBy($column, $value = null, $columns = ['*']);

    /**
     * @param array $data
     * @return mixed
     */
    public function create(array $data);

    /**
     * @param $id
     * @return mixed
     */
    public function delete($id);
}