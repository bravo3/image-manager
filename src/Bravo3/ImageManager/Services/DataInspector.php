<?php

namespace Bravo3\ImageManager\Services;

use Bravo3\ImageManager\Enum\ImageFormat;

/**
 * Inspects data for common formats.
 */
class DataInspector
{
    /**
     * Test to see if we can determine the image format.
     *
     * @param string $data
     *
     * @return ImageFormat|null
     */
    public function getImageFormat(&$data)
    {
        if (!$data || strlen($data) < 5) {
            return;
        }

        // JPEG: FF D8
        if (ord($data{0}) == 0xff && ord($data{1}) == 0xd8) {
            return ImageFormat::JPEG();
        }

        // PNG: 89 50 4E 47
        if ((ord($data{0}) == 0x89) && substr($data, 1, 3) == 'PNG') {
            return ImageFormat::PNG();
        }

        // GIF87a: 47 49 46 38 37 61
        // GIF89a: 47 49 46 38 39 61
        if (substr($data, 0, 6) == 'GIF87a' || substr($data, 0, 6) == 'GIF89a') {
            return ImageFormat::GIF();
        }

        return;
    }

    /**
     * Check if the data is a PDF document.
     *
     * @param string $data
     *
     * @return bool
     */
    public function isPdf(&$data)
    {
        return substr($data, 0, 5) == '%PDF-';
    }

    /**
     * Guess the MIME-type of data specified.
     *
     * @param string $data
     *
     * @return string Guessed MIME-type will be returned
     */
    public function guessMimeType(&$data)
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        return $finfo->buffer($data);
    }
}
