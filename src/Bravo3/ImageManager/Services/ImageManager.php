<?php
namespace Bravo3\ImageManager\Services;

use Bravo3\Cache\PoolInterface;
use Bravo3\ImageManager\Encoders\EncoderInterface;
use Bravo3\ImageManager\Encoders\InterventionEncoder;
use Bravo3\ImageManager\Entities\Image;
use Bravo3\ImageManager\Entities\ImageDimensions;
use Bravo3\ImageManager\Entities\ImageVariation;
use Bravo3\ImageManager\Enum\ImageFormat;
use Bravo3\ImageManager\Exceptions\BadImageException;
use Bravo3\ImageManager\Exceptions\ImageManagerException;
use Bravo3\ImageManager\Exceptions\IoException;
use Bravo3\ImageManager\Exceptions\NoSupportedEncoderException;
use Bravo3\ImageManager\Exceptions\NotExistsException;
use Bravo3\ImageManager\Exceptions\ObjectAlreadyExistsException;
use Gaufrette\Exception\FileAlreadyExists;
use Gaufrette\Exception\FileNotFound as FileNotFoundException;
use Gaufrette\Filesystem;

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
    const ERR_NOT_HYDRATED      = "Image is not hydrated";
    const ERR_NOT_EXISTS        = "Image does not exist";
    const ERR_PARENT_NOT_EXISTS = "Parent image does not exist";
    const ERR_ALREADY_EXISTS    = "Object already exists on remote";

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

    /**
     * @var EncoderInterface[]
     */
    protected $encoders;

    /**
     * Create a new image manager
     *
     * If you do not include any encoders, an InterventionEncoder will be automatically added.
     *
     * @param Filesystem    $filesystem
     * @param PoolInterface $cache_pool
     * @param array         $encoders
     */
    public function __construct(Filesystem $filesystem, PoolInterface $cache_pool = null, array $encoders = [])
    {
        $this->filesystem = $filesystem;
        $this->cache_pool = $cache_pool;
        $this->encoders   = $encoders;

        if (!$this->encoders) {
            $this->addEncoder(new InterventionEncoder());
        }
    }

    /**
     * Push a local image/variation to the remote
     *
     * If it is not hydrated this function will throw an exception
     *
     * @param Image $image
     * @param bool  $overwrite
     * @return $this
     * @throws ImageManagerException
     * @throws ObjectAlreadyExistsException
     * @throws \Exception
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

        if (!$overwrite && $this->tagExists($image->getKey()) === true) {
            throw new ObjectAlreadyExistsException(self::ERR_ALREADY_EXISTS);
        }

        try {

            if ($image->getMimeType()) {
                if (method_exists($this->filesystem->getAdapter(), 'setMetadata')) {
                    // Set file MIME-type
                    $this->filesystem->getAdapter()->setMetadata(
                        $image->getKey(),
                        [
                            'ContentType' => $image->getMimeType()
                        ]
                    );
                }
            }

            $this->filesystem->write($image->getKey(), $image->getData(), $overwrite);
            $image->__friendSet('persistent', true);
            $this->tag($image->getKey());

        } catch (FileAlreadyExists $e) {
            $this->tag($image->getKey());
            throw new ObjectAlreadyExistsException(self::ERR_ALREADY_EXISTS);
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
            $this->pullVariation($image);
        } else {
            $this->pullSource($image);
        }

        return $this;
    }

    /**
     * Pull a source (or variation) image
     *
     * @param Image $image
     * @throws NotExistsException
     */
    protected function pullSource(Image $image)
    {
        // Image is a source image
        if ($this->tagExists($image->getKey()) === false) {
            throw new NotExistsException(self::ERR_NOT_EXISTS);
        }

        try {
            // Get source data
            $image->setData($this->filesystem->read($image->getKey()));
            $image->__friendSet('persistent', true);

        } catch (FileNotFoundException $e) {
            // Image not found
            $this->untag($image->getKey());
            throw new NotExistsException(self::ERR_NOT_EXISTS);
        }
    }

    /**
     * Pull a variation image, if the variation does not exist, try pulling the source and creating the variation
     *
     * @param ImageVariation $image
     * @throws NotExistsException
     */
    protected function pullVariation(ImageVariation $image)
    {
        try {
            // First, check if the variation exists on the remote
            $this->pullSource($image);

        } catch (NotExistsException $e) {
            // Variation does not exist, try pulling the parent data and creating the variation
            try {
                if ($this->tagExists($image->getKey(true)) === false) {
                    throw new NotExistsException(self::ERR_PARENT_NOT_EXISTS);
                }

                $data = $this->filesystem->read($image->getKey(true));

                // Resample
                $parent = new Image($image->getKey(true));
                $parent->setData($data);
                $this->hydrateVariation($parent, $image);
                $parent->flush();

            } catch (FileNotFoundException $e) {
                // No image exists
                throw new NotExistsException(self::ERR_PARENT_NOT_EXISTS);
            }
        }
    }


    /**
     * Mark a file as existing on the remote
     *
     * @param string $key
     */
    protected function tag($key)
    {
        if (!$this->cache_pool) {
            return;
        }

        $item = $this->cache_pool->getItem('remote.'.$key);
        $item->set(1, null);
    }

    /**
     * Mark a file as absent on the remote
     *
     * @param string $key
     */
    protected function untag($key)
    {
        if (!$this->cache_pool) {
            return;
        }

        $item = $this->cache_pool->getItem('remote.'.$key);
        $item->delete();
    }

    /**
     * Check if a file exists on the remote
     *
     * Returns null if caching isn't available (and unsure), else a boolean value
     *
     * @param string $key
     * @return bool|null
     */
    protected function tagExists($key)
    {
        if (!$this->cache_pool) {
            return null;
        }

        $item = $this->cache_pool->getItem('remote.'.$key);
        return $item->exists();
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
        $this->untag($image->getKey());
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

        $quality = $variation->getQuality() ?: 90;

        if ($quality < 1) {
            $quality = 1;
        } elseif ($quality > 100) {
            $quality = 100;
        }

        $variation->setData(null);
        $input = $parent->getData();

        foreach ($this->encoders as $encoder) {
            if ($encoder->supports($input)) {
                $encoder->setData($input);
                $variation->setData(
                    $encoder->createVariation($variation->getFormat(), $quality, $variation->getDimensions())
                );
                $encoder->setData(null);
                break;
            }
        }

        if (!$variation->getData()) {
            throw new NoSupportedEncoderException("There is no known encoder for this data type");
        }

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
     * @param string $data
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

        $tag_exists = $this->tagExists($key);
        if ($tag_exists !== null) {
            return $tag_exists;
        }

        return $this->filesystem->has($key);
    }

    /**
     * Get all registered encoders
     *
     * @return EncoderInterface[]
     */
    public function getEncoders()
    {
        return $this->encoders;
    }

    /**
     * Set encoders
     *
     * @param EncoderInterface[] $encoders
     * @return $this
     */
    public function setEncoders(array $encoders)
    {
        $this->encoders = $encoders;
        return $this;
    }

    /**
     * Add an encoder
     *
     * @param EncoderInterface $encoder
     * @param bool             $prepend Prepend to the list instead of appending
     * @return $this
     */
    public function addEncoder(EncoderInterface $encoder, $prepend = false)
    {
        if ($prepend) {
            array_unshift($this->encoders, $encoder);
        } else {
            $this->encoders[] = $encoder;
        }
        return $this;
    }
}
