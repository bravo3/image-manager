<?php
namespace Bravo3\ImageManager\Entities;

use Bravo3\ImageManager\Enum\ImageFormat;
use Bravo3\ImageManager\Traits\FriendTrait;

class ImageVariation extends Image
{
    use FriendTrait;
    protected $__friends = ['Bravo3\ImageManager\Services\ImageManager'];


    /**
     * Create a new image variation
     *
     * @param Image           $image
     * @param int             $quality
     * @param null            $format
     * @param ImageDimensions $dimensions
     */
    function __construct($key, $quality = 90, $format = null, ImageDimensions $dimensions = null)
    {
        parent::__construct($key);
        $this->dimensions = $dimensions;
        $this->format     = $format;
        $this->quality    = $quality;
    }

    /**
     * Get Dimensions
     *
     * @return ImageDimensions
     */
    public function getDimensions()
    {
        return $this->dimensions;
    }

    /**
     * Get Format
     *
     * @return ImageFormat
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Get Quality
     *
     * @return int
     */
    public function getQuality()
    {
        return $this->quality;
    }


}