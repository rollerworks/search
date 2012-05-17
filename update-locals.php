<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/../../../../app/bootstrap.php.cache';

/**
 * This file creates/updates the locals for the Date/Time filter types.
 * I properly should make a Symfony Commando of this, but that's something for later.
 */

/**
 * Tokenize formatter pattern
 *
 * @param string $pattern the pattern that the date string is following
 * @return array
 */
function tokenize($pattern)
{
    if (! ($n = strlen($pattern))) {
        return array();
    }

    $tokens = array();

    for ($c0 = $pattern[0], $start = 0, $i = 1; $i < $n; ++$i) {
        if (($c = $pattern[$i]) !== $c0) {
            $tokens[] = substr($pattern, $start, $i - $start);
            $c0       = $c;
            $start    = $i;
        }
    }

    $tokens[] = substr($pattern, $start, $n - $start);

    return $tokens;
}

/**
 * Returns the regex for validation
 *
 * @param IntlDateFormatter $formatter
 * @param boolean           $isAmPm
 * @return string
 *
 * @todo Accept optional seconds
 */
function getValidationRegex(\IntlDateFormatter $formatter, &$isAmPm = false)
{
    $tokens      = tokenize($formatter->getPattern());
    $tokensCount = count($tokens);
    $regex       = '';
    $space       = false;

    foreach ($tokens as $index => $token) {
        if ($space && !ctype_space($token) ) {
            $regex .= '+';
            $space = false;
        }

        switch ($token) {
            case 'yyyy':
            case 'yy':
                $regex .= '(?P<year>\d{2,4})';
                break;

            case 'MM':
            case 'M':
                $regex .= '(?P<month>(?:1[0-2]|[0]?[1-9]))';
                break;

            case 'dd':
            case 'd':
                $regex .= '(?P<day>(?:3[01]?|2[0-9]|[01]?[0-9]))';
                break;

            case 'h':
            case 'H':
            case 'hh':
            case 'HH':
                $regex .= '(?P<hours>(?:[01]?\d|2[0-3]))';
                break;

            case 'm':
            case 'mm':
                $regex .= '(?P<minutes>[0-5]?\d)';
                break;

            case 's':
            case 'ss':
                $regex .= '(?P<seconds>[0-5]?\d)';
                break;

            case 'a':
                $isAmPm = true;
                $regex .= '(?P<ampm>(?:[aApP][mM]))';
                break;

            case '/':
            case '-':
            case '.':
                $regex .= '[/.-]';
                break;

            default:
                if (ctype_space($token)) {
                    $regex .= '\\h';
                    $space = true;

                    if ($index === $tokensCount) {
                       $regex .= '+';
                    }
                }
                else {
                    $regex .= preg_quote($token, '#');
                }
                break;
        }
    }

    return $regex;
}

/**
 * Returns the regex for the value matcher.
 *
 * @param IntlDateFormatter $formatter
 * @return string
 *
 * @todo Accept optional seconds
 */
function getMatcherRegex(\IntlDateFormatter $formatter)
{
    $tokens      = tokenize($formatter->getPattern());
    $tokensCount = count($tokens);
    $regex       = '';
    $space       = false;

    $am = 36780;
    $pm = 50520;

    foreach ($tokens as $index => $token) {
        if ($space && !ctype_space($token) ) {
            $regex .= '+';
            $space = false;
        }

        switch ($token) {
            case 'yy':
            case 'yyyy':
                $regex .= '\p{N}{2,4}';
                break;

            case 'MM':
            case 'dd':
            case 'HH':
            case 'hh':
            case 'mm':
            case 'ss':
                $regex .= '\p{N}{2}';
                break;

            case 'M':
            case 'd':
            case 'h':
            case 'H':
            case 'm':
            case 's':
                $regex .= '\p{N}';
                break;

            case 'a':
                $pattern = $formatter->getPattern();
                $formatter->setPattern('a');
                $regex .= '(?:' . preg_quote($formatter->format($am), '/') . '|' . preg_quote($formatter->format($pm), '/') . ')';
                $formatter->setPattern($pattern);
                break;

            case '/':
            case '-':
            case '.':
                $regex .= '[/.-]';
                break;

            default:
                if (ctype_space($token)) {
                    $regex .= '\\h';
                    $space = true;

                    if ($index === $tokensCount) {
                       $regex .= '+';
                    }
                }
                else {
                    $regex .= preg_quote($token, '#');
                }
                break;
        }
    }

    return $regex;
}

foreach (Symfony\Component\Locale\Stub\StubLocale::getLocales() as $locale) {
    $formatterWithoutTime = new \IntlDateFormatter($locale, \IntlDateFormatter::SHORT, \IntlDateFormatter::NONE, 'UTC', \IntlDateFormatter::GREGORIAN);
    $formatterWithTime    = new \IntlDateFormatter($locale, \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT, 'UTC', \IntlDateFormatter::GREGORIAN);
    $formatterTimeOnly    = new \IntlDateFormatter($locale, \IntlDateFormatter::NONE, \IntlDateFormatter::SHORT, 'UTC', \IntlDateFormatter::GREGORIAN);

    $isAmPm = false;

    $regexArray = array(
        'date' => array(
            'validate' => getValidationRegex($formatterWithoutTime),
            'match'    => getMatcherRegex($formatterWithoutTime)
        ),

        'dateTime' => array(
            'validate' => getValidationRegex($formatterWithTime, $isAmPm),
            'match'    => getMatcherRegex($formatterWithTime)
        ),

        'time' => array(
            'validate' => getValidationRegex($formatterTimeOnly),
            'match'    => getMatcherRegex($formatterTimeOnly)
        ),

        'am-pm' => $isAmPm);

    $file = __DIR__ . '/Resources/data/locales/' . $locale . '.php';

    file_put_contents($file, '<' . '?php return ' . var_export($regexArray, true) . ";\n");
}
