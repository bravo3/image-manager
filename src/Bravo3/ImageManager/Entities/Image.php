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
    protected $image;

    /**
     * @var boolean
     */
    protected $persistent = false;


    protected function __construct($key)
    {
        $this->key = $key;
    }


    /**
     * Flush image data from memory
     */
    public function flush()
    {
        $this->image = null;
    }

    /**
     * Set the underlying image data
     *
     * @param InterventionImage $image
     * @return $this
     */
    public function setImage($image)
    {
        $this->image = $image;
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


}