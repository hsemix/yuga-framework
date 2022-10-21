<?php
/**
 * @author Mahad Tech Solutions
 */

namespace Yuga\Support;

class Inflect
{
    public static $plural = [
        '/(quiz)$/i'                    => '$1zes',
        '/^(ox)$/i'                     => '$1en',
        '/([m|l])ouse$/i'               => '$1ice',
        '/(matr|vert|ind)ix|ex$/i'      => '$1ices',
        '/(x|ch|ss|sh)$/i'              => '$1es',
        '/([^aeiouy]|qu)y$/i'           => '$1ies',
        '/(hive)$/i'                    => '$1s',
        '/(?:([^f])fe|([lr])f)$/i'      => '$1$2ves',
        '/(shea|lea|loa|thie)f$/i'      => '$1ves',
        '/sis$/i'                       => 'ses',
        '/([ti])um$/i'                  => '$1a',
        '/(tomat|potat|ech|her|vet)o$/i'=> '$1oes',
        '/(bu)s$/i'                     => '$1ses',
        '/(alias)$/i'                   => '$1es',
        '/(octop)us$/i'                 => '$1i',
        '/(ax|test)is$/i'               => '$1es',
        '/(us)$/i'                      => '$1es',
        '/s$/i'                         => 's',
        '/$/'                           => 's',
    ];

    public static $singular = [
        '/(quiz)zes$/i'                                                     => '$1',
        '/(matr)ices$/i'                                                    => '$1ix',
        '/(vert|ind)ices$/i'                                                => '$1ex',
        '/^(ox)en$/i'                                                       => '$1',
        '/(alias)es$/i'                                                     => '$1',
        '/(octop|vir)i$/i'                                                  => '$1us',
        '/(cris|ax|test)es$/i'                                              => '$1is',
        '/(shoe)s$/i'                                                       => '$1',
        '/(o)es$/i'                                                         => '$1',
        '/(bus)es$/i'                                                       => '$1',
        '/([m|l])ice$/i'                                                    => '$1ouse',
        '/(x|ch|ss|sh)es$/i'                                                => '$1',
        '/(m)ovies$/i'                                                      => '$1ovie',
        '/(s)eries$/i'                                                      => '$1eries',
        '/([^aeiouy]|qu)ies$/i'                                             => '$1y',
        '/([lr])ves$/i'                                                     => '$1f',
        '/(tive)s$/i'                                                       => '$1',
        '/(hive)s$/i'                                                       => '$1',
        '/(li|wi|kni)ves$/i'                                                => '$1fe',
        '/(shea|loa|lea|thie)ves$/i'                                        => '$1f',
        '/(^analy)ses$/i'                                                   => '$1sis',
        '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i'  => '$1$2sis',
        '/([ti])a$/i'                                                       => '$1um',
        '/(n)ews$/i'                                                        => '$1ews',
        '/(h|bl)ouses$/i'                                                   => '$1ouse',
        '/(corpse)s$/i'                                                     => '$1',
        '/(us)es$/i'                                                        => '$1',
        '/s$/i'                                                             => '',
    ];

    public static $irregular = [
        'move'   => 'moves',
        'foot'   => 'feet',
        'goose'  => 'geese',
        'sex'    => 'sexes',
        'child'  => 'children',
        'man'    => 'men',
        'tooth'  => 'teeth',
        'person' => 'people',
        'valve'  => 'valves',
    ];

    public static $uncountable = [
        'audio',
        'bison',
        'chassis',
        'compensation',
        'coreopsis',
        'data',
        'deer',
        'education',
        'equipment',
        'fish',
        'gold',
        'information',
        'knowledge',
        'love',
        'rain',
        'money',
        'moose',
        'nutrition',
        'offspring',
        'plankton',
        'police',
        'rice',
        'series',
        'sheep',
        'species',
        'swine',
        'traffic',
    ];

    public static function pluralize($string)
    {
        // save some time in the case that singular and plural are the same
        if (in_array(strtolower($string), self::$uncountable)) {
            return $string;
        }

        // check for irregular singular forms
        foreach (self::$irregular as $pattern => $result) {
            $pattern = '/'.$pattern.'$/i';

            if (preg_match($pattern, $string)) {
                return preg_replace($pattern, $result, $string);
            }
        }

        // check for matches using regular expressions
        foreach (self::$plural as $pattern => $result) {
            if (preg_match($pattern, $string)) {
                return preg_replace($pattern, $result, $string);
            }
        }

        return $string;
    }

    public static function singularize($string)
    {
        // save some time in the case that singular and plural are the same
        if (in_array(strtolower($string), self::$uncountable)) {
            return $string;
        }

        // check for irregular plural forms
        foreach (self::$irregular as $result => $pattern) {
            $pattern = '/'.$pattern.'$/i';

            if (preg_match($pattern, $string)) {
                return preg_replace($pattern, $result, $string);
            }
        }

        // check for matches using regular expressions
        foreach (self::$singular as $pattern => $result) {
            if (preg_match($pattern, $string)) {
                return preg_replace($pattern, $result, $string);
            }
        }

        return $string;
    }

    public static function pluralize_if($count, $string)
    {
        if ($count == 1) {
            return "1 $string";
        } else {
            return $count.' '.self::pluralize($string);
        }
    }

    public static function toWords($number)
    {
        $hyphen = '-';
        $conjunction = ' and ';
        $separator = ', ';
        $negative = 'negative ';
        $decimal = ' point ';
        $dictionary = [
            0                   => 'zero',
            1                   => 'one',
            2                   => 'two',
            3                   => 'three',
            4                   => 'four',
            5                   => 'five',
            6                   => 'six',
            7                   => 'seven',
            8                   => 'eight',
            9                   => 'nine',
            10                  => 'ten',
            11                  => 'eleven',
            12                  => 'twelve',
            13                  => 'thirteen',
            14                  => 'fourteen',
            15                  => 'fifteen',
            16                  => 'sixteen',
            17                  => 'seventeen',
            18                  => 'eighteen',
            19                  => 'nineteen',
            20                  => 'twenty',
            30                  => 'thirty',
            40                  => 'fourty',
            50                  => 'fifty',
            60                  => 'sixty',
            70                  => 'seventy',
            80                  => 'eighty',
            90                  => 'ninety',
            100                 => 'hundred',
            1000                => 'thousand',
            1000000             => 'million',
            1000000000          => 'billion',
            1000000000000       => 'trillion',
            1000000000000000    => 'quadrillion',
            1000000000000000000 => 'quintillion',
        ];

        if (!is_numeric($number)) {
            return false;
        }

        if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
            // overflow
            trigger_error(
                'convert_number_to_words only accepts numbers between -'.PHP_INT_MAX.' and '.PHP_INT_MAX,
                E_USER_WARNING
            );

            return false;
        }

        if ($number < 0) {
            return $negative.self::toWords(abs($number));
        }

        $string = $fraction = null;

        if (strpos($number, '.') !== false) {
            list($number, $fraction) = explode('.', $number);
        }

        switch (true) {
            case $number < 21:
                $string = $dictionary[$number];
                break;
            case $number < 100:
                $tens = ((int) ($number / 10)) * 10;
                $units = $number % 10;
                $string = $dictionary[$tens];
                if ($units) {
                    $string .= $hyphen.$dictionary[$units];
                }
                break;
            case $number < 1000:
                $hundreds = $number / 100;
                $remainder = $number % 100;
                $string = $dictionary[$hundreds].' '.$dictionary[100];
                if ($remainder) {
                    $string .= $conjunction.self::toWords($remainder);
                }
                break;
            default:
                $baseUnit = pow(1000, floor(log($number, 1000)));
                $numBaseUnits = (int) ($number / $baseUnit);
                $remainder = $number % $baseUnit;
                $string = self::toWords($numBaseUnits).' '.$dictionary[$baseUnit];
                if ($remainder) {
                    $string .= $remainder < 100 ? $conjunction : $separator;
                    $string .= self::toWords($remainder);
                }
                break;
        }

        if (null !== $fraction && is_numeric($fraction)) {
            $string .= $decimal;
            $words = [];
            foreach (str_split((string) $fraction) as $number) {
                $words[] = $dictionary[$number];
            }
            $string .= implode(' ', $words);
        }

        return $string;
    }
}
