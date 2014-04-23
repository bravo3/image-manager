<?php
namespace Bravo3\ImageManager\Tests\Entities;

use Bravo3\ImageManager\Entities\Image;
use Bravo3\ImageManager\Enum\ImageFormat;

class ImageTest extends \PHPUnit_Framework_TestCase {

    /**
     * @small
     */
    public function testDataFormat()
    {
        $fn = __DIR__.'/../Resources/';
        $image = new Image('png');

        $image->setData(file_get_contents($fn.'image.png'));
        $this->assertEquals(ImageFormat::PNG, $image->getDataFormat()->value());

        $image->setData(file_get_contents($fn.'image.jpg'));
        $this->assertEquals(ImageFormat::JPEG, $image->getDataFormat()->value());

        $image->setData(file_get_contents($fn.'animated.gif'));
        $this->assertEquals(ImageFormat::GIF, $image->getDataFormat()->value());

        $image->setData(file_get_contents($fn.'not_an_image.png'));
        $this->assertNull($image->getDataFormat());

        $image->setData(null);
        $this->assertNull($image->getDataFormat());
    }

    /**
     * @small
     * @expectedException \Bravo3\ImageManager\Exceptions\ImageManagerException
     */
    public function testBadKey()
    {
        new Image('');
    }


}
 