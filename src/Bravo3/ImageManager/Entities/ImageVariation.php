<?php
namespace Bravo3\ImageManager\Entities;

use Bravo3\ImageManager\Enum\ImageFormat;
use Bravo3\ImageManager\Exceptions\ImageManagerException;
use Bravo3\ImageManager\Traits\FriendTrait;

class ImageVariation extends Image
{
    use FriendTrait;

    const DEFAULT_QUALITY = 90;
    protected $__friends = ['Bravo3\ImageManager\Services\ImageManager'];

    /**
     * Create a new image variation
     *
     * @param string          $key
     * @param ImageFormat     $format
     * @param int             $quality
     * @param ImageDimensions $dimensions
     * @throws ImageManagerException
     */
    function __construct(
        $key,
        ImageFormat $format,
        $quality = self::DEFAULT_QUALITY,
        ImageDimensions $dimensions = null
    ) {
        parent::__construct($key);
        $this->dimensions = $dimensions;
        $this->format     = $format;
        $this->quality    = $quality;
    }

    /**
     * Get variation or image key
     *
     * @param bool $parent
     * @return string
     */
    public function getKey($parent = false)
    {
        if ($parent) {
            return parent::getKey();
        } else {
            // TODO: add a configurable naming scheme here
            return parent::getKey().'~'.$this;
        }
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

    /**
     * Creates a signature based on the variations applied
     *
     * @return string
     */
    public function __toString()
    {
        $out = 'q'.($this->getQuality() ?: self::DEFAULT_QUALITY).',d'.($this->getDimensions() ?: '--').
               '.'.(string)$this->getFormat()->value();

        return $out;
    }
}
