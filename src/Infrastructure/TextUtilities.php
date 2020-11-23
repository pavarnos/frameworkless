<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   15 Jun 2020
 */

declare(strict_types=1);

namespace Frameworkless\Infrastructure;

use Frameworkless\Environment;

class TextUtilities
{
    /** @var string[] for pluralise() singular => plural */
    private static array $plural = [
        'address'  => 'addresses',
        'Address'  => 'Addresses',
        'person'   => 'people',
        'Person'   => 'People',
        'Status'   => 'Status',
        'Story'    => 'Stories',
        'Country'  => 'Countries',
        'Activity' => 'Activities',
    ];

    /**
     * print a dollar value in the current locale
     * @param string|int|float $value
     * @param int              $decimalPlaces
     * @return string formatted value
     */
    public static function currency($value, int $decimalPlaces = 2): string
    {
        if (empty($value)) {
            return '';
        }
        return number_format((float)$value, $decimalPlaces);
    }

    /**
     * display a url
     * @param string $url
     * @return string
     */
    public static function webSite(string $url): string
    {
        if (empty($url)) {
            return '';
        }
        if (mb_strpos($url, '://') === false && $url[0] != '/') {
            $url = 'https://' . $url;
        }
        return $url;
    }

    /**
     * turn a singular item into a plural items, person into people etc.
     * Most pluralisations are regular and you can simply add an 's' on the end. Irregular ones have special handling
     * @param int    $count
     * @param string $singularText
     * @return string
     */
    public static function pluralise(int $count, string $singularText): string
    {
        if ($count == 1) {
            return $singularText;
        }
        if (isset(self::$plural[$singularText])) {
            return self::$plural[$singularText];
        }
        if (mb_substr($singularText, -1) === 's') {
            return $singularText;
        }
        return $singularText . 's';
    }

    public static function singularise(string $pluralText): string
    {
        $singular = array_search($pluralText, self::$plural, true);
        if ($singular !== false) {
            // if we know the word already
            return (string)$singular;
        }
        if (in_array($pluralText, array_keys(self::$plural), true)) {
            // it is already singular
            return $pluralText;
        }
        if (mb_substr($pluralText, -1) === 's') {
            // chop off the 's' on the end if there is one
            return mb_substr($pluralText, 0, -1);
        }
        // do not know enough to fix it, so return the original text as a sensible default
        return $pluralText;
    }

    /**
     * convert file name to PSR4 class name
     * @param string $fileName
     * @param string $basePath base of PSR-4 include path
     * @return string
     */
    public static function fileNameToClassName(string $fileName, string $basePath = Environment::SRC_PATH): string
    {
        assert(!empty($fileName));
        $className = str_replace($basePath, '', $fileName);
        $className = str_replace('.php', '', $className);
        $className = str_replace(DIRECTORY_SEPARATOR, Environment::NAMESPACE_SEPARATOR, $className);
        return 'Advantage' . Environment::NAMESPACE_SEPARATOR . $className;
    }

    /**
     * converts a case sensitive php class name to a human friendly title
     * @param string $className Person\DemographicAge
     * @return string Person Name Age
     */
    public static function classNameToTitle(string $className): string
    {
        if (strpos($className, Environment::NAMESPACE_SEPARATOR) === false) {
            return ucwords(str_replace(['_', '-'], ' ', \Safe\preg_replace('|([a-z])([A-Z])|', '\1 \2', $className)));
        }
        $className = str_replace(['Advantage\Repository', 'Advantage\Form'], '', $className);
        return trim(
            \Safe\preg_replace(
                '|([a-z])([A-Z])|',
                '\1 \2',
                str_replace(
                    ['Repository', 'Query', 'Form', 'Edit'],
                    '',
                    str_replace(Environment::NAMESPACE_SEPARATOR, ' ', $className)
                )
            )
        );
    }

    /**
     * @param array $values one line of a csv file
     * @return string quoted values
     */
    public static function quoteCSV(array $values): string
    {
        if (empty($values)) {
            return '';
        }
        foreach ($values as &$value) {
            $value = str_replace('"', '""', $value);
            $value = '"' . $value . '"';
        }
        return join(',', $values);
    }

    /**
     * examine two database rows and return info about which have changed
     * @param array $old
     * @param array $new
     * @param array $keys
     * @return array
     */
    public static function calculateChangedValues(array $old, $new, $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $oldValue = empty($old[$key]) ? '' : $old[$key];
            $newValue = empty($new[$key]) ? '' : $new[$key];
            if ($oldValue != $newValue) {
                $result[$key] = ['old' => $oldValue, 'new' => $newValue];
            }
        }
        return $result;
    }

    /**
     * examine two database rows and return info about which have changed
     * @param array  $old
     * @param array  $new
     * @param array  $keys
     * @param string $separator
     * @param int    $maxLength of any changed string
     * @return string describing the changes
     */
    public static function getChangedValueMessage(
        $old,
        $new,
        $keys,
        string $separator = ', ',
        int $maxLength = 100
    ): string {
        $result = [];
        foreach (self::calculateChangedValues($old, $new, $keys) as $key => $change) {
            $text = self::classNameToTitle($key);
            if (!empty($change['old'])) {
                $text .= ' from ' . \Safe\substr((string)$change['old'], 0, $maxLength);
            }
            if (empty($change['new'])) {
                $text .= ' to blank';
            } else {
                $text .= ' to ' . \Safe\substr((string)$change['new'], 0, $maxLength);
            }
            $result[] = $text;
        }
        if (empty($result)) {
            return '';
        }
        return 'Changed ' . join($separator, $result);
    }

    /**
     * make a string shorter for display in a big table
     * @param string $text
     * @param int    $maxLength
     * @return string
     */
    public static function shortenString(string $text, int $maxLength = 100): string
    {
        $length = strlen($text);
        if ($length <= $maxLength) {
            return $text;
        }
        return mb_substr($text, 0, $maxLength - 3) . '...';
    }

    /**
     * take an array of string values for display and select only the first $limit with a message 'and N more' at the
     * end. Used to display a truncated array of values as a string
     * @param array  $values
     * @param int    $limit how many to show
     * @param string $glue
     * @return string
     */
    public static function shortenArray(array $values, int $limit = 5, string $glue = ', '): string
    {
        $valuesToShow = array_slice($values, 0, $limit);
        $delta        = count($values) - count($valuesToShow);
        if ($delta <= 2) {
            // if there is only a few more, just show them all
            $valuesToShow = $values;
        } else {
            $valuesToShow[] = 'and ' . $delta . ' more';
        }
        return join($glue, $valuesToShow);
    }

    /**
     * convert a file size in bytes to an SI unit like 1.2MB
     * @param int|float $bytes number of bytes
     * @return string formatted size including unit
     */
    public static function bytesToSI($bytes): string
    {
        if (empty($bytes)) {
            return '0';
        }
        $prefix = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $base   = 1024;
        $class  = min((int)log($bytes, $base), count($prefix) - 1);
        return \Safe\sprintf('%1.1f', $bytes / pow($base, $class)) . $prefix[$class];
    }

    /**
     * Converts numbers like 10M into bytes. From phpMyAdmin
     * @param string $size eg 10Mb
     * @return int
     */
    public static function siToBytes(string $size = ''): int
    {
        if (empty($size)) {
            return 0;
        }

        $scan = [
            'gb' => 1073741824, //1024 * 1024 * 1024;
            'g'  => 1073741824, //1024 * 1024 * 1024;
            'mb' => 1048576,
            'm'  => 1048576,
            'kb' => 1024,
            'k'  => 1024,
            'b'  => 1,
        ];

        $size       = strtolower($size);
        $sizeLength = strlen($size);
        foreach ($scan as $unit => $factor) {
            $unitLength = strlen($unit);
            if ($sizeLength > $unitLength && \Safe\substr($size, $sizeLength - $unitLength) == $unit) {
                return intval(\Safe\substr($size, 0, $sizeLength - $unitLength)) * $factor;
            }
        }

        return intval($size); // assume bytes
    }
}