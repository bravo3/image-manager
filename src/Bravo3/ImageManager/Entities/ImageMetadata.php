<?php

namespace Bravo3\ImageManager\Entities;

use Bravo3\ImageManager\Entities\Interfaces\SerialisableInterface;
use Bravo3\ImageManager\Enum\ImageOrientation;
use Bravo3\ImageManager\Enum\ImageFormat;
use Bravo3\ImageManager\Exceptions\InvalidImageMetadataException;

/**
 * Purpose of the metadata class is to keep the source image
 * properties. ImageManager stores the serialized metadata object within
 * the cache layer.
 */
class ImageMetadata implements SerialisableInterface
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
     * @var ImageDimensions
     */
    protected $resolution = null;

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
    public function setMimetype($mimetype)
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
    public function setFormat(ImageFormat $format)
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Gets the Image resolution.
     *
     * @return ImageDimensions
     */
    public function getResolution()
    {
        return $this->resolution;
    }

    /**
     * Sets the Image resolution.
     *
     * @param ImageDimensions
     *
     * @return self
     */
    public function setResolution(ImageDimensions $resolution)
    {
        $this->resolution = $resolution;

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
    public function setOrientation(ImageOrientation $orientation)
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
    public function setDimensions(ImageDimensions $dimensions)
    {
        $this->dimensions = $dimensions;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function serialise()
    {
        return json_encode([
            'mimetype'    => $this->getMimetype(),
            'format'      => $this->getFormat()->value(),
            'resolution'  => $this->getResolution()->serialise(),
            'orientation' => $this->getOrientation()->value(),
            'dimensions'  => $this->getDimensions()->serialise(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function deserialise($json)
    {
        if (empty($json)) {
            throw new InvalidImageMetadataException();
        }

        $object_data = json_decode($json, true);

        if (isset($object_data['mimetype'])) {
            throw new InvalidImageMetadataException();
        }

        $instance = new static();
        $instance
            ->setMimeType($object_data['mimetype'])
            ->setFormat(ImageFormat::memberByValue($object_data['format']))
            ->setResolution(ImageDimensions::deserialise($object_data['resolution']))
            ->setOrientation(ImageOrientation::memberByValue($object_data['orientation']))
            ->setDimensions(ImageDimensions::deserialise($object_data['dimensions']))
        ;

        return $instance;
    }
}
