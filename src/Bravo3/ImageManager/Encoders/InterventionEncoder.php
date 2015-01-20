<?php
namespace Bravo3\ImageManager\Encoders;

use Bravo3\ImageManager\Entities\ImageDimensions;
use Bravo3\ImageManager\Enum\ImageFormat;
use Bravo3\ImageManager\Exceptions\BadImageException;
use Bravo3\ImageManager\Services\DataInspector;
use Intervention\Image\Image as InterventionImage;

class InterventionEncoder extends AbstractEncoder
{
    /**
     * Check if we support this data-type
     *
     * @param string $data
     * @return bool
     */
    public function supports(&$data)
    {
        $inspector = new DataInspector();
        return $inspector->getImageFormat($data) !== null;
    }

    /**
     * Create an image variation
     *
     * @param ImageFormat     $output_format
     * @param int             $quality
     * @param ImageDimensions $dimensions
     * @return string
     */
    public function createVariation(ImageFormat $output_format, $quality, ImageDimensions $dimensions = null)
    {
        try {
            $img = new InterventionImage($this->data);
        } catch (\Intervention\Image\Exception\InvalidImageDataStringException $e) {
            throw new BadImageException("Bad image data", 0, $e);
        } catch (\Intervention\Image\Exception\ImageNotFoundException $e) {
            throw new BadImageException("Not an image", 0, $e);
        }

        if ($dimensions) {
            if ($dimensions->getGrab()) {
                $img->grab($dimensions->getWidth(), $dimensions->getHeight());
            } else {
                $img->resize(
                    $dimensions->getWidth(),
                    $dimensions->getHeight(),
                    $dimensions->getMaintainRatio(),
                    $dimensions->canUpscale()
                );
            }
        }

        return $img->encode($output_format->key(), $quality);
    }
}
