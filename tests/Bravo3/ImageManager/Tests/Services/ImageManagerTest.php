<?php
namespace Bravo3\ImageManager\Tests\Services;

use Bravo3\ImageManager\Entities\Image;
use Bravo3\ImageManager\Enum\ImageFormat;
use Bravo3\ImageManager\Services\ImageManager;
use Gaufrette\Adapter\Local as LocalAdapter;
use Gaufrette\Filesystem;

class ImageManagerTest extends \PHPUnit_Framework_TestCase
{
    protected static $tmp_dir;
    protected static $allow_delete = false;
    const TEST_KEY = 'image.png';


    /**
     * @small
     * @dataProvider imageProvider
     */
    public function testLocalImages($fn)
    {
        $im = new ImageManager(new Filesystem(new LocalAdapter(static::$tmp_dir.'remote')));

        // We'll do a few memory tests here, doesn't prove a lot, but consumption should go up and down..
        gc_collect_cycles();
        $start_memory = memory_get_usage();

        $image = $im->load($fn);
        $this->assertTrue($image instanceof Image);

        $hydrated_memory = memory_get_usage();

        $this->assertGreaterThanOrEqual($start_memory, $hydrated_memory, "Hydration increased memory consumption");

        $im->save($image, self::$tmp_dir.'local/'.basename($fn).'.png', null, ImageFormat::PNG());
        $im->save($image, self::$tmp_dir.'local/'.basename($fn).'.jpg', null, ImageFormat::JPEG());
        $im->save($image, self::$tmp_dir.'local/'.basename($fn).'.gif', null, ImageFormat::GIF());

        gc_collect_cycles();
        $saved_memory = memory_get_usage();
        $image->flush();
        $this->assertLessThan($saved_memory, memory_get_usage(), "Flushing decreased memory consumption");
    }


    /**
     * @small
     * @expectedException \Bravo3\ImageManager\Exceptions\BadImageException
     */
    public function testBadImage()
    {
        $fn = __DIR__.'/../Resources/not_an_image.png';
        $im = new ImageManager(new Filesystem(new LocalAdapter(static::$tmp_dir.'remote')));
        $img = $im->load($fn);
        $im->save($img, self::$tmp_dir.'local/invalid.gif', 90);
    }

    /**
     * @small
     * @expectedException \Bravo3\ImageManager\Exceptions\IoException
     */
    public function testMissingImage()
    {
        $fn = __DIR__.'/../Resources/does_not_exist.png';
        $im = new ImageManager(new Filesystem(new LocalAdapter(static::$tmp_dir.'remote')));
        $im->load($fn);
    }

    /**
     * TODO: add a dataProvider for a real cache and an absent cache
     *
     * @medium
     */
    public function testRemote()
    {
        $fn = __DIR__.'/../Resources/image.png';
        $im = new ImageManager(new Filesystem(new LocalAdapter(static::$tmp_dir.'remote')));

        $image_a = $im->load($fn, self::TEST_KEY);
        $this->assertTrue($image_a->isHydrated());
        $this->assertFalse($image_a->isPersistent());

        $this->assertFalse($im->exists($image_a));
        $im->push($image_a);
        $this->assertTrue($im->exists($image_a));
        $this->assertTrue($image_a->isPersistent());

        $image_b = new Image(self::TEST_KEY);
        $this->assertFalse($image_b->isHydrated());
        $this->assertFalse($image_b->isPersistent());

        $im->pull($image_b);
        $this->assertTrue($image_b->isHydrated());
        $this->assertTrue($image_b->isPersistent());
        $im->save($image_b, self::$tmp_dir.'local/pushed_and_pulled.png');

        $im->remove($image_b);
        $this->assertFalse($im->exists($image_a));
    }


    /**
     * Get a list of images
     *
     * @return array
     */
    public function imageProvider()
    {
        $base = __DIR__.'/../Resources/';
        return [
            [$base.'image.jpg'],
            [$base.'image.png'],
            [$base.'transparent.png'],
            [$base.'animated.gif'],
        ];
    }


    /**
     * This isn't an actual test, it permits the teardown function to delete the test images
     * Exclude this test to keep the test images
     *
     * @small
     * @group deleteTestImages
     */
    public function testDeleteTestImages()
    {
        self::$allow_delete = true;
    }

    // --

    public static function setUpBeforeClass()
    {
        $sys_temp = sys_get_temp_dir();
        if (substr($sys_temp, -1) != DIRECTORY_SEPARATOR) {
            $sys_temp .= DIRECTORY_SEPARATOR;
        }

        self::$tmp_dir = $sys_temp.rand(10000, 99999).DIRECTORY_SEPARATOR;

        mkdir(self::$tmp_dir.'local', 0777, true);
        mkdir(self::$tmp_dir.'remote', 0777, true);
    }

    public static function tearDownAfterClass()
    {
        if (self::$allow_delete) {
            self::rrmdir(self::$tmp_dir);
        } else {
            fwrite(STDERR, "\n\nTest images saved to ".self::$tmp_dir."\n");
        }
    }

    protected static function rrmdir($dir)
    {
        foreach (glob($dir.'/*') as $file) {
            if (is_dir($file)) {
                self::rrmdir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dir);
    }


}
 