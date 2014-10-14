<?php
namespace Bravo3\ImageManager\Entities;

/**
 * A set of rules for resampling images
 */
class ImageDimensions
{

    /**
     * @var int
     */
    protected $width;

    /**
     * @var int
     */
    protected $height;

    /**
     * @var bool
     */
    protected $upscale;

    /**
     * @var bool
     */
    protected $maintain_ratio;

    /**
     * @var bool
     */
    protected $grab;

    function __construct($width = null, $height = null, $maintain_ratio = true, $upscale = true, $grab = false)
    {
        $this->width          = $width;
        $this->height         = $height;
        $this->upscale        = $upscale;
        $this->maintain_ratio = $maintain_ratio;
        $this->grab           = $grab;
    }

    /**
     * Creates a signature containing the dimension specification
     *
     * @return string
     */
    public function __toString()
    {
        return 'x'.($this->getWidth() ? : '-').
               'y'.($this->getHeight() ? : '-').
               'u'.($this->canUpscale() ? '1' : '0').
               'r'.($this->getMaintainRatio() ? '1' : '0').
               'g'.($this->getGrab() ? 'g' : '0');
    }

    /**
     * Get proposed width
     *
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Get proposed height
     *
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Check if the image can be upscaled
     *
     * @return boolean
     */
    public function canUpscale()
    {
        return $this->upscale;
    }

    /**
     * Check if we should maintain the image ratio
     *
     * @return boolean
     */
    public function getMaintainRatio()
    {
        return $this->maintain_ratio;
    }

    /**
     * Check if also crop as well as resize
     *
     * @return boolean
     */
    public function getGrab()
    {
        return $this->grab;
    }
}