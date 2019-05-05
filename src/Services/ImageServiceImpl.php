<?php

namespace Viviniko\Media\Services\Impl;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Viviniko\Media\Events\FileCreated;
use Viviniko\Media\Events\FileDeleted;
use Viviniko\Media\FileExistsException;
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
     * @param \Illuminate\Contracts\Bus\Dispatcher $dispatcher
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
        $disk = $this->disk;
        if ($this->repository->findBy(['disk' => $disk, 'object' => $target])) {
            throw new FileExistsException();
        }

        $image = Image::make($source);
        $mimeType = $image->mime();
        if ($width || $height) {
            $image->resize($width, $height);
        }
        $data = $image->encode($mimeType, $quality);
        $hash = md5($data);
        $originalFilename = basename(urldecode($source instanceof UploadedFile ? $source->getClientOriginalName() : $source));

        Storage::disk($disk)->put($target, $data);
        $file = $this->repository->create([
            'disk' => $disk,
            'object' => $target,
            'size' => $image->filesize(),
            'mime_type' => $mimeType,
            'width' => $image->width(),
            'height' => $image->height(),
            'md5' => $hash,
            'original_filename' => $originalFilename
        ]);
        $this->dispatcher->dispatch(new FileCreated($file));

        return $file;
    }

    /**
     * {@inheritdoc}
     */
    public function crop($id, $width, $height, $x = null, $y = null)
    {
        $image = $this->repository->find($id);
        $disk = $image->disk;
        $crop = Image::make(Storage::disk($disk)->path($image->object))->crop($width, $height, $x, $y);
        $data = $crop->encode($image->mime_type, 100);
        $hash = md5($data);

        while (($target = $this->makeFilename($image->object, '_s', $disk)) && $this->repository->findBy(['disk' => $disk, 'object' => $target]));

        Storage::disk()->put($target, $data);
        $file = $this->repository->create([
            'disk' => $disk,
            'object' => $target,
            'size' => $image->filesize(),
            'mime_type' => $image->mime_type,
            'width' => $image->width(),
            'height' => $image->height(),
            'md5' => $hash,
            'original_filename' => $image->original_filename,
        ]);
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