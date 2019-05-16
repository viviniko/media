<?php

namespace Viviniko\Media\Facades;

use Illuminate\Support\Facades\Facade;

class Files extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \Viviniko\Media\Repositories\FileRepository::class;
    }
}