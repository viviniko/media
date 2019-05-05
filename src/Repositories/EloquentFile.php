<?php

namespace Viviniko\Media\Repositories;

use Illuminate\Support\Facades\Config;
use Viviniko\Repository\EloquentRepository;

class EloquentFile extends EloquentRepository implements FileRepository
{
    public function __construct()
    {
        parent::__construct(Config::get('media.file'));
    }
}