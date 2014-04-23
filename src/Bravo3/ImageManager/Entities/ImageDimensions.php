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

    function __construct($width = null, $height = null, $maintain_ratio = true, $upscale = true)
    {
        $this->width          = $width;
        $this->height         = $height;
        $this->upscale        = $upscale;
        $this->maintain_ratio = $maintain_ratio;
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
               'r'.($this->getMaintainRatio() ? '1' : '0');
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


}