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

namespace Rollerworks\Component\Search\Tests\Extension\Core\Type;

use Rollerworks\Component\Search\Extension\Core\ChoiceList\View\ChoiceView;
use Rollerworks\Component\Search\Extension\Core\Type\CountryType;
use Rollerworks\Component\Search\FieldSetView;
use Rollerworks\Component\Search\Test\FieldTransformationAssertion;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
use Symfony\Component\Intl\Util\IntlTestHelper;

/**
 * @internal
 */
final class CountryTypeTest extends SearchIntegrationTestCase
{
    protected function setUp()
    {
        IntlTestHelper::requireIntl($this, false);

        parent::setUp();
    }

    public function testCountriesAreSelectable()
    {
        $field = $field = $this->getFactory()->createField('choice', CountryType::class);
        $field->finalizeConfig();

        FieldTransformationAssertion::assertThat($field)
            ->withInput('Germany', 'DE')
            ->successfullyTransformsTo('DE')
            ->andReverseTransformsTo('Germany', 'DE');

        $view = $field->createView(new FieldSetView());
        $choices = $view->vars['choices'];

        // Don't check objects for identity
        $this->assertContains(new ChoiceView('DE', 'DE', 'Germany'), $choices, '', false, false);
        $this->assertContains(new ChoiceView('GB', 'GB', 'United Kingdom'), $choices, '', false, false);
        $this->assertContains(new ChoiceView('US', 'US', 'United States'), $choices, '', false, false);
        $this->assertContains(new ChoiceView('FR', 'FR', 'France'), $choices, '', false, false);
        $this->assertContains(new ChoiceView('MY', 'MY', 'Malaysia'), $choices, '', false, false);
    }

    public function testUnknownCountryIsNotIncluded()
    {
        $field = $field = $this->getFactory()->createField('choice', CountryType::class);
        $field->finalizeConfig();

        $view = $field->createView(new FieldSetView());

        $choices = $view->vars['choices'];

        foreach ($choices as $choice) {
            if ('ZZ' === $choice->value) {
                $this->fail('Should not contain choice "ZZ"');
            }
        }

        self::assertContains('AF', $choices[0]->value ?? 'ZZ');
    }
}
