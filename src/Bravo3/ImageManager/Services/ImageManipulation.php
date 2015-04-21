<?php

namespace Bravo3\ImageManager\Services;

use Bravo3\ImageManager\Entities\Image;
use Bravo3\ImageManager\Entities\ImageDimensions;
use Bravo3\ImageManager\Enum\ImageOrientation;
use Bravo3\ImageManager\Exceptions\BadImageException;

/**
 * Image manipulation service.
 */
class ImageManipulation
{
    /**
     * Get orientation of an image from the supplied Image object.
     * NOTE: This function doesn't read EXIF data of the file to detect orientation
     * data.
     *
     * @param Image $image
     *
     * @return ImageOrientation
     */
    public function getImageOrientation(Image $image)
    {
        if (empty($image->getData())) {
            throw new BadImageException();
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
    public function getImageDimensions(Image $image)
    {
        if (empty($image->getData())) {
            throw new BadImageException();
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
    public function getImageResolution(Image $image)
    {
        if (empty($image->getData())) {
            throw new BadImageException();
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
}
