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
    public function save($file, $group = 'default', $width = null, $height = null, $quality = 75)
    {
        $image = Image::make($file);

        if ($width || $height) {
            $image->resize($width, $height);
        }

        $data = $image->encode($image->mime(), $quality);
        $hash = sha1($data);
        //Create file if file is not exists, or return file instance
        $existFile = $this->findBy(['sha1' => $hash, 'group' => $group])->first();

        if (!$existFile) {
            $filename = $this->makeFilename($this->generateFilename($file, $group, $hash));
            Storage::disk($this->disk)->put($filename, $data);

            return $this->create([
                'filename' => $filename,
                'size' => Storage::disk($this->disk)->size($filename),
                'disk' => $this->disk,
                'mime_type' => Storage::disk($this->disk)->mimeType($filename),
                'sha1' => $hash,
                'group' => $group
            ]);
        } else {
            if (!Storage::disk($this->disk)->exists($existFile->filename)) {
                Storage::disk($this->disk)->put($existFile->filename, $data);
            }
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
        $existFile = $this->findBy(['sha1' => $hash, 'group' => $image->group])->first();
        if (!$existFile) {
            $filename = $this->makeFilename($this->makeFilename($image->filename, '_s', $image->disk));
            Storage::disk($image->disk)->put($filename, $data);
            return $this->create([
                'filename' => $filename,
                'size' => Storage::disk($this->disk)->size($filename),
                'disk' => $image->disk,
                'mime_type' => Storage::disk($this->disk)->mimeType($filename),
                'sha1' => $hash,
                'group' => $image->group,
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

    private function generateFilename($file, $group, $hash)
    {
        $clientFilename = $file instanceof UploadedFile ? $file->getClientOriginalName() : basename($file);
        $groupsConfig = Config::get('media.groups');
        $dirFormat = isset($groupsConfig[$group]['dir_format']) ? $groupsConfig[$group]['dir_format'] : null;
        $nameFormat = isset($groupsConfig[$group]['name_format']) ? $groupsConfig[$group]['name_format'] : null;
        $groupDir = implode('/', array_map(function ($sub) {
            return str_slug($sub, '_');
        }, explode('.', $group)));
        $doFormat = function ($format, $group = null, $clientFilename = null, $hash = null) {
            $basename = $clientFilename;
            $ext = '';
            if (($dotPos = strrpos($clientFilename, '.')) !== false) {
                $basename = implode('/', array_map(function ($sub) {
                    return str_slug($sub, '_');
                }, explode('/', substr($clientFilename, 0, $dotPos))));
                $ext = strtolower(substr($clientFilename, $dotPos));
            }
            $len = strlen($hash);
            $hashDir = $hash[$len-4] . $hash[$len-3] . '/' . $hash[$len-2] . $hash[$len-1];
            $hashDir3 = $hash[$len-6] . $hash[$len-5] . $hash[$len-4] . '/' . $hash[$len-3] . $hash[$len-2] . $hash[$len-1];
            return str_replace([
                '{group}',
                '{origin_name}',
                '{base_name}',
                '{ext}',
                '{hash}',
                '{hash_dir}',
                '{hash_dir_3}',
                '{YYYY}',
                '{YY}',
                '{MM}',
                '{DD}',
            ], [
                $group,
                $clientFilename,
                $basename,
                $ext,
                $hash,
                $hashDir,
                $hashDir3,
                date('Y'),
                date('y'),
                date('m'),
                date('d')
            ], $format);
        };
        $dir = trim($dirFormat ? $doFormat($dirFormat, $groupDir, $clientFilename, $hash) : $groupDir, '/');
        if ($nameFormat == '@' && strpos($clientFilename, '@') !== false) {
            $filenamePaths = explode('@', $clientFilename);
            $clientFilename = array_pop($filenamePaths);
            $filename = implode('/', $filenamePaths) . '/' . $clientFilename;
        } else {
            $filename = $doFormat('{hash_dir}/{origin_name}', $groupDir, $clientFilename, $hash);
        }

        return $dir . '/' . $filename;
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