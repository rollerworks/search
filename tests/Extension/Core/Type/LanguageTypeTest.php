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
    protected function setUp()
    {
        IntlTestHelper::requireIntl($this, false);

        parent::setUp();
    }

    public function testCurrenciesAreSelectable()
    {
        $field = $field = $this->getFactory()->createField('choice', LanguageType::class);
        $field->finalizeConfig();

        FieldTransformationAssertion::assertThat($field)
            ->withInput('British English', 'en_GB')
            ->successfullyTransformsTo('en_GB')
            ->andReverseTransformsTo('British English', 'en_GB');

        $view = $field->createView(new FieldSetView());

        $choices = $view->vars['choices'];

        $this->assertContains(new ChoiceView('en', 'en', 'English'), $choices, '', false, false);
        $this->assertContains(new ChoiceView('en_GB', 'en_GB', 'British English'), $choices, '', false, false);
        $this->assertContains(new ChoiceView('en_US', 'en_US', 'American English'), $choices, '', false, false);
        $this->assertContains(new ChoiceView('fr', 'fr', 'French'), $choices, '', false, false);
        $this->assertContains(new ChoiceView('my', 'my', 'Burmese'), $choices, '', false, false);
    }

    public function testMultipleLanguagesIsNotIncluded()
    {
        $field = $field = $this->getFactory()->createField('choice', LanguageType::class);
        $field->finalizeConfig();

        $view = $field->createView(new FieldSetView());
        $choices = $view->vars['choices'];

        $this->assertNotContains(new ChoiceView('mul', 'mul', 'Mehrsprachig'), $choices, '', false, false);
    }
}
