<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Symfony\Validator;

use Rollerworks\Component\Search\AbstractExtension;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ValidatorInterface as LegacyValidatorInterface;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ValidatorExtension extends AbstractExtension
{
    /**
     * @var ValidatorInterface|LegacyValidatorInterface
     */
    private $validator;

    /**
     * Constructor.
     *
     * @param ValidatorInterface|LegacyValidatorInterface $metadataFactory
     */
    public function __construct($metadataFactory)
    {
        $this->validator = $metadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadTypeExtensions()
    {
        return [
            new Type\FieldTypeValidatorExtension($this->validator),
        ];
    }
}
