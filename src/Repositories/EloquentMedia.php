<?php

namespace Viviniko\Media\Repositories;

use Illuminate\Support\Facades\Config;
use Viviniko\Repository\EloquentRepository;

class EloquentMedia extends EloquentRepository implements MediaRepository
{
    protected $searchRules = ['filename' => 'like'];

    public function __construct()
    {
        parent::__construct(Config::get('media.media'));
    }
}