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

namespace Rollerworks\Component\Search\Extension\Core\Type;

use Rollerworks\Component\Search\Extension\Core\ChoiceList\ArrayChoiceList;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\ChoiceList;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\ChoiceLoaderTrait;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\Loader\ChoiceLoader;
use Rollerworks\Component\Search\Field\AbstractFieldType;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class LanguageType extends AbstractFieldType implements ChoiceLoader
{
    use ChoiceLoaderTrait;

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choice_loader' => $this,
            'choice_translation_domain' => false,
        ]);
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }

    public function loadChoiceList(callable $value = null): ChoiceList
    {
        if (null !== $this->choiceList) {
            return $this->choiceList;
        }

        return $this->choiceList = new ArrayChoiceList(array_flip(Intl::getLanguageBundle()->getLanguageNames()), $value);
    }

    public function isValuesConstant(): bool
    {
        return true;
    }

    public function getBlockPrefix(): string
    {
        return 'language';
    }
}
