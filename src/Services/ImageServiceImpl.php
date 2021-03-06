<?php

namespace Viviniko\Media\Services\Impl;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Viviniko\Media\DiskObject;
use Viviniko\Media\Models\File;
use Viviniko\Media\Repositories\FileRepository;
use Viviniko\Media\Services\ImageService;

class ImageServiceImpl extends FileServiceImpl implements ImageService
{
    /**
     * ImageServiceImpl constructor.
     * @param \Viviniko\Media\Repositories\FileRepository $repository
     * @param \Illuminate\Contracts\Events\Dispatcher $dispatcher
     */
    public function __construct(FileRepository $repository, Dispatcher $dispatcher)
    {
        parent::__construct($repository, $dispatcher);
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
            'disk_url' => DiskObject::create($disk, $target)->toDiskUrl(),
            'size' => strlen($data),
            'mime_type' => $mimeType,
            'md5' => $hash,
            'original_filename' => $originalFilename
        ];

        return DB::transaction(function () use ($attributes, $data) {
            if ($file = $this->repository->findBy(['disk_url' => $attributes['disk_url']])) {
                if (!empty($file->md5) && $file->md5 !== $attributes['md5']) {
                    $file = $this->repository->update($file->id, $attributes)->setContent($data);
                }
            } else {
                $file = $this->repository->create($attributes)->setContent($data);
            }

            return $file;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function crop($id, $width, $height, $x = null, $y = null)
    {
        $image = $id instanceof File ? $id : $this->repository->find($id);
        list($disk, $object) = array_values($image->diskObject->toArray());
        $target = $this->makeFilename($object, '!' . "crop-{$image->id}_{$width}_{$height}_{$x}_{$y}");

        $file = $this->get($target, $disk);
        if (!$file) {
            $crop = Image::make($image->content)->crop($width, $height, $x, $y);
            $data = $crop->encode($image->mime_type, 100)->getEncoded();
            $hash = md5($data);
            $attributes = [
                'disk_url' => DiskObject::create($disk, $target)->toDiskUrl(),
                'size' => strlen($data),
                'mime_type' => $crop->mime(),
                'md5' => $hash,
                'original_filename' => $image->original_filename,
            ];
            $file = DB::transaction(function () use ($attributes, $data) {
                return $this->repository->create($attributes)->setContent($data);
            });
        }

        return $file;
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