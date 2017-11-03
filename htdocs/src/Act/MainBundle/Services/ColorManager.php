<?php

namespace Act\MainBundle\Services;

/**
 * Class ColorManager
 *
 * Contains useful code to convert RGB colors to Hexadecimal
 * one and the opposite.
 *
 * @package Act\MainBundle\Services
 */
class ColorManager
{
    /**
     * Returns the color in hexadecimal format.
     *
     * @param string $color
     *   The color in format "R,G,B".
     *
     * @return string
     *   The result in hexadecimal format.
     */
    public function rgbToHexa($color)
    {
        $colors = explode(',', $color);
        $hex = '#';

        if (count($colors) == 3) {
            $hex .= dechex($colors[0]);
            $hex .= dechex($colors[1]);
            $hex .= dechex($colors[2]);
        }

        return $hex;
    }

    /**
     * Returns the color in RGB format.
     *
     * @param string $color
     *   The color in hexadecimal.
     *
     * @return string
     *   The result in format "R,G,B".
     */
    public function hexaToRgb($color)
    {
        $hex = substr($color, 1);
        $color = array();

        if (strlen($hex) == 3) {
            $color['r'] = hexdec(substr($hex, 0, 1));
            $color['g'] = hexdec(substr($hex, 1, 1));
            $color['b'] = hexdec(substr($hex, 2, 1));
        } elseif (strlen($hex) == 6) {
            $color['r'] = hexdec(substr($hex, 0, 2));
            $color['g'] = hexdec(substr($hex, 2, 2));
            $color['b'] = hexdec(substr($hex, 4, 2));
        }

        return $color['r'].','.$color['g'].','.$color['b'];
    }
}
