<?php

namespace Bravo3\ImageManager\Services;

use Bravo3\ImageManager\Entities\Image;
use Bravo3\ImageManager\Entities\ImageMetadata;
use Bravo3\ImageManager\Entities\ImageDimensions;
use Bravo3\ImageManager\Entities\ImageVariation;
use Bravo3\ImageManager\Enum\ImageOrientation;
use Bravo3\ImageManager\Enum\ImageFormat;
use Bravo3\ImageManager\Exceptions\ImageManagerException;

/**
 * Image inspector service.
 */
class ImageInspector
{
    const ERR_SOURCE_IMAGE = 'Only source Image object can be used for retrieving metadata.';

    /**
     * Get orientation of an image from the supplied Image object.
     * NOTE: This function doesn't read EXIF data of the file to detect orientation
     * data.
     *
     * @param Image $image
     *
     * @return ImageOrientation
     */
    protected function getImageOrientation(Image $image)
    {
        if (!$image->isHydrated()) {
            throw new ImageManagerException(ImageManager::ERR_NOT_HYDRATED);
        }

        $img = new \Imagick();
        $img->readImageBlob($image->getData());
        $width  = $img->getImageWidth();
        $height = $img->getImageHeight();

        if ($width >= $height) {
            return ImageOrientation::LANDSCAPE();
        }

        return ImageOrientation::PORTRAIT();
    }

    /**
     * Return image dimensions based on the Image object provided.
     *
     * @param Image $image
     *
     * @return ImageDimensions
     */
    protected function getImageDimensions(Image $image)
    {
        if (!$image->isHydrated()) {
            throw new ImageManagerException(ImageManager::ERR_NOT_HYDRATED);
        }

        $img = new \Imagick();
        $img->readImageBlob($image->getData());

        $dimensions = new ImageDimensions(
            $img->getImageWidth(),
            $img->getImageHeight()
        );

        return $dimensions;
    }

    /**
     * Return image resolution based on the Image object provided.
     * Please note that this method seems to return the image density, or DPI,
     * not it's output resolution.
     *
     * @param Image $image
     *
     * @return ImageDimensions
     */
    protected function getImageResolution(Image $image)
    {
        if (!$image->isHydrated()) {
            throw new ImageManagerException(ImageManager::ERR_NOT_HYDRATED);
        }

        $img = new \Imagick();
        $img->readImageBlob($image->getData());
        $d = $img->getImageResolution();

        $dimensions = new ImageDimensions(
            $d['x'],
            $d['y']
        );

        return $dimensions;
    }

    /**
     * @param Image $image
     *
     * @return ImageMetadata
     */
    public function getImageMetadata(Image $image)
    {
        if (!$image->isHydrated()) {
            throw new ImageManagerException(ImageManager::ERR_NOT_HYDRATED);
        }

        if ($image instanceof ImageVariation) {
            throw new ImageManagerException(self::ERR_SOURCE_IMAGE);
        }

        $metadata       = new ImageMetadata();
        $data_inspector = new DataInspector();
        $data           = $image->getData();

        if ($data_inspector->isPdf($data)) {
            $format = ImageFormat::PDF();
        } else {
            $format = $data_inspector->getImageFormat($data);
        }

        $metadata
            ->setMimetype($data_inspector->guessMimeType($data))
            ->setFormat($format)
            ->setResolution($this->getImageResolution($image))
            ->setOrientation($this->getImageOrientation($image))
            ->setDimensions($this->getImageDimensions($image))
        ;

        return $metadata;
    }
}
