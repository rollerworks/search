<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\SearchBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SearchFormType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')) {
            $builder
                ->add('filter', 'Symfony\Component\Form\Extension\Core\Type\TextareaType')
                ->add('format', 'Symfony\Component\Form\Extension\Core\Type\HiddenType', ['data' => $options['format']])
                ->add('submit', 'Symfony\Component\Form\Extension\Core\Type\SubmitType');
        } else {
            $builder
                ->add('filter', 'textarea')
                ->add('format', 'hidden', ['data' => $options['format']])
                ->add('submit', 'submit');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->configureOptions($resolver);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults(['format' => 'filter_query'])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'rollerworks_search';
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'rollerworks_search';
    }
}
