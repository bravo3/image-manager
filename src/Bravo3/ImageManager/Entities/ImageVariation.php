<?php
namespace Bravo3\ImageManager\Entities;

class ImageVariation extends Image
{

    /**
     * @var Image
     */
    protected $parent;

    /**
     * Set Parent
     *
     * @param Image $parent
     * @return $this
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * Get Parent
     *
     * @return Image
     */
    public function getParent()
    {
        return $this->parent;
    }



} 