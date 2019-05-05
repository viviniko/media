<?php

namespace Viviniko\Media\Services\Impl;

use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Viviniko\Media\FileExistsException;
use Viviniko\Media\Repositories\FileRepository;
use Viviniko\Media\Services\ImageService;

class ImageServiceImpl implements ImageService
{
    /**
     * @var \Viviniko\Media\Repositories\FileRepository
     */
    protected $repository;

    /**
     * @var string
     */
    private $disk;

    /**
     * @var array
     */
    protected $searchRules = ['original_filename' => 'like', 'object' => 'like'];

    /**
     * ImageServiceImpl constructor.
     * @param FileRepository $repository
     */
    public function __construct(FileRepository $repository)
    {
        $this->repository = $repository;
        $this->disk = Config::get('media.disk');
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl($id)
    {
        return data_get($this->repository->find($id, ['disk', 'object']), 'url');
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
        return $this->repository->create([
            'disk' => $disk,
            'object' => $target,
            'size' => $image->filesize(),
            'mime_type' => $mimeType,
            'width' => $image->width(),
            'height' => $image->height(),
            'md5' => $hash,
            'original_filename' => $originalFilename
        ]);
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
        return $this->repository->create([
            'disk' => $disk,
            'object' => $target,
            'size' => $image->filesize(),
            'mime_type' => $image->mime_type,
            'width' => $image->width(),
            'height' => $image->height(),
            'md5' => $hash,
            'original_filename' => $image->original_filename,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        if ($media = $this->repository->find($id)) {
            Storage::disk($media->disk)->delete($media->object);
        }

        return $this->repository->delete($id);
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