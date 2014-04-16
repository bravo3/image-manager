<?php
namespace Bravo3\ImageManager\Entities;

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
     * Get the content-type of the image
     *
     * @return string
     */
    public function getContentType()
    {

    }

    /**
     * Get the binary content of the image
     *
     * @return string
     */
    public function getContent()
    {

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

}