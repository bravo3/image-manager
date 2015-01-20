<?php
namespace Bravo3\ImageManager\Encoders;

use Bravo3\ImageManager\Entities\ImageDimensions;
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
     * Check if we support this data-type
     *
     * @param string $data
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
     * Create an image variation
     *
     * @param ImageFormat     $output_format
     * @param int             $quality
     * @param ImageDimensions $dimensions
     * @return string
     */
    public function createVariation(ImageFormat $output_format, $quality, ImageDimensions $dimensions = null)
    {
        $src  = $this->getTempFile($this->data);
        $dest = $this->getTempFile();

        $img = new \Imagick();
        $img->setResolution($this->resolution, $this->resolution);
        $img->readImage($src);

        $img->setImageIndex(0);
        $img->setImageFormat((string)$output_format->value());
        $img->setImageCompressionQuality($quality);

        if ($dimensions) {
            $img->resizeImage(
                $dimensions->getWidth() ?: 0,
                $dimensions->getHeight() ?: 0,
                $this->filter,
                1,
                false
            );
        }
        $img->writeImage((string)$output_format->value().':'.$dest);

        return file_get_contents($dest);
    }
}
