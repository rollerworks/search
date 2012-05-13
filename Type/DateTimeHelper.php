<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Type;

use \NumberFormatter;
use \IntlDateFormatter;

/**
 * DateTime helper class
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class DateTimeHelper
{
    /**
     * Only date input is accepted
     */
    const ONLY_DATE = 0;

    /**
     * Only date with time input is accepted
     */
    const ONLY_DATE_TIME = 1;

    /**
     * Only date with optional time input is accepted.
     */
    const ONLY_DATE_OPTIONAL_TIME = 2;

    /**
     * Only time input is accepted
     */
    const ONLY_TIME = 3;

    /**
     * @var array
     */
    static private $regexData = array();

    /**
     * @var string
     */
    static private $currentLocale = '';

    /**
     * Get the Locale date(Time) format.
     *
     * Known limitations:
     * * Seconds are not accepted
     * * Timezone is not configurable
     *
     * @param string          $inputValue
     * @param boolean|integer $validationFlag
     * @param string          $isoDateTime Output of dateTime as ISO
     * @param boolean         $hashTime
     * @param string|null     $locale
     * @return string
     *
     * @todo Accept input that is already ISO
     */
    static public function validateLocalDateTime($inputValue, $validationFlag = self::ONLY_DATE, &$isoDateTime = null, &$hashTime = false, $locale = null)
    {
        $locale = $locale ?: \Locale::getDefault();

        if (self::$currentLocale !== $locale) {
            self::loadRegexData($locale);
        }

        $hasDate = ($validationFlag < self::ONLY_TIME);

        if (self::ONLY_TIME === $validationFlag) {
            $regex = self::$regexData['time']['validate'];
        }
        elseif (self::ONLY_DATE_OPTIONAL_TIME === $validationFlag || self::ONLY_DATE_TIME === $validationFlag) {
            $regex = self::$regexData['dateTime']['validate'];
        }
        else {
            $regex = self::$regexData['date']['validate'];
        }

        $isAmPm     = self::$regexData['am-pm'];
        $inputValue = self::normaliseInput($locale, $inputValue, ($validationFlag >= self::ONLY_DATE_TIME));

        if (!preg_match("#^$regex$#u", $inputValue, $matched)) {
            // Time is optional and previous validation failed
            if (self::ONLY_DATE_OPTIONAL_TIME === $validationFlag) {
                $regex = self::$regexData['date']['validate'];

                if (!preg_match("#^$regex$#u", $inputValue, $matched)) {
                    return false;
                }
            }
            else {
                return false;
            }
        }

        if (isset($matched['hours']) && $isAmPm) {
            if (intval($matched['hours']) > 12) {
                return false;
            }
            elseif ($isAmPm && strtolower($matched['ampm']) === 'pm') {
                $matched['hours'] = intval($matched['hours']);
                $matched['hours'] = (12 == $matched['hours'] ? 0 : $matched['hours'] + 12);
            }
        }

        $isoDateTime = null;

        if ($hasDate) {
            $matched['day']   = intval($matched['day']);
            $matched['month'] = intval($matched['month']);
            $matched['year']  = intval($matched['year']);

            // Convert year to full notation
            if ($matched['year'] >= 0 && $matched['year'] <= 69) {
                $matched['year'] += 2000;
            }
            elseif ($matched['year'] >= 70 && $matched['year'] <= 99) {
                $matched['year'] += 1900;
            }

            if (!checkdate($matched['month'], $matched['day'], $matched['year'])) {
                return false;
            }

            $isoDateTime = sprintf('%d-%02d-%02d', $matched['year'], $matched['month'], $matched['day']);

            if (isset($matched['hours'])) {
                $isoDateTime .= ' ';
            }
        }

        if (isset($matched['hours'])) {
            $hashTime = true;
            $isoDateTime .= sprintf('%02d:%02d:00', intval($matched['hours']), intval($matched['minutes']));
        }

        return true;
    }

    /**
     * Returns the regex for value matching.
     *
     * @param integer     $validationFlag
     * @param string|null $locale
     * @return string
     */
    static public function getMatcherRegex($validationFlag, $locale = null)
    {
        $locale = $locale ?: \Locale::getDefault();

        if (self::$currentLocale !== $locale) {
            self::loadRegexData($locale);
        }

        $regex = '(?:';

        if (self::ONLY_TIME === $validationFlag) {
            $regex .= self::$regexData['time']['match'];
        }
        elseif (self::ONLY_DATE_TIME === $validationFlag) {
            $regex .= self::$regexData['dateTime']['match'];
        }
        elseif (self::ONLY_DATE_OPTIONAL_TIME === $validationFlag) {
            $regex .= '(?:';
            $regex .= self::$regexData['dateTime']['match'];
            $regex .= '|';
            $regex .= self::$regexData['date']['match'];
            $regex .= ')';
        }
        else {
            $regex .= self::$regexData['date']['match'];
        }

        $regex .= ')';

        return $regex;
    }

    /**
     * Loads the regex data and stores in static-object.
     *
     * @param string $locale
     * @throws \InvalidArgumentException When the locale is not available
     */
    static private function loadRegexData($locale)
    {
        if (self::$currentLocale === $locale) {
            return;
        }

        $dir = __DIR__ . '/../Resources/data/locales/';
        $file = $dir . $locale . '.php';

        if (!file_exists($file)) {
            $file = $dir . substr($locale, 0, strpos($locale, '_')) . '.php';

            if (!file_exists($file)) {
                throw new \InvalidArgumentException(sprintf('No data available for locale "%s". Please run: update-locals.php and report this issue.', $locale));
            }
        }

        self::$regexData = require $file;
    }

    /**
     * Normalise the input.
     *
     * Converts numeric-characters to integers and locale-pm/am to am/pm
     *
     * @param string  $locale
     * @param string  $input
     * @param boolean $withTime
     * @return string
     */
    static private function normaliseInput($locale, $input, $withTime)
    {
        $numberFormatter = new NumberFormatter($locale, NumberFormatter::PATTERN_DECIMAL);

        $input = preg_replace_callback('/(\p{N})/u', function($match) use ($numberFormatter) {
            /** @var NumberFormatter $numberFormatter */
            if (ctype_digit($match[1])) {
                return $match[1];
            }

            return $numberFormatter->parse($match[1], NumberFormatter::TYPE_INT32);
        }, $input);

        if ($withTime) {
            $formatter = new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::SHORT, 'UTC', IntlDateFormatter::GREGORIAN);

            if (strpos($formatter->getPattern(), 'a') !== false) {
                // 1970-01-01 11:13 and 1970-01-01 15:02
                $am = 36780;
                $pm = 50520;

                $formatter->setPattern('a');
                $input = preg_replace(array('/' . preg_quote($formatter->format($am), '/') . '/u', '/' . preg_quote($formatter->format($pm), '/') . '/u'), array('am', 'pm'), $input);
            }
        }

        return $input;
    }
}
