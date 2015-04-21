<?php

namespace Bravo3\ImageManager\Enum;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * @method static ImageOrientation PORTRAIT()
 * @method static ImageOrientation LANDSCAPE()
 */
final class ImageOrientation extends AbstractEnumeration
{
    const PORTRAIT  = 'portrait';
    const LANDSCAPE = 'landscape';
}
