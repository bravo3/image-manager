<?php
namespace Bravo3\ImageManager\Tests\Resources;

use Bravo3\ImageManager\Traits\FriendTrait;

class FriendlyClass
{
    use FriendTrait;

    protected $__friends = ['Bravo3\ImageManager\Tests\Traits\FriendTraitTest'];

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
