<?php

namespace Viviniko\Media\Services\Impl;

use Viviniko\Media\Services\FileService;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;

class FileServiceImpl implements FileService
{
    /** @var $files \Illuminate\Contracts\Filesystem\Filesystem */
    protected $files;

    /**
     * FileObject constructor.
     * @param $files
     */
    public function __construct()
    {
        $this->files = app('filesystem')->disk(config('media.disk'));
    }

    /**
     * Generate dir info
     * @param $path
     * @return mixed
     */
    public function dirInfo($path)
    {
        //directories and files
        $directories = $this->files->directories($path);
        $directories = collect($directories)->map(function ($dir) {
            return (object)[
                'name' => last(explode('/', $dir)),
                'is_folder' => true,
                'path' => $dir
            ];
        })->toArray();
        $files = collect($this->files->files($path))->map(function ($file) {
            $fileObject = new File($this->files->path($file));
            return (object)[
                'name' => $fileObject->getFilename(),
                'cTime' => date('Y-m-d H:i:s', $fileObject->getCTime()),
                'is_folder' => false,
                'url' => $this->files->url($file),
                'extension' => strtolower($fileObject->getExtension()),
                'path' => $file,
            ];
        })->toArray();
        $allFiles = array_merge($directories, $files);

        $pathArray = explode('/', $path);
        $dir = '';
        $pathArray = collect($pathArray)->map(function ($path) use (&$dir) {
            $dir .= $path . '/';
            return [
                'path' => $path,
                'dir' => $dir
            ];
        })->splice(1)->toArray();

        return compact('allFiles', 'pathArray');
    }

    /**
     * Upload file
     * @param UploadedFile $file
     * @return mixed
     */
    public function upload(UploadedFile $file, $path = null)
    {
        $filename = $file->getClientOriginalName();
        try {
            $filename = iconv('gbk', 'utf-8', $filename);
        }catch (\Exception $e){}
        while ($this->files->exists($path . '/' . $filename)) {
            $filename = strtoupper(str_random(3)) . $filename;
        }
        return $file->storeAs($path, $filename, ['disk' => config('media.disk')]);
    }

    public function mkdir($name, $path = null)
    {
        return $this->files->makeDirectory($path . '/' . $name);
    }

    public function rmdir($path)
    {
        return $this->files->deleteDirectory($path);
    }

    /**
     * Delete file
     * @param $path
     * @return mixed
     */
    public function delete($path)
    {
        return $this->files->delete($path);
    }

    public function rename($oldName, $newName, $path = null)
    {
        $path = str_finish($path, '/');
        $old = $path . $oldName;
        $new = $path . $newName;
        if ($old == $new) {
            return true;
        }
        if ($this->files->exists($new)) {
            return false;
        }
        return $this->files->move($old, $new);
    }
}