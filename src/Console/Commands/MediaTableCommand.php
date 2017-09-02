<?php

namespace Viviniko\Media\Console\Commands;

use Viviniko\Support\Console\CreateMigrationCommand;

class MediaTableCommand extends CreateMigrationCommand
{
    /**
     * @var string
     */
    protected $name = 'media:table';

    /**
     * @var string
     */
    protected $description = 'Create a migration for the media service table';

    /**
     * @var string
     */
    protected $stub = __DIR__.'/stubs/media.stub';

    /**
     * @var string
     */
    protected $migration = 'create_media_table';
}
