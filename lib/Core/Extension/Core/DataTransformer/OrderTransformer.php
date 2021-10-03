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

use Rollerworks\Component\Search\DataTransformer;
use Rollerworks\Component\Search\Exception\TransformationFailedException;

/**
 * @author Dalibor Karlović <dalibor@flexolabs.io>
 */
final class OrderTransformer implements DataTransformer
{
    public const CASE_LOWERCASE = 'LOWERCASE';
    public const CASE_UPPERCASE = 'UPPERCASE';

    /**
     * @var array
     */
    private $alias;

    /**
     * @var string
     */
    private $case;

    /**
     * @var string|null
     */
    private $default;

    public function __construct(array $alias, string $case = self::CASE_UPPERCASE, ?string $default = null)
    {
        $this->alias = $alias;
        $this->case = $case;
        $this->default = $default;
    }

    public function transform($value)
    {
        if ($value !== null && ! \is_string($value)) {
            throw new TransformationFailedException('Expected a string or null.');
        }

        if ($value === null) {
            return '';
        }

        return $value;
    }

    public function reverseTransform($value)
    {
        if ($value !== null && ! \is_string($value)) {
            throw new TransformationFailedException('Expected a string or null.');
        }

        if ($value === '') {
            return null;
        }

        switch ($this->case) {
            case self::CASE_LOWERCASE:
                $value = mb_strtolower($value);

                break;

            case self::CASE_UPPERCASE:
                $value = mb_strtoupper($value);

                break;
        }

        if (! isset($this->alias[$value])) {
            throw new TransformationFailedException(
                sprintf(
                    'Invalid sort direction "%1$s" specified, expected one of: "%2$s"',
                    $value,
                    implode('", "', array_keys($this->alias))
                )
            );
        }

        return $this->alias[$value];
    }
}
