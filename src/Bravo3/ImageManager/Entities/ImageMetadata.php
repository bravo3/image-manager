<?php

namespace Bravo3\ImageManager\Entities;

use Bravo3\ImageManager\Enum\ImageOrientation;
use Bravo3\ImageManager\Enum\ImageFormat;

/**
 * Purpose of the metadata class is to keep the source image
 * properties. ImageManager stores the serialized metadata object within
 * the cache layer.
 */
class ImageMetadata
{
    /**
     * Internet media type.
     *
     * @var string
     */
    protected $mimetype;

    /**
     * ImageManager format.
     *
     * @var ImageFormat
     */
    protected $format;

    /**
     * Image resolution.
     *
     * @var int
     */
    protected $dpi;

    /**
     * Orientation of the image.
     *
     * @var ImageOrientation
     */
    protected $orientation;

    /**
     * Source image dimensions.
     *
     * @var ImageDimensions
     */
    protected $dimensions;

    /**
     * Gets the Internet media type.
     *
     * @return string
     */
    public function getMimetype()
    {
        return $this->mimetype;
    }

    /**
     * Sets the Internet media type.
     *
     * @param string $mimetype the mimetype
     *
     * @return self
     */
    protected function setMimetype($mimetype)
    {
        $this->mimetype = $mimetype;

        return $this;
    }

    /**
     * Gets the ImageManager format.
     *
     * @return ImageFormat
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Sets the ImageManager format.
     *
     * @param ImageFormat $format the format
     *
     * @return self
     */
    protected function setFormat(ImageFormat $format)
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Gets the Image resolution.
     *
     * @return int
     */
    public function getDpi()
    {
        return $this->dpi;
    }

    /**
     * Sets the Image resolution.
     *
     * @param int $dpi the dpi
     *
     * @return self
     */
    protected function setDpi($dpi)
    {
        $this->dpi = $dpi;

        return $this;
    }

    /**
     * Gets the Orientation of the image.
     *
     * @return ImageOrientation
     */
    public function getOrientation()
    {
        return $this->orientation;
    }

    /**
     * Sets the Orientation of the image.
     *
     * @param ImageOrientation $orientation the orientation
     *
     * @return self
     */
    protected function setOrientation(ImageOrientation $orientation)
    {
        $this->orientation = $orientation;

        return $this;
    }

    /**
     * Gets the Source image dimensions.
     *
     * @return ImageDimensions
     */
    public function getDimensions()
    {
        return $this->dimensions;
    }

    /**
     * Sets the Source image dimensions.
     *
     * @param ImageDimensions $dimensions the dimensions
     *
     * @return self
     */
    protected function setDimensions(ImageDimensions $dimensions)
    {
        $this->dimensions = $dimensions;

        return $this;
    }
}
