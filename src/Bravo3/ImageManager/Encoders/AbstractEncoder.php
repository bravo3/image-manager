<?php
namespace Bravo3\ImageManager\Encoders;

abstract class AbstractEncoder implements EncoderInterface
{
    /**
     * @var string
     */
    protected $data;

    /**
     * Set the encoders data object
     *
     * @param string $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }
}