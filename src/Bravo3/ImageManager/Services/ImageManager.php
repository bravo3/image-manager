<?php
namespace Bravo3\ImageManager\Services;

use Bravo3\Cache\PoolInterface;
use Bravo3\ImageManager\Entities\Image;
use Bravo3\ImageManager\Entities\ImageDimensions;
use Bravo3\ImageManager\Entities\ImageVariation;
use Bravo3\ImageManager\Enum\ImageFormat;
use Bravo3\ImageManager\Exceptions\BadImageException;
use Bravo3\ImageManager\Exceptions\ImageManagerException;
use Bravo3\ImageManager\Exceptions\IoException;
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
    const ERR_NO_KEY       = "Image does not have a key";
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
    public function push(Image $image)
    {
        if (!$image->isHydrated()) {
            throw new ImageManagerException(self::ERR_NOT_HYDRATED);
        }

        if (!$image->getKey()) {
            throw new ImageManagerException(self::ERR_NO_KEY);
        }

        // TODO: reuse code from ::save()
        $this->filesystem->write($image->getKey(), $image->getContent());
        $image->__friendSet('persistent', true);

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
        if (!$image->getKey()) {
            throw new ImageManagerException(self::ERR_NO_KEY);
        }

        $data = $this->filesystem->read($image->getKey());

        if (!$data) {
            throw new BadImageException("Bad image data from remote");
        }

        $image->setData($data);
        $image->__friendSet('persistent', true);

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
     * If you specify either a quality or format, the image will be re-rendered. If you leave BOTH of these null,
     * the data data will be saved to the filesystem. If the image is a variation, the image will always be re-rendered.
     *
     * @param Image       $image
     * @param string      $filename Path to save the image
     * @param int         $quality  Defaults to 90 if re-rendering and left null
     * @param ImageFormat $format   Will check the data data if left null
     * @throws ImageManagerException
     * @return $this
     */
    public function save(Image $image, $filename)
    {
        if (!$image->isHydrated()) {
            throw new ImageManagerException(self::ERR_NOT_HYDRATED);
        }

        $data = $image->getData();

        // Image variation options
        if ($image instanceof ImageVariation) {
            $ext     = $image->getFormat() ? $image->getFormat() : $image->getDataFormat();
            $quality = $image->getQuality() ? : 90;

            if ($quality < 1) {
                $quality = 1;
            } elseif ($quality > 100) {
                $quality = 100;
            }

            if ($ext === null) {
                throw new BadImageException("Unknown image format");
            }

            $img  = new InterventionImage($data);

            // TOOD: resample based on ImageDimensions

            $data = $img->encode($ext, $quality);
        }


        file_put_contents($filename, $data);

        return $this;
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
    public function load($data, $key = null)
    {
        $image = new Image($key);
        $image->setData($data);

        return $image;
    }


    /**
     * Check if a variation exists on the remote
     *
     * @param                 $key
     * @param int             $quality
     * @param null            $format
     * @param ImageDimensions $dimensions
     * @return ImageVariation
     */
    public function testVariation($key, $quality = 90, $format = null, ImageDimensions $dimensions = null)
    {
        $variation = new ImageVariation($key);

        return $variation;
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

        if (!$key) {
            return false;
        }

        return $this->filesystem->has($key);
    }


    /**
     * Detect the image format from a filename
     *
     * @param $fn
     * @return ImageFormat|null
     */
    public function formatFromFilename($fn)
    {
        $ext = pathinfo($fn, PATHINFO_EXTENSION);

        switch (strtolower($ext)) {
            default:
                return null;

            case 'gif':
                return ImageFormat::GIF();

            case 'png':
                return ImageFormat::PNG();

            case 'jpg':
            case 'jpeg':
                return ImageFormat::JPEG();
        }
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
 