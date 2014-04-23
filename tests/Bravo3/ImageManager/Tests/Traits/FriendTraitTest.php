<?php
namespace Bravo3\ImageManager\Tests\Traits;

use Bravo3\ImageManager\Tests\Resources\FriendlyClass;
use Bravo3\ImageManager\Tests\Resources\UnfriendlyClass;

class FriendTraitTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @small
     */
    public function testFriend()
    {
        $class = new FriendlyClass();
        $class->__friendSet('x', 10);
        $this->assertEquals(10, $class->getX());
    }

    /**
     * @small
     * @expectedException \Exception
     */
    public function testEnemy()
    {
        $class = new UnfriendlyClass();
        $class->__friendSet('x', 10);
        $this->assertEquals(10, $class->getX());
    }

}
 