<?php

namespace Bravo3\ImageManager\Encoders;

use Bravo3\ImageManager\Entities\ImageDimensions;
use Bravo3\ImageManager\Entities\ImageCropDimensions;
use Bravo3\ImageManager\Enum\ImageFormat;
use Bravo3\ImageManager\Services\DataInspector;

class ImagickEncoder extends AbstractFilesystemEncoder
{
    /**
     * @var int
     */
    protected $filter;

    /**
     * @var int
     */
    protected $resolution;

    /**
     * @param int $filter     Resampling filter
     * @param int $resolution Read resolution - higher values will increase the quality of vector image rasterisation
     */
    public function __construct($filter = \Imagick::FILTER_LANCZOS, $resolution = 300)
    {
        parent::__construct();

        $this->filter     = $filter;
        $this->resolution = $resolution;
    }

    /**
     * Check if we support this data-type.
     *
     * @param string $data
     *
     * @return bool
     */
    public function supports(&$data)
    {
        $inspector = new DataInspector();

        // Normal image formats supported
        if ($inspector->getImageFormat($data) !== null) {
            return true;
        }

        // PDF supported
        if ($inspector->isPdf($data)) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function createVariation(ImageFormat $output_format, $quality,
        ImageDimensions $dimensions = null, ImageCropDimensions $crop_dimensions = null)
    {
        $src  = $this->getTempFile($this->data);
        $img = new \Imagick();
        $img->setResolution($this->resolution, $this->resolution);
        $img->readImage($src);
        $img->setIteratorIndex(0);

        // Flatten images here helps the encoder to get rid of the black background
        // that appears on encoded image files.
        $img = $img->flattenImages();
        $img->setImageFormat((string) $output_format->value());
        $img->setImageCompressionQuality($quality);

        if (null !== $crop_dimensions) {
            $img->cropImage(
                $crop_dimensions->getWidth(),
                $crop_dimensions->getHeight(),
                $crop_dimensions->getX(),
                $crop_dimensions->getY()
            );
        }

        if (null !== $dimensions) {
            $img->resizeImage(
                $dimensions->getWidth() ?: 0,
                $dimensions->getHeight() ?: 0,
                $this->filter,
                1,
                false
            );
        }

        return $img->getImageBlob();
    }
}
