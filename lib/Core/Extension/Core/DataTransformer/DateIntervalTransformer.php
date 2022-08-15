<?php

declare(strict_types=1);

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Core\DataTransformer;

use Carbon\CarbonInterval;
use Carbon\Translator;
use Rollerworks\Component\Search\DataTransformer;
use Rollerworks\Component\Search\Exception\TransformationFailedException;
use function Symfony\Component\String\u;

final class DateIntervalTransformer implements DataTransformer
{
    /** @var string */
    private $fromLocale;

    /** @var string */
    private $toLocale;

    public function __construct(string $fromLocale, string $toLocale = null)
    {
        $this->fromLocale = $fromLocale;
        $this->toLocale = $toLocale ?? $fromLocale;
    }

    /**
     * @param CarbonInterval|null $value
     */
    public function transform($value): string
    {
        if ($value === null) {
            return '';
        }

        if (! $value instanceof CarbonInterval) {
            throw new TransformationFailedException('Expected a CarbonInterval instance or null.');
        }

        $value = clone $value;
        $value->locale($this->toLocale);

        if ($value->invert === 1) {
            return u($value->forHumans())->prepend('-')->toString();
        }

        return $value->forHumans();
    }

    /**
     * @param string $value
     */
    public function reverseTransform($value): ?CarbonInterval
    {
        if (! \is_scalar($value)) {
            throw new TransformationFailedException('Expected a scalar.');
        }

        if ($value === '') {
            return null;
        }

        try {
            $value = $this->translateNumberWords($value);
            $uValue = u($value)->trim();

            if ($uValue->startsWith('-')) {
                return CarbonInterval::parseFromLocale($uValue->trimStart('-')->toString(), $this->fromLocale)->invert();
            }

            return CarbonInterval::parseFromLocale($uValue->toString(), $this->fromLocale);
        } catch (\Exception $e) {
            throw new TransformationFailedException('Unable to parse value to DateInterval', 0, $e);
        }
    }

    private function translateNumberWords(string $timeString): string
    {
        $timeString = strtr($timeString, ['’' => "'"]);

        $translator = Translator::get($this->fromLocale);
        $translations = $translator->getMessages();

        if (! isset($translations[$this->fromLocale])) {
            return $timeString;
        }

        $messages = $translations[$this->fromLocale];

        foreach (['year', 'month', 'week', 'day', 'hour', 'minute', 'second'] as $item) {
            foreach (explode('|', $messages[$item]) as $idx => $messagePart) {
                if (preg_match('/[:%](count|time)/', $messagePart)) {
                    continue;
                }

                if ($messagePart[0] === '{') {
                    $idx = (int) mb_substr($messagePart, 1, mb_strpos($messagePart, '}'));
                }

                $messagePart = self::cleanWordFromTranslationString($messagePart);
                $timeString = str_replace($messagePart, $idx . ' ' . $item, $timeString);
            }
        }

        return $timeString;
    }

    /**
     * Return the word cleaned from its translation codes.
     */
    private static function cleanWordFromTranslationString(string $word): string
    {
        $word = str_replace([':count', '%count', ':time'], '', $word);
        $word = strtr($word, ['’' => "'"]);
        $word = preg_replace('/({\d+(,(\d+|Inf))?}|[\[\]]\d+(,(\d+|Inf))?[\[\]])/', '', $word);

        return trim($word);
    }
}
