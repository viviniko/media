<?php

namespace Viviniko\Media\Services\Image;

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
    public function save(UploadedFile $file, $dir = 'default', $width = null, $height = null, $quality = 100)
    {
        $image = Image::make($file);

        if ($width || $height) {
            $image->resize($width, $height);
        }

        $data = $image->encode($file->getMimeType(), $quality);

        $hash = sha1($data);

        //Create file if file is not exists, or return file instance
        $existFile = $this->findBy('sha1', $hash)->first();

        if (!$existFile) {
            $dir = $dir ? rtrim($dir, '/') : '';
            $dir .= "/$hash[0]$hash[1]/$hash[2]$hash[3]";
            $filename = ltrim($dir . '/' . $file->getClientOriginalName(), '/') ;
            $basename = $filename;
            $ext = '';
            if (($dotPos = strrpos($filename, '.')) !== false) {
                $basename = substr($filename, 0, $dotPos);
                $ext= substr($filename, $dotPos);
            }
            $i = 1;
            while (Storage::disk($this->disk)->exists($filename)) {
                $filename = "{$basename}-{$i}{$ext}";
                if (++$i > 10000) {
                    throw new \Exception('Error file name');
                }
            }
            Storage::disk($this->disk)->put($filename, $data);
            return $this->create([
                'filename' => $filename,
                'size' => $file->getSize(),
                'disk' => $this->disk,
                'mime_type' => $file->getMimeType(),
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
}