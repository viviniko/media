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
use Viviniko\Media\Events\FileUpdated;
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
    public function get($object, $disk = null)
    {
        return $this->repository->findBy(['disk' => $disk ?: $this->disk, 'object' => $object]);
    }

    /**
     * {@inheritdoc}
     */
    public function has($object, $disk = null)
    {
        return $this->repository->exists(['disk' => $disk ?: $this->disk, 'object' => $object]);
    }

    /**
     * {@inheritdoc}
     */
    public function put($source, $target, $width = null, $height = null, $quality = 75)
    {
        $disk = $this->disk;
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
        $exists = $this->repository->findBy(['disk' => $attributes['disk'], 'object' => $attributes['object']]);
        $file = DB::transaction(function () use ($attributes, $data, $exists) {
            return ($exists ?: $this->repository->create($attributes))->setContent($data);
        });
        $this->dispatcher->dispatch($exists ? new FileUpdated($file) : new FileCreated($file));

        return $file;
    }

    /**
     * {@inheritdoc}
     */
    public function crop($id, $width, $height, $x = null, $y = null)
    {
        $image = $id instanceof File ? $id : $this->repository->find($id);
        $disk = $image->disk;
        $target = $this->makeFilename($image->object, '!' . base64_encode("cropped:$width:$height:$x:$y"));

        $file = $this->get($target, $disk);
        if (!$file) {
            $crop = Image::make($image->content)->crop($width, $height, $x, $y);
            $data = $crop->encode($image->mime_type, 100)->getEncoded();
            $hash = md5($data);
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
        }

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

        if ($disk) {
            $i = 1;
            while (Storage::disk($disk)->exists($filename)) {
                $filename = "{$basename}-{$i}{$ext}";
            }
        }

        return $filename;
    }
}