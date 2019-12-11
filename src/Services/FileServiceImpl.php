<?php

namespace Viviniko\Media\Services\Impl;

use function GuzzleHttp\Psr7\mimetype_from_extension;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Viviniko\Media\Repositories\FileRepository;
use Viviniko\Media\Services\FileService;

class FileServiceImpl implements FileService
{
    /**
     * @var \Viviniko\Media\Repositories\FileRepository
     */
    protected $repository;

    /**
     * @var string
     */
    protected $disk;

    /**
     * @var \Illuminate\Contracts\Bus\Dispatcher
     */
    protected $dispatcher;

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
    public function put($source, $target)
    {
        $disk = $this->disk;

        if ($source instanceof UploadedFile) {
            $data = $source->get();
            $originalFilename = $source->getClientOriginalName();
            $mimeType = $source->getMimeType();
        } else if (filter_var($source, FILTER_VALIDATE_URL)) {
            $data = $this->getDataFromUrl($source);
            $originalFilename = $source;
            $mimeType = mimetype_from_extension($source);
        } else {
            $data = file_get_contents($source);
            $originalFilename = $source;
            $mimeType = mimetype_from_extension($source);
        }

        $hash = md5($data);
        $originalFilename = basename(urldecode($originalFilename));
        $attributes = [
            'disk' => $disk,
            'object' => $target,
            'size' => strlen($data),
            'mime_type' => $mimeType,
            'md5' => $hash,
            'original_filename' => $originalFilename
        ];

        return DB::transaction(function () use ($attributes, $data) {
            if ($file = $this->repository->findBy(['disk' => $attributes['disk'], 'object' => $attributes['object']])) {
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
    public function delete($id)
    {
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

    private function getDataFromUrl($url)
    {
        $options = [
            'http' => [
                'method'=>"GET",
                'header'=>"Accept-language: en\r\n". "User-Agent: Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.2 (KHTML, like Gecko) Chrome/22.0.1216.0 Safari/537.2\r\n"
            ]
        ];
        $context  = stream_context_create($options);
        $data = @file_get_contents($url, false, $context);

        if (empty($data)) {
            throw new \Exception("Unable to init from given url: $url.");
        }

        return $data;
    }
}