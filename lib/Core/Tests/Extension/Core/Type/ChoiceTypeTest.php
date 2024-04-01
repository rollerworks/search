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

use Rollerworks\Component\Search\Extension\Core\ChoiceList\View\ChoiceGroupView;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\View\ChoiceView;
use Rollerworks\Component\Search\Extension\Core\Type\ChoiceType;
use Rollerworks\Component\Search\Field\SearchField;
use Rollerworks\Component\Search\FieldSetView;
use Rollerworks\Component\Search\Test\FieldTransformationAssertion;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

/**
 * @internal
 */
final class ChoiceTypeTest extends SearchIntegrationTestCase
{
    private $choices = [
        'Bernhard' => 'a',
        'Fabien' => 'b',
        'Kris' => 'c',
        'Jon' => 'd',
        'Roman' => 'e',
    ];

    private $scalarChoices = [
        'Yes' => true,
        'No' => false,
        'n/a' => '',
    ];

    private $objectChoices;

    protected $groupedChoices = [
        'Symfony' => [
            'Bernhard' => 'a',
            'Fabien' => 'b',
            'Kris' => 'c',
        ],
        'Doctrine' => [
            'Jon' => 'd',
            'Roman' => 'e',
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->objectChoices = [
            (object) ['id' => 1, 'name' => 'Bernhard'],
            (object) ['id' => 2, 'name' => 'Fabien'],
            (object) ['id' => 3, 'name' => 'Kris'],
            (object) ['id' => 4, 'name' => 'Jon'],
            (object) ['id' => 5, 'name' => 'Roman'],
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->objectChoices = null;
    }

    /** @test */
    public function choices_option_expects_array_or_traversable(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->getFactory()->createField('choice', ChoiceType::class, [
            'choices' => new \stdClass(),
        ]);
    }

    /** @test */
    public function choice_list_option_expects_choice_list(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->getFactory()->createField('choice', ChoiceType::class, [
            'choice_loader' => new \stdClass(),
        ]);
    }

    /** @test */
    public function choice_list_and_choices_can_be_empty(): void
    {
        $field = $this->getFactory()->createField('choice', ChoiceType::class);
        self::assertEquals([], $field->getOption('choices'));
    }

    /** @test */
    public function choice_list_with_scalar_values(): void
    {
        /** @var SearchField $field */
        $field = $this->getFactory()->createField('choice', ChoiceType::class, [
            'choices' => $this->scalarChoices,
        ]);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('Yes', '1')
            ->successfullyTransformsTo(true)
            ->andReverseTransformsTo('Yes', '1')
        ;

        FieldTransformationAssertion::assertThat($field)
            ->withInput('No', '0')
            ->successfullyTransformsTo(false)
            ->andReverseTransformsTo('No', '0')
        ;

        FieldTransformationAssertion::assertThat($field)
            ->withInput('n/a', '')
            ->successfullyTransformsTo('')
            ->andReverseTransformsTo('n/a', '')
        ;

        $field->finalizeConfig();
        $view = $field->createView(new FieldSetView());

        self::assertSame('1', $view->vars['choices'][0]->value);
        self::assertSame('0', $view->vars['choices'][1]->value);
        self::assertSame('', $view->vars['choices'][2]->value);
    }

    /** @test */
    public function choice_list_with_scalar_values_and_norm_format_label(): void
    {
        /** @var SearchField $field */
        $field = $this->getFactory()->createField('choice', ChoiceType::class, [
            'choices' => $this->scalarChoices,
            'norm_format' => 'label',
        ]);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('Yes', 'Yes')
            ->successfullyTransformsTo(true)
            ->andReverseTransformsTo('Yes', 'Yes')
        ;

        FieldTransformationAssertion::assertThat($field)
            ->withInput('No', 'No')
            ->successfullyTransformsTo(false)
            ->andReverseTransformsTo('No', 'No')
        ;

        FieldTransformationAssertion::assertThat($field)
            ->withInput('n/a', 'n/a')
            ->successfullyTransformsTo('')
            ->andReverseTransformsTo('n/a', 'n/a')
        ;

        $field->finalizeConfig();
        $view = $field->createView(new FieldSetView());

        self::assertSame('1', $view->vars['choices'][0]->value);
        self::assertSame('0', $view->vars['choices'][1]->value);
        self::assertSame('', $view->vars['choices'][2]->value);
    }

    /** @test */
    public function choice_list_with_scalar_values_and_false_as_preferred_choice(): void
    {
        /** @var SearchField $field */
        $field = $this->getFactory()->createField('choice', ChoiceType::class, [
            'choices' => $this->scalarChoices,
            'preferred_choices' => [false],
        ]);

        $field->finalizeConfig();
        $view = $field->createView(new FieldSetView());

        self::assertEquals('No', $view->vars['preferred_choices'][1]->label, 'False value should be preferred.');
    }

    /** @test */
    public function object_choices(): void
    {
        /** @var SearchField $field */
        $field = $this->getFactory()->createField('choice', ChoiceType::class, [
            'choices' => $this->objectChoices,
            'choice_label' => 'name',
            'choice_value' => 'id',
        ]);

        FieldTransformationAssertion::assertThat($field)
            ->withInput($this->objectChoices[2]->name, $this->objectChoices[2]->id)
            ->successfullyTransformsTo($this->objectChoices[2])
            ->andReverseTransformsTo($this->objectChoices[2]->name, $this->objectChoices[2]->id)
        ;
    }

    /** @test */
    public function value_view_format_is_value(): void
    {
        /** @var SearchField $field */
        $field = $this->getFactory()->createField('choice', ChoiceType::class, [
            'choices' => $this->objectChoices,
            'choice_label' => 'name',
            'choice_value' => 'id',
            'view_format' => 'value',
        ]);

        FieldTransformationAssertion::assertThat($field)
            ->withInput($this->objectChoices[2]->id)
            ->successfullyTransformsTo($this->objectChoices[2])
            ->andReverseTransformsTo($this->objectChoices[2]->id)
        ;

        $field->finalizeConfig();
        $view = $field->createView(new FieldSetView());

        // Ensure widget views still have the label.
        self::assertEquals([
            new ChoiceView($this->objectChoices[0], '1', 'Bernhard'),
            new ChoiceView($this->objectChoices[1], '2', 'Fabien'),
            new ChoiceView($this->objectChoices[2], '3', 'Kris'),
            new ChoiceView($this->objectChoices[3], '4', 'Jon'),
            new ChoiceView($this->objectChoices[4], '5', 'Roman'),
        ], $view->vars['choices']);
    }

    /** @test */
    public function array_choices(): void
    {
        /** @var SearchField $field */
        $field = $this->getFactory()->createField('choice', ChoiceType::class, [
            'choices' => $this->choices,
        ]);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('Fabien', 'b')
            ->successfullyTransformsTo('b')
            ->andReverseTransformsTo('Fabien', 'b')
        ;
    }

    /** @test */
    public function pass_choices_to_view(): void
    {
        $choices = ['A' => 'a', 'B' => 'b', 'C' => 'c', 'D' => 'd'];

        /** @var SearchField $field */
        $field = $this->getFactory()->createField('choice', ChoiceType::class, [
            'choices' => $choices,
        ]);

        $field->finalizeConfig();
        $view = $field->createView(new FieldSetView());

        self::assertEquals([
            new ChoiceView('a', 'a', 'A'),
            new ChoiceView('b', 'b', 'B'),
            new ChoiceView('c', 'c', 'C'),
            new ChoiceView('d', 'd', 'D'),
        ], $view->vars['choices']);
    }

    /** @test */
    public function pass_preferred_choices_to_view(): void
    {
        $choices = ['A' => 'a', 'B' => 'b', 'C' => 'c', 'D' => 'd'];

        /** @var SearchField $field */
        $field = $this->getFactory()->createField('choice', ChoiceType::class, [
            'choices' => $choices,
            'preferred_choices' => ['b', 'd'],
        ]);

        $field->finalizeConfig();
        $view = $field->createView(new FieldSetView());

        self::assertEquals([
            0 => new ChoiceView('a', 'a', 'A'),
            2 => new ChoiceView('c', 'c', 'C'),
        ], $view->vars['choices']);

        self::assertEquals([
            1 => new ChoiceView('b', 'b', 'B'),
            3 => new ChoiceView('d', 'd', 'D'),
        ], $view->vars['preferred_choices']);
    }

    /** @test */
    public function pass_hierarchical_choices_to_view(): void
    {
        /** @var SearchField $field */
        $field = $this->getFactory()->createField('choice', ChoiceType::class, [
            'choices' => $this->groupedChoices,
            'preferred_choices' => ['b', 'd'],
        ]);

        $field->finalizeConfig();
        $view = $field->createView(new FieldSetView());

        self::assertEquals([
            'Symfony' => new ChoiceGroupView('Symfony', [
                0 => new ChoiceView('a', 'a', 'Bernhard'),
                2 => new ChoiceView('c', 'c', 'Kris'),
            ]),
            'Doctrine' => new ChoiceGroupView('Doctrine', [
                4 => new ChoiceView('e', 'e', 'Roman'),
            ]),
        ], $view->vars['choices']);

        self::assertEquals([
            'Symfony' => new ChoiceGroupView('Symfony', [
                1 => new ChoiceView('b', 'b', 'Fabien'),
            ]),
            'Doctrine' => new ChoiceGroupView('Doctrine', [
                3 => new ChoiceView('d', 'd', 'Jon'),
            ]),
        ], $view->vars['preferred_choices']);
    }
}
