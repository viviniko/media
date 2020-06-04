<?php

namespace Viviniko\Media;

class DiskObject
{
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

    public function toUrl()
    {
        return "{$this->disk}://{$this->object}";
    }

    public function toString()
    {
        return $this->toUrl();
    }

    public static function create($disk, $object)
    {
        return new self($disk, $object);
    }

    public static function valueOf($url)
    {
        return self::create(...explode('://', $url));
    }
}