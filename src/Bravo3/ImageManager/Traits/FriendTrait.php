<?php
namespace Bravo3\ImageManager\Traits;

trait FriendTrait
{
    /**
     * Property setter for friend classes
     *
     * @access private
     * @param string $key
     * @param mixed  $value
     * @return mixed
     * @throws \Exception
     */
    public function __friendSet($key, $value)
    {
        $friends = isset($this->__friends) ? $this->__friends : [];

        $trace = debug_backtrace();
        if (isset($trace[1]['class']) && in_array($trace[1]['class'], $friends)) {
            return $this->$key = $value;
        } else {
            throw new \Exception("Property is private");
        }
    }
}
