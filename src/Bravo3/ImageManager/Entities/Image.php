<?php
namespace Bravo3\ImageManager\Entities;

use Bravo3\ImageManager\Enum\ImageFormat;
use Bravo3\ImageManager\Exceptions\ImageManagerException;
use Intervention\Image\Image as InterventionImage;

class Image
{

    /**
     * @var string
     */
    protected $key;

    /**
     * @var InterventionImage
     */
    protected $image = null;

    /**
     * @var boolean
     */
    protected $persistent = false;


    function __construct($key = null)
    {
        $this->key = $key;
    }


    /**
     * Flush image data from memory
     */
    public function flush($collect_garbage = true)
    {
        $this->image = null;

        if ($collect_garbage) {
            gc_collect_cycles();
        }
    }

    /**
     * Set the underlying image data
     *
     * @param InterventionImage $image
     * @param bool              $persistent True if the source is the remote
     * @return $this
     */
    public function setImage($image, $persistent = false)
    {
        $this->image      = $image;
        $this->persistent = $persistent;

        return $this;
    }

    /**
     * Get the underlying image data
     *
     * @return InterventionImage
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Get the binary content of the image
     *
     * @param int         $quality
     * @param ImageFormat $format Null for auto-detect based on key, if no key exists an exception will be thrown
     * @return string
     */
    public function getContent($quality = 90, ImageFormat $format = null)
    {
        if (!$this->isHydrated()) {
            return null;
        }

        if (!$format) {
            if (!$this->getKey()) {
                throw new ImageManagerException("A key must exist when not specifying a format");
            }

            $ext = pathinfo($this->getKey(), PATHINFO_EXTENSION);
        } else {
            $ext = $format->key();
        }

        return $this->image->encode($ext, $quality);
    }

    /**
     * Set the remote key
     *
     * @param string $key
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * Get the remote key
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Check if the image is known to exist on the remote
     *
     * @return boolean
     */
    public function isPersistent()
    {
        return $this->persistent;
    }

    /**
     * Check if the image data has been loaded
     *
     * @return bool
     */
    public function isHydrated()
    {
        return $this->image !== null;
    }


    private $__friends = ['Bravo3\ImageManager\Services\ImageManager'];

    /**
     * Property setter for friend classes
     *
     * @access private
     * @param string $key
     * @param mixed  $value
     * @return mixed
     * @throws \Exception
     */
    public function __friendSet($key, $value)
    {
        $trace = debug_backtrace();
        if (isset($trace[1]['class']) && in_array($trace[1]['class'], $this->__friends)) {
            return $this->$key = $value;
        } else {
            throw new \Exception("Property is private");
        }
    }

}