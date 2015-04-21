<?php

namespace Bravo3\ImageManager\Encoders;

use Bravo3\ImageManager\Entities\ImageDimensions;
use Bravo3\ImageManager\Entities\ImageCropDimensions;
use Bravo3\ImageManager\Enum\ImageFormat;

interface EncoderInterface
{
    /**
     * Check if we support this data-type.
     *
     * @param string $data
     *
     * @return bool
     */
    public function supports(&$data);

    /**
     * Set the encoders data object.
     *
     * @param string $data
     */
    public function setData($data);

    /**
     * Create an image variation.
     *
     * @param ImageFormat         $output_format
     * @param int                 $quality
     * @param ImageDimensions     $dimensions
     * @param ImageCropDimensions $crop_dimensions
     *
     * @return string
     */
    public function createVariation(ImageFormat $output_format, $quality,
        ImageDimensions $dimensions = null, ImageCropDimensions $crop_dimensions);
}
