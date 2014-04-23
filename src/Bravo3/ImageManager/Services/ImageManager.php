<?php
namespace Bravo3\ImageManager\Services;

use Bravo3\Cache\PoolInterface;
use Bravo3\ImageManager\Entities\Image;
use Bravo3\ImageManager\Entities\ImageDimensions;
use Bravo3\ImageManager\Entities\ImageVariation;
use Bravo3\ImageManager\Enum\ImageFormat;
use Bravo3\ImageManager\Exceptions\BadImageException;
use Bravo3\ImageManager\Exceptions\ObjectAlreadyExistsException;
use Bravo3\ImageManager\Exceptions\ImageManagerException;
use Bravo3\ImageManager\Exceptions\IoException;
use Bravo3\ImageManager\Exceptions\NotExistsException;
use Gaufrette\Exception\FileAlreadyExists;
use Gaufrette\Exception\FileNotFound as FileNotFoundException;
use Gaufrette\Filesystem;
use Intervention\Image\Image as InterventionImage;

/**
 * An image manager for cloud computing
 *
 * The concept behind this is to store your images on a cloud storage device (such as S3) and to create variations
 * either ahead of time or in real-time, keeping them accessible via a CDN through your cloud storage.
 *
 * It should also be possible NOT to have your image store publicly accessible and instead retrieve the binary image
 * data for alternative delivery.
 */
class ImageManager
{
    const ERR_NOT_HYDRATED = "Image is not hydrated";

    /**
     * A filesystem to store all images on
     *
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * A high-speed caching pool to store meta-data on stored objects
     *
     * This cache pool is used to remember if objects exist without the need to hit the image pool. If you are using a
     * filesystem image pool then there is little use for a cache pool, however If you're using an S3 or similar, then
     * you should consider using something like Doctrine, PDO or Redis here.
     *
     * @var PoolInterface
     */
    protected $cache_pool;


    function __construct(Filesystem $filesystem, PoolInterface $cache_pool = null)
    {
        $this->filesystem = $filesystem;
        $this->cache_pool = $cache_pool;
    }


    /**
     * Push a local image/variation to the remote
     *
     * If it is not hydrated this function will throw an exception
     *
     * @param Image $image
     * @return $this
     * @throws ImageManagerException
     */
    public function push(Image $image, $overwrite = true)
    {
        if (!$image->isHydrated() && ($image instanceof ImageVariation)) {
            // A pull on a variation will check if the variation exists, if not create it
            $this->pull($image);
        }

        if (!$image->isHydrated()) {
            throw new ImageManagerException(self::ERR_NOT_HYDRATED);
        }

        try {
            $this->filesystem->write($image->getKey(), $image->getData(), $overwrite);
            $image->__friendSet('persistent', true);
        } catch (FileAlreadyExists $e) {
            throw new ObjectAlreadyExistsException("Key '".$image->getKey()."' already exists on remote");
        }

        return $this;
    }


    /**
     * Get an image/variation from the remote
     *
     * If this image is a variation that does not exist, an attempt will be made to retrieve the parent first
     * then create the variation. The variation should be optionally pushed if it's Image::isPersistent() function
     * returns false.
     *
     * @param Image $image
     * @return $this
     * @throws ImageManagerException
     */
    public function pull(Image $image)
    {
        if ($image instanceof ImageVariation) {
            // Image is a variation - try the variation first, then try the source (parent) image
            try {
                // Get variation data
                $image->setData($this->filesystem->read($image->getKey()));
                $image->__friendSet('persistent', true);

            } catch (FileNotFoundException $e) {
                // Variation does not exist, get parent data
                try {
                    $data = $this->filesystem->read($image->getKey(true));

                    // Resample
                    $parent = new Image($image->getKey(true));
                    $parent->setData($data);
                    $this->hydrateVariation($parent, $image);
                    $parent->flush();

                } catch (FileNotFoundException $e) {
                    // No image exists
                    throw new NotExistsException("Parent image does not exist");
                }
            }
        } else {
            // Image is a source image
            try {
                // Get source data
                $image->setData($this->filesystem->read($image->getKey()));
                $image->__friendSet('persistent', true);

            } catch (FileNotFoundException $e) {
                // Image not found
                throw new NotExistsException("Image does not exist");
            }

        }

        return $this;
    }

    /**
     * Delete an image from the remote
     *
     * @param Image $image
     * @return $this
     */
    public function remove(Image $image)
    {
        $this->filesystem->delete($image->getKey());
        return $this;
    }


    /**
     * Save the image to the local filesystem
     *
     * The extension of the filename is ignored, either the original format or the variation format will be used.
     * If the image is not hydrated a pull will be attempted.
     *
     * @param Image  $image
     * @param string $filename Path to save the image
     * @return $this
     */
    public function save(Image $image, $filename)
    {
        if (!$image->isHydrated()) {
            // Auto-pull
            $this->pull($image);
        }

        file_put_contents($filename, $image->getData());

        return $this;
    }

    /**
     * Hydrate and render an image variation with parent data
     *
     * You can use this to create a variation with a source image
     *
     * @param Image          $parent
     * @param ImageVariation $variation
     * @return ImageVariation
     * @throws BadImageException
     * @throws ImageManagerException
     */
    protected function hydrateVariation(Image $parent, ImageVariation &$variation)
    {
        if (!$parent->isHydrated()) {
            throw new ImageManagerException('Parent: '.self::ERR_NOT_HYDRATED);
        }

        // Image variation - re-render the image
        $ext     = $variation->getFormat();
        $quality = $variation->getQuality() ? : 90;

        if ($quality < 1) {
            $quality = 1;
        } elseif ($quality > 100) {
            $quality = 100;
        }

        try {
            $img = new InterventionImage($parent->getData());
        } catch (\Intervention\Image\Exception\InvalidImageDataStringException $e) {
            throw new BadImageException("Bad image data", 0, $e);
        }

        if ($dim = $variation->getDimensions()) {
            $img->resize($dim->getWidth(), $dim->getHeight(), $dim->getMaintainRatio(), $dim->canUpscale());
        }

        $variation->setData($img->encode($ext->key(), $quality));

        return $variation;
    }

    /**
     * Create a new image variation from a local source image
     *
     * @param Image           $source
     * @param ImageFormat     $format
     * @param int             $quality
     * @param ImageDimensions $dimensions
     * @return ImageVariation
     */
    public function createVariation(
        Image $source,
        ImageFormat $format,
        $quality = ImageVariation::DEFAULT_QUALITY,
        ImageDimensions $dimensions = null
    ) {
        $var = new ImageVariation($source->getKey(), $format, $quality, $dimensions);
        return $this->hydrateVariation($source, $var);
    }

    /**
     * Create a new image from a filename and hydrate it
     *
     * @param string $filename
     * @param string $key
     * @return Image
     */
    public function loadFromFile($filename, $key = null)
    {
        if (!is_readable($filename)) {
            throw new IoException("File not readable: ".$filename);
        }

        if (!$key) {
            $key = basename($filename);
        }

        $image = new Image($key);
        $image->setData(file_get_contents($filename));

        return $image;
    }

    /**
     * Create a new image from memory and hydrate it
     *
     * @param string $filename
     * @param string $key
     * @return Image
     */
    public function load($data, $key)
    {
        $image = new Image($key);
        $image->setData($data);

        return $image;
    }

    /**
     * Check if an image exists on the remote
     *
     * This will check the cache pool if one exists, else it will talk to the remote filesystem to check if
     * the image exists.
     *
     * @param Image $image
     * @return boolean
     */
    public function exists(Image $image)
    {
        $key = $image->getKey();

        return $this->filesystem->has($key);
    }

    /*
     * REQUIRE
     * -------
     *
     * - A facility to save a new image (from memory)
     * - A facility to delete an image, and all of it's variations
     * - A facility to check if a variation exists [and create it]
     * - A facility to manually create a variation
     * - A facility to retrieve an image/variation (to memory)
     * - A facility to retrieve a URL for an image/variation
     *
     * CONSIDER
     * --------
     *
     * - How do we tackle URL resolution?
     * - Forking Illuminate to provide stream support and manual format saving (instead of auto-detect format on extension)
     *
     */

}
 