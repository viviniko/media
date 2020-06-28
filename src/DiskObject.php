<?php

namespace Viviniko\Media;

use Illuminate\Support\Facades\Storage;

class DiskObject
{
    const SCHEME = "disk";

    /**
     * @var string
     */
    private $disk;

    /**
     * @var string
     */
    private $object;

    public function __construct($disk, $object)
    {
        $this->disk = $disk;
        $this->object = $object;
    }

    /**
     * @return string
     */
    public function getDisk(): string
    {
        return $this->disk;
    }

    /**
     * @param string $disk
     */
    public function setDisk(string $disk)
    {
        $this->disk = $disk;
    }

    /**
     * @return string
     */
    public function getObject(): string
    {
        return $this->object;
    }

    /**
     * @param string $object
     */
    public function setObject(string $object)
    {
        $this->object = $object;
    }

    public function toDiskUrl()
    {
        return empty($this->disk) ? $this->object : (self::SCHEME . "://{$this->disk}/{$this->object}");
    }

    public function content()
    {
        return empty($this->disk) ? file_get_contents($this->object) : Storage::disk($this->disk)->get($this->object);
    }

    public function put($content)
    {
        if (empty($this->disk)) {
            throw new \Exception("Disk is empty.");
        }

        return Storage::disk($this->disk)->put($this->object, $content);
    }

    public function toUrl()
    {
        return empty($this->disk) ? $this->object : Storage::disk($this->disk)->url($this->object);
    }

    public function toArray()
    {
        return ['disk' => $this->disk, 'object' => $this->object];
    }

    public function toString()
    {
        return $this->toDiskUrl();
    }

    public static function create($disk, $object)
    {
        return new self($disk, $object);
    }

    public static function valueOf($url)
    {
        list($scheme, $diskObject) = explode('://', $url, 2);
        if ($scheme == self::SCHEME) {
            return self::create(...explode('/', $diskObject, 2));
        }

        return self::create(null, $url);
    }
}