<?php

namespace Viviniko\Media\Services\Impl;

use Curl\Curl;
use Viviniko\Media\DiskObject;
use Viviniko\Media\Models\File;
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
        return $this->repository->findBy(['url' => DiskObject::create($disk ?: $this->disk, $object)->toUrl()]);
    }

    /**
     * {@inheritdoc}
     */
    public function has($object, $disk = null)
    {
        return $this->repository->exists(['url' => DiskObject::create($disk ?: $this->disk, $object)->toUrl()]);
    }

    /**
     * {@inheritdoc}
     */
    public function put($source, $target)
    {
        $file = $this->parse($source);
        $file->disk = $this->disk;
        $file->object = $target;

        return DB::transaction(function () use ($file) {
            if ($exists = $this->get($file->object, $file->disk)) {
                if (!empty($file->md5) && $file->md5 !== $exists['md5']) {
                    $exists = $this->repository->update($file->id, $file->toArray())->setContent($file->content);
                }
            } else {
                $exists = $this->repository->create($file->toArray())->setContent($file->content);
            }

            return $exists;
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

    public function parse($source)
    {
        if ($source instanceof UploadedFile) {
            $data = $source->get();
            $originalFilename = $source->getClientOriginalName();
            $mimeType = $source->getMimeType();
        } else if (filter_var($source, FILTER_VALIDATE_URL)) {
            $data = $this->getDataFromUrl($source);
            $info = parse_url($source);
            $originalFilename = basename($info['path']);
            $mimeType = mimetype_from_extension(substr($originalFilename, strrpos($originalFilename, '.')+1));
        } else {
            $data = file_get_contents($source);
            $originalFilename = $source;
            $mimeType = mime_content_type($source);
        }
        throw_if(!$data, new \InvalidArgumentException("Parse File [$source] Failed"));
        $originalFilename = basename(urldecode($originalFilename));
        return new File([
            'size' => strlen($data),
            'mime_type' => $mimeType,
            'original_filename' => $originalFilename
        ]);
    }

    private function getDataFromUrl(&$url)
    {
        $curl = new Curl();
        $curl->setUserAgent('Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.2 (KHTML, like Gecko) Chrome/22.0.1216.0 Safari/537.2');
        $curl->setOpt(CURLOPT_AUTOREFERER, 1);
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, 1);
        $locations = [];
        $curl->setOpt(CURLOPT_HEADERFUNCTION, function($ch, $header) use(&$locations) {
            $key = 'Location:';
            if (strpos($header, $key) === 0) {
                $locations[] = trim(substr($header, strlen($key)));
            }
            return strlen($header);
        });

        $curl->get($url);

        if ($curl->error) {
            throw new \Exception("Unable to init from given url: $url, Error[{$curl->errorCode}]: {$curl->errorMessage}");
        }

        if (!empty($locations)) {
            $url = array_pop($locations);
        }

        return $curl->response;
    }
}