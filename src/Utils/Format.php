<?php

namespace Po1nt\EET\Utils;

/**
 * Format Helper class
 *
 * @package Po1nt\EET\Utils
 */
abstract class Format {

    /**
     * Format float as valid currency format
     *
     * @param $value
     * @return string
     */
    public static function price($value) {
        return number_format($value, 2, '.', '');
    }

    /**
     * Helper function for calculating BKB
     *
     * @param $code
     * @return string
     */
    public static function BKB($code) {
        $r = '';
        for ($i = 0; $i < 40; $i++) {
            if ($i % 8 == 0 && $i != 0) {
                $r .= '-';
            }
            $r .= $code[$i];
        }
        return $r;
    }

}
