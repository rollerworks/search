<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search;

use Rollerworks\Component\Search\Extension\Core\CoreExtension;

/**
 * Entry point of the Search system.
 *
 * Use this class to conveniently create new search factories:
 *
 * <code>
 * use Rollerworks\Component\Search\Searches;
 *
 * $searchFactory = Searches::createSearchFactory();
 *
 * $fieldSet = $searchFactory->createFieldSetBuilder('fieldset-name')
 *     ->add('firstName', 'text')
 *     ->add('lastName', 'text')
 *     ->add('age', 'integer')
 *     ->add('gender', 'choice', array(
 *         'choices' => array('m' => 'Male', 'f' => 'Female'),
 *     ))
 *     ->getFieldSet();
 * </code>
 *
 * You can also add custom extensions to the search factory:
 *
 * <code>
 * $searchFactory = Searches::createSearchFactoryBuilder();
 *     ->addExtension(new AcmeExtension())
 *     ->getSearchFactory();
 * </code>
 *
 * If you create custom field types or type extensions, it is
 * generally recommended to create your own extensions that lazily
 * loads these types and type extensions. In projects where performance
 * does not matter that much, you can also pass them directly to the
 * search factory:
 *
 * <code>
 * use Rollerworks\Component\Search\Searches;
 *
 * $searchFactory = Searches::createSearchFactoryBuilder();
 *     ->addType(new PersonType())
 *     ->addType(new PhoneNumberType())
 *     ->addTypeExtension(new DoctrineDbalExtension())
 *     ->getSearchFactory();
 * </code>
 *
 * Support for the Validator component is provided by ValidatorExtension.
 * This extension needs a Validator object to function properly:
 *
 * <code>
 * use Rollerworks\Component\Search\Searches;
 * use Rollerworks\Component\Search\Extension\Validator\ValidatorExtension;
 * use Symfony\Component\Validator\Validation;
 *
 * $validatorBuilder = Validation::createValidatorBuilder();
 * $validator = $validatorBuilder->getValidator();
 *
 * $searchFactory = Searches::createSearchFactoryBuilder();
 *     ->addExtension(new ValidatorExtension($validator))
 *     ->getSearchFactory();
 * </code>
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class Searches
{
    /**
     * Creates a search factory builder with the default configuration.
     *
     * @return SearchFactoryBuilder The search factory builder.
     */
    public static function createSearchFactoryBuilder()
    {
        $builder = new SearchFactoryBuilder();
        $builder->addExtension(new CoreExtension());

        return $builder;
    }

    /**
     * This class cannot be instantiated.
     */
    private function __construct()
    {
    }
}
