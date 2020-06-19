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
use Rollerworks\Component\Search\Extension\Core\Type\LanguageType;
use Rollerworks\Component\Search\FieldSetView;
use Rollerworks\Component\Search\Test\FieldTransformationAssertion;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
use Symfony\Component\Intl\Util\IntlTestHelper;

/**
 * @internal
 */
final class LanguageTypeTest extends SearchIntegrationTestCase
{
    protected function setUp(): void
    {
        IntlTestHelper::requireIntl($this, false);

        parent::setUp();
    }

    public function testChoicesAreSelectable()
    {
        $field = $field = $this->getFactory()->createField('choice', LanguageType::class);
        $field->finalizeConfig();

        FieldTransformationAssertion::assertThat($field)
            ->withInput('English', 'en')
            ->successfullyTransformsTo('en')
            ->andReverseTransformsTo('English', 'en');

        $view = $field->createView(new FieldSetView());

        $choices = $view->vars['choices'];

        $this->assertContainsEquals(new ChoiceView('en', 'en', 'English'), $choices);
        $this->assertContainsEquals(new ChoiceView('fr', 'fr', 'French'), $choices);
        $this->assertContainsEquals(new ChoiceView('my', 'my', 'Burmese'), $choices);
    }

    public function testMultipleLanguagesIsNotIncluded()
    {
        $field = $field = $this->getFactory()->createField('choice', LanguageType::class);
        $field->finalizeConfig();

        $view = $field->createView(new FieldSetView());
        $choices = $view->vars['choices'];

        $this->assertNotContainsEquals(new ChoiceView('mul', 'mul', 'Mehrsprachig'), $choices);
    }
}
