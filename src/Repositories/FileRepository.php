<?php

namespace Viviniko\Media\Repositories;

use Viviniko\Repository\SearchRequest;

interface FileRepository
{
    /**
     * Search.
     *
     * @param SearchRequest $searchRequest
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection
     */
    public function search(SearchRequest $searchRequest);

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
     * @param null $column
     * @param null $value
     * @return mixed
     */
    public function count($column = null, $value = null);

    /**
     * @param $id
     * @return mixed
     */
    public function delete($id);
}