<?php
namespace Bravo3\ImageManager\Entities;

class ImageDimensions
{

    /**
     * @var int
     */
    protected $min_x = null;

    /**
     * @var int
     */
    protected $min_y = null;

    /**
     * @var int
     */
    protected $max_x = null;

    /**
     * @var int
     */
    protected $max_y = null;

    /**
     * @var int
     */
    protected $x = null;

    /**
     * @var int
     */
    protected $y = null;

    /**
     * Set the maximum width
     *
     * @param int $max_x
     * @return $this
     */
    public function setMaxX($max_x)
    {
        $this->max_x = $max_x;
        return $this;
    }

    /**
     * Get MaxX
     *
     * @return int
     */
    public function getMaxX()
    {
        return $this->max_x;
    }

    /**
     * Set the maximum height
     *
     * @param int $max_y
     * @return $this
     */
    public function setMaxY($max_y)
    {
        $this->max_y = $max_y;
        return $this;
    }

    /**
     * Get MaxY
     *
     * @return int
     */
    public function getMaxY()
    {
        return $this->max_y;
    }

    /**
     * Set the minimum width
     *
     * @param int $min_x
     * @return $this
     */
    public function setMinX($min_x)
    {
        $this->min_x = $min_x;
        return $this;
    }

    /**
     * Get MinX
     *
     * @return int
     */
    public function getMinX()
    {
        return $this->min_x;
    }

    /**
     * Set the minimum height
     *
     * @param int $min_y
     * @return $this
     */
    public function setMinY($min_y)
    {
        $this->min_y = $min_y;
        return $this;
    }

    /**
     * Get MinY
     *
     * @return int
     */
    public function getMinY()
    {
        return $this->min_y;
    }

    /**
     * Force the width
     *
     * @param int $x
     * @return $this
     */
    public function setX($x)
    {
        $this->x = $x;
        return $this;
    }

    /**
     * Get X
     *
     * @return int
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * Force the height
     *
     * @param int $y
     * @return $this
     */
    public function setY($y)
    {
        $this->y = $y;
        return $this;
    }

    /**
     * Get Y
     *
     * @return int
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * Creates a signature containing the dimension specification
     *
     * @return string
     */
    public function __toString()
    {
        $out = '';

        if ($this->min_x) {
            $out .= ',lx'.$this->min_x;
        }
        if ($this->min_y) {
            $out .= ',ly'.$this->min_y;
        }
        if ($this->max_x) {
            $out .= ',hx'.$this->max_x;
        }
        if ($this->max_y) {
            $out .= ',hy'.$this->max_y;
        }
        if ($this->x) {
            $out .= ',x'.$this->max_x;
        }
        if ($this->y) {
            $out .= ',y'.$this->max_y;
        }

        if (!$out) {
            return '--';
        }

        return substr($out, 1);
    }


} 