<?php
namespace Bravo3\ImageManager\Entities;

use Bravo3\ImageManager\Enum\ImageFormat;
use Bravo3\ImageManager\Exceptions\BadImageException;
use Bravo3\ImageManager\Exceptions\ImageManagerException;
use Bravo3\ImageManager\Exceptions\IoException;
use Intervention\Image\Image as InterventionImage;

class Image
{

    /**
     * @var string
     */
    protected $key;

    /**
     * @var string
     */
    protected $raw = null;

    /**
     * @var string
     */
    protected $raw_content_type = null;

    /**
     * @var boolean
     */
    protected $persistent = false;


    function __construct($key = null)
    {
        $this->key = $key;
    }


    /**
     * Flush image data from memory
     */
    public function flush($collect_garbage = true)
    {
        $this->raw = null;

        if ($collect_garbage) {
            gc_collect_cycles();
        }
    }

    /**
     * Creates the Intervention Image object from the data data
     *
     * @throws ImageManagerException
     */
    protected function createImageFromRawData()
    {
        if (!$this->raw) {
            throw new ImageManagerException("Unable to create image without raw data");
        }

        return new InterventionImage($this->raw);
    }

    /**
     * Get the binary content of the image
     *
     * This will be the original file data unless $quantity or $format are specified, in which the image will be
     * re-rendered to meet the requirements. If this image is a variation, it will always be re-rendered.
     *
     * @param int         $quality Between 1-100, defaults to 90 if null is provided
     * @param ImageFormat $format  Null to use the original format
     * @return string
     */
    public function getContent($quality = null, ImageFormat $format = null)
    {
        if (!$this->isHydrated()) {
            return null;
        }

        if ($quality !== null || $format !== null) {
            $ext     = $format ? $format->key() : $this->getRawFormat();
            $quality = $quality ? : 90;

            if ($quality < 1) {
                $quality = 1;
            } elseif ($quality > 100) {
                $quality = 100;
            }

            if ($ext === null) {
                throw new BadImageException("Unknown image format");
            }

            return $this->createImageFromRawData()->encode($ext, $quality);
        } else {
            return $this->raw;
        }

    }

    /**
     * Check the data data for the image type
     *
     * If unknown or no data data is present, null will be returned
     *
     * @return ImageFormat|null
     */
    public function getRawFormat()
    {
        if (!$this->raw || strlen($this->raw) < 5) {
            return null;
        }

        // JPEG: FF D8
        if ($this->raw{0} == 0xff && $this->raw{1} == 0xd8) {
            return ImageFormat::JPEG();
        }

        // PNG: 89 50 4E 47
        if ($this->raw{0} == 0xff && substr($this->raw, 1, 3) == 'PNG') {
            return ImageFormat::PNG();
        }

        // GIF87a: 47 49 46 38 37 61
        // GIF89a: 47 49 46 38 39 61
        if (substr($this->raw, 0, 6) == 'GIF87a' || substr($this->raw, 0, 6) == 'GIF89a') {
            return ImageFormat::GIF();
        }

        return null;
    }

    /**
     * Set the remote key
     *
     * @param string $key
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * Get the remote key
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Check if the image is known to exist on the remote
     *
     * @return boolean
     */
    public function isPersistent()
    {
        return $this->persistent;
    }

    /**
     * Check if the image data has been loaded
     *
     * @return bool
     */
    public function isHydrated()
    {
        return $this->raw !== null;
    }


    /**
     * Set the data image binary data as per the file format
     *
     * @param string $data
     * @return $this
     */
    public function load($data)
    {
        $this->raw        = $data;
        $this->persistent = false;

        return $this;
    }

    /**
     * Set the data image binary data as per the file format
     *
     * @param string $fn
     * @return $this
     */
    public function loadFromFile($fn)
    {
        if (!is_readable($fn)) {
            throw new IoException("File not readable: ".$fn);
        }

        $this->raw        = file_get_contents($fn);
        $this->persistent = false;

        return $this;
    }

    private $__friends = ['Bravo3\ImageManager\Services\ImageManager'];

    /**
     * Property setter for friend classes
     *
     * @access private
     * @param string $key
     * @param mixed  $value
     * @return mixed
     * @throws \Exception
     */
    public function __friendSet($key, $value)
    {
        $trace = debug_backtrace();
        if (isset($trace[1]['class']) && in_array($trace[1]['class'], $this->__friends)) {
            return $this->$key = $value;
        } else {
            throw new \Exception("Property is private");
        }
    }


}
