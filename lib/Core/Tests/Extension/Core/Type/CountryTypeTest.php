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
    protected function setUp(): void
    {
        IntlTestHelper::requireIntl($this, '70.1');

        parent::setUp();
    }

    /** @test */
    public function countries_are_selectable(): void
    {
        $field = $this->getFactory()->createField('choice', CountryType::class);
        $field->finalizeConfig();

        FieldTransformationAssertion::assertThat($field)
            ->withInput('Germany', 'DE')
            ->successfullyTransformsTo('DE')
            ->andReverseTransformsTo('Germany', 'DE')
        ;

        $view = $field->createView(new FieldSetView());
        $choices = $view->vars['choices'];

        // Don't check objects for identity
        self::assertContainsEquals(new ChoiceView('DE', 'DE', 'Germany'), $choices);
        self::assertContainsEquals(new ChoiceView('GB', 'GB', 'United Kingdom'), $choices);
        self::assertContainsEquals(new ChoiceView('US', 'US', 'United States'), $choices);
        self::assertContainsEquals(new ChoiceView('FR', 'FR', 'France'), $choices);
        self::assertContainsEquals(new ChoiceView('MY', 'MY', 'Malaysia'), $choices);
    }

    /** @test */
    public function unknown_country_is_not_included(): void
    {
        $field = $this->getFactory()->createField('choice', CountryType::class);
        $field->finalizeConfig();

        $view = $field->createView(new FieldSetView());

        $choices = $view->vars['choices'];

        foreach ($choices as $choice) {
            if ($choice->value === 'ZZ') {
                self::fail('Should not contain choice "ZZ"');
            }
        }

        self::assertStringContainsString('AF', $choices[0]->value ?? 'ZZ');
    }
}
