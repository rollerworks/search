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
use Rollerworks\Component\Search\Extension\Core\Type\LocaleType;
use Rollerworks\Component\Search\FieldSetView;
use Rollerworks\Component\Search\Test\FieldTransformationAssertion;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
use Symfony\Component\Intl\Util\IntlTestHelper;

/**
 * @internal
 */
final class LocaleTypeTest extends SearchIntegrationTestCase
{
    protected function setUp(): void
    {
        IntlTestHelper::requireIntl($this, false);

        parent::setUp();
    }

    /** @test */
    public function currencies_are_selectable(): void
    {
        $field = $field = $this->getFactory()->createField('choice', LocaleType::class);
        $field->finalizeConfig();

        // NB. Use the ISO value as view-format for easier input.
        FieldTransformationAssertion::assertThat($field)
            ->withInput('en_GB')
            ->successfullyTransformsTo('en_GB')
            ->andReverseTransformsTo('en_GB');

        $view = $field->createView(new FieldSetView());

        $choices = $view->vars['choices'];

        self::assertContainsEquals(new ChoiceView('en', 'en', 'English'), $choices);
        self::assertContainsEquals(new ChoiceView('en_GB', 'en_GB', 'English (United Kingdom)'), $choices);
        self::assertContainsEquals(new ChoiceView('zh_Hant_MO', 'zh_Hant_MO', 'Chinese (Traditional, Macao SAR China)'), $choices);
    }
}
