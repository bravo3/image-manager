<?php
namespace Bravo3\ImageManager\Enum;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * @method static ImageFormat PNG()
 * @method static ImageFormat JPEG()
 * @method static ImageFormat GIF()
 */
class ImageFormat extends AbstractEnumeration
{
    const PNG = 'png';
    const JPEG = 'jpg';
    const GIF = 'gif';
} 