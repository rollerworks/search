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
use Rollerworks\Component\Search\Extension\Core\ChoiceList\Loader\ChoiceLoader;
use Rollerworks\Component\Search\Field\AbstractFieldType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class TimezoneType extends AbstractFieldType implements ChoiceLoader
{
    /**
     * Timezone loaded choice list.
     *
     * The choices are generated from the ICU function \DateTimeZone::listIdentifiers().
     *
     * @var ArrayChoiceList
     */
    private $choiceList;

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choice_loader' => $this,
            'choice_translation_domain' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function loadChoiceList(callable $value = null): ChoiceList
    {
        if (null !== $this->choiceList) {
            return $this->choiceList;
        }

        return $this->choiceList = new ArrayChoiceList($this->getTimezones(), $value);
    }

    /**
     * {@inheritdoc}
     */
    public function loadChoicesForValues(array $values, callable $value = null): array
    {
        // Optimize
        if (empty($values)) {
            return [];
        }

        // If no callable is set, values are the same as choices
        if (null === $value) {
            return $values;
        }

        return $this->loadChoiceList($value)->getChoicesForValues($values);
    }

    /**
     * {@inheritdoc}
     */
    public function loadValuesForChoices(array $choices, callable $value = null): array
    {
        // Optimize
        if (empty($choices)) {
            return [];
        }

        // If no callable is set, choices are the same as values
        if (null === $value) {
            return $choices;
        }

        return $this->loadChoiceList($value)->getValuesForChoices($choices);
    }

    /**
     * {@inheritdoc}
     */
    public function isValuesConstant(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'timezone';
    }

    /**
     * Returns a normalized array of timezone choices.
     *
     * @return array The timezone choices
     */
    private static function getTimezones(): array
    {
        $timezones = [];

        foreach (\DateTimeZone::listIdentifiers() as $timezone) {
            $parts = explode('/', $timezone);

            if (count($parts) > 2) {
                $region = $parts[0];
                $name = $parts[1].' - '.$parts[2];
            } elseif (count($parts) > 1) {
                $region = $parts[0];
                $name = $parts[1];
            } else {
                $region = 'Other';
                $name = $parts[0];
            }

            $timezones[$region][str_replace('_', ' ', $name)] = $timezone;
        }

        return $timezones;
    }
}
