<?php
namespace Bravo3\ImageManager\Tests\Resources;

use Bravo3\ImageManager\Traits\FriendTrait;

class UnfriendlyClass
{
    use FriendTrait;

    protected $__friends = [];

    private $x = 1;

    /**
     * Get X
     *
     * @return int
     */
    public function getX()
    {
        return $this->x;
    }

}
