<?php
namespace Bravo3\ImageManager\Services;

use Bravo3\Cache\PoolInterface;

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

    /**
     * A storage pool for binary data
     *
     * This pool may have a slow response time and should return the entire object. Recommended pools are connectors
     * to storage facilities like Amazon S3, or filesystem drivers.
     *
     * @var PoolInterface
     */
    protected $image_pool;

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


    function __construct(PoolInterface $image_pool, PoolInterface $cache_pool = null)
    {
        $this->image_pool = $image_pool;
        $this->cache_pool = $cache_pool;
    }


    /*
     * REQUIRE
     * -------
     *
     * - A facility to save a new image [from file, stream or memory]
     * - A facility to delete an image, and all of it's variations (this is harder than it seems using a PoolInterface)
     * - A facility to check if a variation exists [and create it]
     * - A facility to manually create a variation
     * - A facility to retrieve an image/variation [to a file, stream or memory]
     * - A facility to retrieve a URL for an image/variation
     *
     * CONSIDER
     * --------
     *
     * - Is a PoolInterface sufficient for the image pool?
     * - How do we tackle URL resolution?
     * - If we use a PoolInterface, how do we bulk delete all variations
     * - Forking Illuminate to provide stream support and manual format saving (instead of auto-detect format on extension)
     *
     */

}
 