<?php

namespace Bravo3\ImageManager\Entities;

use Bravo3\ImageManager\Enum\ImageFormat;
use Bravo3\ImageManager\Exceptions\ImageManagerException;
use Bravo3\ImageManager\Services\DataInspector;
use Bravo3\ImageManager\Traits\FriendTrait;

class Image
{
    use FriendTrait;

    protected $__friends = ['Bravo3\ImageManager\Services\ImageManager'];

    /**
     * @var string
     */
    protected $key;

    /**
     * @var string
     */
    protected $data = null;

    /**
     * @var string
     */
    protected $raw_content_type = null;

    /**
     * @var bool
     */
    protected $persistent = false;

    /**
     * @param string $key
     *
     * @throws ImageManagerException
     */
    public function __construct($key)
    {
        if (!$key) {
            throw new ImageManagerException('Invalid key');
        }
        $this->key = $key;
    }

    /**
     * Flush image data from memory.
     *
     * @param bool $collect_garbage
     */
    public function flush($collect_garbage = true)
    {
        $this->data = null;

        if ($collect_garbage) {
            gc_collect_cycles();
        }
    }

    /**
     * Check the data data for the image type.
     *
     * If unknown or no data data is present, null will be returned
     *
     * @deprecated since 1.1.0 Use DataInspector::getImageFormat() instead
     *
     * @return ImageFormat|null
     */
    public function getDataFormat()
    {
        $inspector = new DataInspector();

        return $inspector->getImageFormat($this->data);
    }

    /**
     * Set the remote key.
     *
     * @param string $key
     *
     * @return $this
     */
    public function setKey($key)
    {
        $this->key        = $key;
        $this->persistent = false;

        return $this;
    }

    /**
     * Get the remote key.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Check if the image is known to exist on the remote.
     *
     * @return bool
     */
    public function isPersistent()
    {
        return $this->persistent;
    }

    /**
     * Check if the image data has been loaded.
     *
     * @return bool
     */
    public function isHydrated()
    {
        return !empty($this->data) && !is_null($this->data);
    }

    /**
     * Set image data.
     *
     * @param string $data
     *
     * @return $this
     */
    public function setData($data)
    {
        $this->data       = $data;
        $this->persistent = false;

        // Guess MIME-type
        $inspector              = new DataInspector();
        $this->raw_content_type = $inspector->guessMimeType($this->data);

        return $this;
    }

    /**
     * Return the content-type of data specified.
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->raw_content_type;
    }

    /**
     * Get image data.
     *
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }
}
