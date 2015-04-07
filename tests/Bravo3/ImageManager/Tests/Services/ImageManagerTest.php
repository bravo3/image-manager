<?php
namespace Bravo3\ImageManager\Tests\Services;

use Bravo3\Cache\Ephemeral\EphemeralCachePool;
use Bravo3\Cache\PoolInterface;
use Bravo3\ImageManager\Encoders\ImagickEncoder;
use Bravo3\ImageManager\Entities\Image;
use Bravo3\ImageManager\Entities\ImageDimensions;
use Bravo3\ImageManager\Entities\ImageVariation;
use Bravo3\ImageManager\Enum\ImageFormat;
use Bravo3\ImageManager\Services\ImageManager;
use Gaufrette\Adapter\Local as LocalAdapter;
use Gaufrette\Filesystem;

class ImageManagerTest extends \PHPUnit_Framework_TestCase
{
    protected static $tmp_dir;
    protected static $allow_delete = false;
    const TEST_KEY     = 'image';
    const TEST_KEY_VAR = 'image_var';


    /**
     * @small
     * @dataProvider imageProvider
     * @param string $fn
     */
    public function testLocalImages($fn)
    {
        $im = new ImageManager(new Filesystem(new LocalAdapter(static::$tmp_dir.'remote')));

        // We'll do a few memory tests here, doesn't prove a lot, but consumption should go up and down..
        gc_collect_cycles();
        $start_memory = memory_get_usage();

        $image = $im->loadFromFile($fn);
        $this->assertTrue($image instanceof Image);

        $hydrated_memory = memory_get_usage();

        $this->assertGreaterThanOrEqual($start_memory, $hydrated_memory, "Hydration increased memory consumption");

        $im->save($image, self::$tmp_dir.'local/'.basename($fn));

        gc_collect_cycles();
        $saved_memory = memory_get_usage();
        $image->flush();
        $this->assertLessThan($saved_memory, memory_get_usage(), "Flushing decreased memory consumption");
    }


    /**
     * @small
     * @expectedException \Bravo3\ImageManager\Exceptions\NoSupportedEncoderException
     */
    public function testBadImage()
    {
        $fn  = __DIR__.'/../Resources/not_an_image.png';
        $im  = new ImageManager(new Filesystem(new LocalAdapter(static::$tmp_dir.'remote')));
        $img = $im->loadFromFile($fn);
        $var = $im->createVariation($img, ImageFormat::JPEG(), 90);
        $im->save($var, self::$tmp_dir.'local/invalid.jpg');
    }

    /**
     * @small
     * @expectedException \Bravo3\ImageManager\Exceptions\IoException
     */
    public function testMissingImage()
    {
        $fn = __DIR__.'/../Resources/does_not_exist.png';
        $im = new ImageManager(new Filesystem(new LocalAdapter(static::$tmp_dir.'remote')));
        $im->loadFromFile($fn);
    }

    /**
     * @medium
     * @dataProvider cacheProvider
     * @param array $cache
     */
    public function testRemote($cache)
    {
        $fn = __DIR__.'/../Resources/image.png';
        $im = new ImageManager(new Filesystem(new LocalAdapter(static::$tmp_dir.'remote')), $cache);

        $image_a = $im->loadFromFile($fn, self::TEST_KEY);
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
     * @expectedException \Bravo3\ImageManager\Exceptions\NotExistsException
     * @medium
     */
    public function testKeyValidationFail()
    {
        $fn = __DIR__.'/../Resources/image.png';
        $im = new ImageManager(new Filesystem(new LocalAdapter(static::$tmp_dir.'remote')), new EphemeralCachePool());

        $image = $im->loadFromFile($fn, self::TEST_KEY);
        $this->assertTrue($image->isHydrated());
        $this->assertFalse($image->isPersistent());
        $im->push($image);
        $this->assertTrue($image->isPersistent());

        $im->setImageExists($image, false);

        $var = new ImageVariation(self::TEST_KEY, ImageFormat::GIF(), 50);
        $im->pull($var);
    }

    /**
     * @medium
     */
    public function testKeyValidationSuccess()
    {
        $fn = __DIR__.'/../Resources/image.png';
        $im = new ImageManager(
            new Filesystem(new LocalAdapter(static::$tmp_dir.'remote')),
            new EphemeralCachePool(),
            [],
            true
        );

        $image = $im->loadFromFile($fn, self::TEST_KEY);
        $this->assertTrue($image->isHydrated());
        $this->assertFalse($image->isPersistent());
        $im->push($image);
        $this->assertTrue($image->isPersistent());

        $im->setImageExists($image, false);

        $var = new ImageVariation(self::TEST_KEY, ImageFormat::GIF(), 50);
        $im->pull($var);
    }

    /**
     * Data provider returning a real cache and a null cache
     *
     * @return array
     */
    public function cacheProvider()
    {
        return [
            [null],
            [new EphemeralCachePool()]
        ];
    }

    /**
     * @medium
     */
    public function testVariationLocalCreate()
    {
        $fn = __DIR__.'/../Resources/image.png';
        $im = new ImageManager(new Filesystem(new LocalAdapter(static::$tmp_dir.'remote')));

        $source = $im->loadFromFile($fn, self::TEST_KEY);

        // Create and render the variation
        $var = $im->createVariation($source, ImageFormat::JPEG(), 50);

        $this->assertFalse($var->isPersistent());
        $this->assertTrue($var->isHydrated());

        // Test the local save
        $im->save($var, self::$tmp_dir.'local/variation.jpg');
        $this->assertFalse($var->isPersistent());

        $im->push($var);
        $this->assertTrue($var->isPersistent());

        // Test the pull
        $var_pull = new ImageVariation(self::TEST_KEY, ImageFormat::JPEG(), 50);
        $this->assertFalse($var_pull->isPersistent());
        $this->assertFalse($var_pull->isHydrated());

        $im->pull($var_pull);
        $this->assertTrue($var_pull->isPersistent());
        $this->assertTrue($var_pull->isHydrated());
        $im->save($var_pull, self::$tmp_dir.'local/variation_pulled.jpg');

        // Test an auto-pull
        $var_autopull = new ImageVariation(self::TEST_KEY, ImageFormat::JPEG(), 50);
        $this->assertFalse($var_autopull->isPersistent());
        $this->assertFalse($var_autopull->isHydrated());
        $im->save($var_autopull, self::$tmp_dir.'local/variation_autopulled.jpg');

        // Image data should be identical - no loss
        $md5_a = md5_file(self::$tmp_dir.'local/variation.jpg');
        $md5_b = md5_file(self::$tmp_dir.'local/variation_pulled.jpg');
        $md5_c = md5_file(self::$tmp_dir.'local/variation_autopulled.jpg');
        $this->assertEquals($md5_a, $md5_b);
        $this->assertEquals($md5_a, $md5_c);
    }

    /**
     * @medium
     */
    public function testEncodePdf()
    {
        $fn = __DIR__.'/../Resources/sample_pdf.pdf';
        $im = new ImageManager(new Filesystem(new LocalAdapter(static::$tmp_dir.'remote')));
        $im->addEncoder(new ImagickEncoder());

        $source = $im->loadFromFile($fn, self::TEST_KEY.'_pdf');

        // Create and render the variation
        $var = $im->createVariation($source, ImageFormat::PNG(), 90, new ImageDimensions(1200));
        $im->save($var, self::$tmp_dir.'local/sample_pdf.png');
    }

    /**
     * @medium
     */
    public function testPdfEncodeColorCorrection()
    {
        $fn = __DIR__.'/../Resources/sample_pdf2.pdf';

        // Define a pixel within the image which has colour white when alpha
        // channel ignored. Test ignores alpha channel - See below.
        $x_px = $y_px = 10;

        // Retrieve PDF background color before encoding
        $imagick_pdf = new \Imagick($fn);
        $before_bg_color = $imagick_pdf
            ->getImagePixelColor($x_px, $y_px)
            ->getColor();

        $im = new ImageManager(new Filesystem(new LocalAdapter(static::$tmp_dir.'remote')));
        $imagick_encoder = new ImagickEncoder();
        $im->addEncoder($imagick_encoder);

        $source = $im->loadFromFile($fn, self::TEST_KEY.'_pdf');

        // Create and render the variation
        $var = $im->createVariation($source, ImageFormat::JPEG(), 90, new ImageDimensions(1200));
        $fn_jpeg = self::$tmp_dir.'local/sample_pdf2.jpg';
        $im->save($var, $fn_jpeg);

        // Check jpeg bg color
        $imagick_jpeg = new \Imagick($fn_jpeg);
        $after_bg_color = $imagick_jpeg
            ->getImagePixelColor($x_px, $y_px)
            ->getColor();

        // Unset alpha channel
        unset($before_bg_color['a']);
        unset($after_bg_color['a']);

        $this->assertEquals($before_bg_color, $after_bg_color);
    }

    /**
     * @medium
     */
    public function testVariationRemoteCreate()
    {
        $fn = __DIR__.'/../Resources/image.png';
        $im = new ImageManager(new Filesystem(new LocalAdapter(static::$tmp_dir.'remote')));

        $source = $im->loadFromFile($fn, self::TEST_KEY_VAR);
        $im->push($source);

        $var = new ImageVariation(self::TEST_KEY_VAR, ImageFormat::GIF(), 50);
        $im->pull($var);

        $this->assertTrue($var->isHydrated());
        $this->assertFalse($var->isPersistent());

        $this->assertTrue($im->exists($source));
        $this->assertFalse($im->exists($var));

        $im->push($var);

        $this->assertTrue($var->isPersistent());
        $this->assertTrue($im->exists($var));
    }

    /**
     * @medium
     * @expectedException \Bravo3\ImageManager\Exceptions\ImageManagerException
     */
    public function testNotHydratedPush()
    {
        $im  = new ImageManager(new Filesystem(new LocalAdapter(static::$tmp_dir.'remote')));
        $var = new Image(self::TEST_KEY);
        $im->push($var);
    }

    /**
     * @medium
     * @expectedException \Bravo3\ImageManager\Exceptions\ImageManagerException
     */
    public function testNotHydratedSrc()
    {
        $im  = new ImageManager(new Filesystem(new LocalAdapter(static::$tmp_dir.'remote')));
        $img = new Image(self::TEST_KEY);
        $im->createVariation($img, ImageFormat::PNG());
    }

    /**
     * @medium
     */
    public function testBounds()
    {
        $fn = __DIR__.'/../Resources/image.png';
        $im = new ImageManager(new Filesystem(new LocalAdapter(static::$tmp_dir.'remote')));

        $source = $im->load(file_get_contents($fn), self::TEST_KEY);
        $im->createVariation($source, ImageFormat::PNG(), -1);
        $im->createVariation($source, ImageFormat::PNG(), 150);
    }

    /**
     * @medium
     * @dataProvider cacheProvider
     * @expectedException \Bravo3\ImageManager\Exceptions\NotExistsException
     * @param array $cache
     */
    public function testNotFoundImage($cache)
    {
        $im = new ImageManager(new Filesystem(new LocalAdapter(static::$tmp_dir.'remote')), $cache);

        $image = new Image('does-not-exist');
        $im->pull($image);
    }

    /**
     * @medium
     * @dataProvider cacheProvider
     * @expectedException \Bravo3\ImageManager\Exceptions\NotExistsException
     * @param array $cache
     */
    public function testNotFoundVariation($cache)
    {
        $im = new ImageManager(new Filesystem(new LocalAdapter(static::$tmp_dir.'remote')), $cache);

        $image = new ImageVariation('does-not-exist', ImageFormat::PNG());
        $im->pull($image);
    }

    /**
     * @medium
     */
    public function testRemoteResample()
    {
        $fn = __DIR__.'/../Resources/image.png';
        $im = new ImageManager(new Filesystem(new LocalAdapter(static::$tmp_dir.'remote')));

        $source = $im->loadFromFile($fn, self::TEST_KEY_VAR);
        $im->push($source);

        $this->assertTrue($source->isHydrated());
        $this->assertTrue($source->isPersistent());

        $var_x = new ImageVariation(
            self::TEST_KEY_VAR, ImageFormat::JPEG(), 90,
            new ImageDimensions(20)
        );
        $im->push($var_x);

        $var_xy_stretch = new ImageVariation(
            self::TEST_KEY_VAR, ImageFormat::JPEG(), 90,
            new ImageDimensions(200, null, false)
        );
        $im->push($var_xy_stretch);

        $var_y = new ImageVariation(
            self::TEST_KEY_VAR, ImageFormat::JPEG(), 90,
            new ImageDimensions(null, 200)
        );
        $im->push($var_y);

        $var_xy = new ImageVariation(
            self::TEST_KEY_VAR, ImageFormat::JPEG(), 90,
            new ImageDimensions(100, 200)
        );
        $im->push($var_xy);

        $var_xy_scale = new ImageVariation(
            self::TEST_KEY_VAR, ImageFormat::JPEG(), 90,
            new ImageDimensions(100, 200, false)
        );
        $im->push($var_xy_scale);

        $var_xy_stretch = new ImageVariation(
            self::TEST_KEY_VAR, ImageFormat::JPEG(), 90,
            new ImageDimensions(100, 200, false)
        );
        $im->push($var_xy_stretch);

        $var_x_noup = new ImageVariation(
            self::TEST_KEY_VAR, ImageFormat::JPEG(), 90,
            new ImageDimensions(1000, null, true, false)
        );
        $im->push($var_x_noup);

        $var_x_up = new ImageVariation(
            self::TEST_KEY_VAR, ImageFormat::JPEG(), 90,
            new ImageDimensions(1000)
        );
        $im->push($var_x_up);

        $var_g = new ImageVariation(
            self::TEST_KEY_VAR, ImageFormat::JPEG(), 90,
            new ImageDimensions(100, 200, true, true, true)
        );
        $im->push($var_g);
    }

    /**
     * @medium
     */
    public function testLocalResample()
    {
        $fn = __DIR__.'/../Resources/image.png';
        $im = new ImageManager(new Filesystem(new LocalAdapter(static::$tmp_dir.'remote')));

        $source  = $im->loadFromFile($fn, self::TEST_KEY_VAR);
        $resized = $im->createVariation($source, ImageFormat::JPEG(), 90, new ImageDimensions(100));

        $this->assertTrue($resized->isHydrated());
        $this->assertFalse($resized->isPersistent());

        $im->save($resized, self::$tmp_dir.'local/resized.jpg');
    }

    /**
     * @medium
     * @dataProvider cacheProvider
     * @expectedException \Bravo3\ImageManager\Exceptions\ObjectAlreadyExistsException
     * @param array $cache
     */
    public function testOverwrite($cache)
    {
        $fn = __DIR__.'/../Resources/image.png';
        $im = new ImageManager(new Filesystem(new LocalAdapter(static::$tmp_dir.'remote')), $cache);

        $source = $im->loadFromFile($fn, self::TEST_KEY);
        $im->push($source);
        $im->push($source, false);
    }

    /**
     * @medium
     */
    public function testChangeKey()
    {
        $fn = __DIR__.'/../Resources/image.png';
        $im = new ImageManager(new Filesystem(new LocalAdapter(static::$tmp_dir.'remote')));

        $source = $im->loadFromFile($fn, self::TEST_KEY);
        $im->push($source);

        $this->assertTrue($source->isHydrated());
        $this->assertTrue($source->isPersistent());

        $source->setKey('change_key');

        $this->assertTrue($source->isHydrated());
        $this->assertFalse($source->isPersistent());

        $im->push($source);
        $this->assertTrue($source->isPersistent());
    }

    // --

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
