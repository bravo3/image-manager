<?php
namespace Bravo3\ImageManager\Tests\Services;

use Bravo3\Cache\Ephemeral\EphemeralCachePool;
use Bravo3\ImageManager\Services\ImageManager;

class ImageManagerTest extends \PHPUnit_Framework_TestCase {

    /**
     * @small
     */
    public function testStuff()
    {
        $image_pool = new EphemeralCachePool();
        $im = new ImageManager($image_pool);

        $this->markTestIncomplete();
    }

}
 