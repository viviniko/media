<?php

namespace Viviniko\Media\Services\Image;

use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Cache;
use Viviniko\Media\Contracts\ImageService as ImageServiceInterface;
use Viviniko\Repository\SimpleRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class EloquentImage extends SimpleRepository implements ImageServiceInterface
{
    protected $modelConfigKey = 'media.media';

    protected $fieldSearchable = ['filename' => 'like'];
    /**
     * @var string
     */
    protected $disk;

    /**
     * EloquentImage constructor.
     */
    public function __construct()
    {
        $this->disk = Config::get('media.disk', 'public');
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl($id)
    {
        if (Cache::getStore() instanceof TaggableStore) {
            return Cache::tags('medias')->remember('media.url?:' . $id, Config::get('cache.ttl', 60), function () use ($id) {
                return data_get(parent::find($id, ['disk', 'filename']), 'url');
            });
        }

        return data_get(parent::find($id, ['disk', 'filename']), 'url');
    }

    /**
     * {@inheritdoc}
     */
    public function save($file, $dir = 'default', $width = null, $height = null, $quality = 100)
    {
        $image = Image::make($file);

        if ($width || $height) {
            $image->resize($width, $height);
        }

        $data = $image->encode($image->mime(), $quality);

        $hash = sha1($data);

        //Create file if file is not exists, or return file instance
        $existFile = $this->findBy('sha1', $hash)->first();

        if (!$existFile) {
            $dir = $dir ? rtrim($dir, '/') : '';

            $dir .= "/$hash[0]$hash[1]/$hash[2]$hash[3]";

            $filename = $this->makeFilename(ltrim($dir . '/' . ($file instanceof UploadedFile ? $file->getClientOriginalName() : basename($file)), '/'));
            Storage::disk($this->disk)->put($filename, $data);
            return $this->create([
                'filename' => $filename,
                'size' => Storage::disk($this->disk)->size($filename),
                'disk' => $this->disk,
                'mime_type' => Storage::disk($this->disk)->mimeType($filename),
                'sha1' => $hash,
            ]);
        }

        return $existFile;
    }

    /**
     * {@inheritdoc}
     */
    public function crop($id, $width, $height, $x = null, $y = null)
    {
        $image = $this->find($id);
        $crop = Image::make(Storage::disk($image->disk)->path($image->filename))->crop($width, $height, $x, $y);
        $data = $crop->encode($image->mime_type, 100);
        $hash = sha1($data);
        //Create file if file is not exists, or return file instance
        $existFile = $this->findBy('sha1', $hash)->first();
        if (!$existFile) {
            $filename = $this->makeFilename($this->makeFilename($image->filename, '_s', $image->disk));
            Storage::disk($image->disk)->put($filename, $data);
            return $this->create([
                'filename' => $filename,
                'size' => Storage::disk($this->disk)->size($filename),
                'disk' => $image->disk,
                'mime_type' => Storage::disk($this->disk)->mimeType($filename),
                'sha1' => $hash,
            ]);
        }

        return $existFile;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        if ($picture = $this->find($id)) {
            Storage::disk($this->disk)->delete($picture->filename);
        }

        return parent::delete($id);
    }

    private function makeFilename($filename, $suffix = '', $disk = null)
    {
        $basename = $filename;
        $ext = '';
        if (($dotPos = strrpos($filename, '.')) !== false) {
            $basename = implode('/', array_map(function ($sub) {
                return str_slug($sub, '_');
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