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

namespace Rollerworks\Component\Search\Processor;

use Rollerworks\Component\Search\SearchFactory;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface as PropertyAccessor;

/**
 * AbstractCacheSearchProcessor provides the basic logic for all processors.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractSearchProcessor implements SearchProcessor
{
    protected $propertyAccessor;
    protected $searchFactory;

    /**
     * Constructor.
     *
     * @param SearchFactory    $searchFactory
     * @param PropertyAccessor $propertyAccessor
     */
    public function __construct(SearchFactory $searchFactory, PropertyAccessor $propertyAccessor = null)
    {
        $this->searchFactory = $searchFactory;
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
    }

    /**
     * Gets a Request object value from the request.
     *
     * @param array             $parameters
     * @param ProcessorConfig   $config
     * @param string            $name
     * @param string|array|null $default
     * @param string            $type       Eg. string or array
     *
     * @return array|null|string
     */
    final protected function getRequestParam(array $parameters, ProcessorConfig $config, string $name, $default = null, string $type = null)
    {
        if ($prefix = $config->getRequestPrefix()) {
            $name = "[{$prefix}][{$name}]";
        }

        if (false === strpos($name, '[')) {
            return $parameters[$name] ?? $default;
        }

        try {
            $value = $this->propertyAccessor->getValue($parameters, $name) ?? $default;
            if (null !== $type && false === ('is_'.$type)($value)) {
                return $default;
            }

            return $value;
        } catch (AccessException | UnexpectedTypeException $e) {
            return $default;
        }
    }
}
