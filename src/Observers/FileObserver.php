<?php

namespace Viviniko\Media\Observers;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Storage;
use Viviniko\Media\Events\FileCreated;
use Viviniko\Media\Events\FileDeleted;
use Viviniko\Media\Events\FileUpdated;
use Viviniko\Media\Models\File;

class FileObserver
{
    private $dispatcher;

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function created(File $file)
    {
        $this->dispatcher->dispatch(new FileCreated($file));
    }

    public function updated(File $file)
    {
        $this->dispatcher->dispatch(new FileUpdated($file));
    }

    public function deleting(File $file)
    {
        Storage::disk($file->disk)->delete($file->object);
    }

    public function deleted(File $file)
    {
        $this->dispatcher->dispatch(new FileDeleted($file));
    }
}