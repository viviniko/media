<?php

namespace Viviniko\Media\Services\Impl;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Viviniko\Media\Events\FileCreated;
use Viviniko\Media\Events\FileDeleted;
use Viviniko\Media\FileExistsException;
use Viviniko\Media\Models\File;
use Viviniko\Media\Repositories\FileRepository;
use Viviniko\Media\Services\ImageService;

class ImageServiceImpl implements ImageService
{
    /**
     * @var \Viviniko\Media\Repositories\FileRepository
     */
    private $repository;

    /**
     * @var string
     */
    private $disk;

    /**
     * @var \Illuminate\Contracts\Bus\Dispatcher
     */
    private $dispatcher;

    /**
     * ImageServiceImpl constructor.
     * @param \Viviniko\Media\Repositories\FileRepository $repository
     * @param \Illuminate\Contracts\Events\Dispatcher $dispatcher
     */
    public function __construct(FileRepository $repository, Dispatcher $dispatcher)
    {
        $this->repository = $repository;
        $this->dispatcher = $dispatcher;
        $this->disk = Config::get('media.disk');
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl($id)
    {
        return data_get($this->repository->find($id), 'url');
    }

    /**
     * {@inheritdoc}
     */
    public function save($source, $target, $width = null, $height = null, $quality = 75)
    {
        while (true) {
            try {
                return $this->put($source, $target, $width, $height, $quality);
            } catch (FileExistsException $ex) {
                $target = $this->makeFilename($target, '', $this->disk);
            }
        }
    }

    public function put($source, $target, $width = null, $height = null, $quality = 75)
    {
        $disk = $this->disk;
        if ($exists = $this->repository->findBy(['disk' => $disk, 'object' => $target])) {
            throw new FileExistsException("Disk [$exists->disk] File exists:  $exists->object");
        }

        $image = Image::make($source);
        $mimeType = $image->mime();
        if ($width || $height) {
            $image->resize($width, $height);
        }
        $data = $image->encode($mimeType, $quality)->getEncoded();
        $hash = md5($data);
        $originalFilename = basename(urldecode($source instanceof UploadedFile ? $source->getClientOriginalName() : $source));
        $attributes = [
            'disk' => $disk,
            'object' => $target,
            'size' => strlen($data),
            'mime_type' => $mimeType,
            'md5' => $hash,
            'original_filename' => $originalFilename
        ];
        $file = DB::transaction(function () use ($attributes, $data) {
            return $this->repository->create($attributes)->setContent($data);
        });

        $this->dispatcher->dispatch(new FileCreated($file));

        return $file;
    }

    /**
     * {@inheritdoc}
     */
    public function crop($id, $width, $height, $x = null, $y = null)
    {
        $image = $id instanceof File ? $id : $this->repository->find($id);
        $disk = $image->disk;
        $crop = Image::make($image->content)->crop($width, $height, $x, $y);
        $data = $crop->encode($image->mime_type, 100)->getEncoded();
        $hash = md5($data);
        while (($target = $this->makeFilename($image->object, '_s', $disk)) && $this->repository->findBy(['disk' => $disk, 'object' => $target]));
        $attributes = [
            'disk' => $disk,
            'object' => $target,
            'size' => strlen($data),
            'mime_type' => $crop->mime(),
            'md5' => $hash,
            'original_filename' => $image->original_filename,
        ];
        $file = DB::transaction(function () use ($attributes, $data) {
            return $this->repository->create($attributes)->setContent($data);
        });

        $this->dispatcher->dispatch(new FileCreated($file));

        return $file;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        $file = $this->repository->find($id);

        if (!$file) return 0;

        $result = $this->repository->delete($id);

        Storage::disk($file->disk)->delete($file->object);

        $this->dispatcher->dispatch(new FileDeleted($file));

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function disk($disk)
    {
        $this->disk = $disk;

        return $this;
    }

    private function makeFilename($filename, $suffix = '', $disk = null)
    {
        $basename = $filename;
        $ext = '';
        if (($dotPos = strrpos($filename, '.')) !== false) {
            $basename = implode('/', array_map(function ($sub) {
                return Str::slug($sub, '_');
            }, explode('/', substr($filename, 0, $dotPos))));
            $ext = strtolower(substr($filename, $dotPos));
        }
        $basename .= $suffix;
        $filename = $basename . $ext;
        $i = 1;
        while (Storage::disk($disk ?? $this->disk)->exists($filename)) {
            $filename = "{$basename}-{$i}{$ext}";
            if (++$i > 10000) {
                throw new \Exception('Error file name');
            }
        }

        return $filename;
    }
}