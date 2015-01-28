<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Extension\Validator;

use Rollerworks\Component\Search\Extension\Symfony\Validator\Validator;
use Rollerworks\Component\Search\Extension\Symfony\Validator\ValidatorExtension;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\FieldSetBuilder;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesError;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

final class ValidatorTest extends SearchIntegrationTestCase
{
    private $sfValidator;

    /**
     * @var FieldSet
     */
    private $fieldSet;

    /**
     * @var Validator
     */
    private $validator;

    protected function setUp()
    {
        parent::setUp();

        $this->fieldSet = $this->getFieldSet();
        $this->sfValidator = Validation::createValidator();
        $this->validator = new Validator($this->sfValidator);
    }

    protected function getFieldSet($build = true)
    {
        $fieldSet = new FieldSetBuilder('test', $this->getFactory());
        $fieldSet->add('id', 'integer', array('constraints' => new Assert\Range(array('min' => 5))));
        $fieldSet->add(
            'date',
            'date',
            array(
                'constraints' => array(
                    new Assert\Date(),
                    new Assert\Range(
                        array('min' => new \DateTime('2014-12-20 14:35:05', new \DateTimeZone('UTC')))
                    )
                )
            )
        );
        $fieldSet->add('type', 'text');

        return $fieldSet->getFieldSet();
    }

    protected function getExtensions()
    {
        return array(new ValidatorExtension());
    }

    /**
     * @test
     */
    public function it_validates_fields_with_constraints()
    {
        $condition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->addSingleValue(new SingleValue(10))
                ->addSingleValue(new SingleValue(3))
                ->addSingleValue(new SingleValue(4))
                ->addRange(new Range(20, 50))
                ->addRange(new Range(4, 8))
                ->addRange(new Range(1, 4))
                ->addExcludedRange(new Range(2, 3))
                ->addExcludedRange(new Range(15, 18))
                ->addComparison(new Compare(10, '>'))
                ->addComparison(new Compare(3, '>'))
            ->end()
            ->field('type')
                ->addSingleValue(new SingleValue('foo'))
            ->end()
            ->getSearchCondition()
        ;

        $this->validator->validate($condition);
        $valuesGroup = $condition->getValuesGroup();

        $this->assertTrue($valuesGroup->hasErrors());
        $this->assertSearchError($valuesGroup->getField('id'), 'singleValues[1].value', 'This value should be {{ limit }} or more.', array('{{ value }}' => '3', '{{ limit }}' => 5));
        $this->assertSearchError($valuesGroup->getField('id'), 'singleValues[2].value', 'This value should be {{ limit }} or more.', array('{{ value }}' => '4', '{{ limit }}' => 5));
        $this->assertSearchError($valuesGroup->getField('id'), 'ranges[1].lower', 'This value should be {{ limit }} or more.', array('{{ value }}' => '4', '{{ limit }}' => 5));
        $this->assertSearchError($valuesGroup->getField('id'), 'ranges[2].lower', 'This value should be {{ limit }} or more.', array('{{ value }}' => '1', '{{ limit }}' => 5));
        $this->assertSearchError($valuesGroup->getField('id'), 'ranges[2].upper', 'This value should be {{ limit }} or more.', array('{{ value }}' => '4', '{{ limit }}' => 5));
        $this->assertSearchError($valuesGroup->getField('id'), 'excludedRanges[0].lower', 'This value should be {{ limit }} or more.', array('{{ value }}' => '2', '{{ limit }}' => 5));
        $this->assertSearchError($valuesGroup->getField('id'), 'excludedRanges[0].upper', 'This value should be {{ limit }} or more.', array('{{ value }}' => '3', '{{ limit }}' => 5));
        $this->assertSearchError($valuesGroup->getField('id'), 'comparisons[1].value', 'This value should be {{ limit }} or more.', array('{{ value }}' => '3', '{{ limit }}' => 5));

        // No more errors then asserted
        $this->assertCount(8, $valuesGroup->getField('id')->getErrors());
        $this->assertCount(0, $valuesGroup->getField('type')->getErrors());
    }

    /**
     * @test
     */
    public function it_validates_object_values()
    {
        if (!interface_exists('Symfony\Component\Validator\Validator\ValidatorInterface')) {
            $this->markTestSkipped('This test requires at least Symfony 2.4');
        }

        $date = new \DateTime('2014-12-13 14:35:05', new \DateTimeZone('UTC'));
        $date2 = new \DateTime('2014-12-10 14:35:05', new \DateTimeZone('UTC'));
        $date3 = new \DateTime('2014-12-20 14:35:05', new \DateTimeZone('UTC'));
        $date4 = new \DateTime('2014-12-17 14:35:05', new \DateTimeZone('UTC'));

        $startTime = new \DateTime('2014-12-15 14:35:05', new \DateTimeZone('UTC'));
        $endTime = clone $startTime;
        $endTime->modify('+1 day');

        $startTime2 = new \DateTime('2014-12-18 14:35:05', new \DateTimeZone('UTC'));
        $endTime2 = clone $startTime2;
        $endTime2->modify('+2 days');

        $startTime3 = new \DateTime('2014-12-20 14:35:05', new \DateTimeZone('UTC'));
        $endTime3 = clone $startTime3;
        $endTime3->modify('+1 day');

        $dateLimit = $this->formatDateTime(new \DateTime('2014-12-20 14:35:05', new \DateTimeZone('UTC')));

        $condition = SearchConditionBuilder::create($this->fieldSet)
            ->field('date')
                ->addSingleValue(new SingleValue($date3, $date3->format('m/d/Y')))
                ->addSingleValue(new SingleValue($date, $date->format('m/d/Y')))
                ->addSingleValue(new SingleValue($date2, $date2->format('m/d/Y')))
                ->addRange(new Range($startTime, $endTime, true, true, $startTime->format('m/d/Y'), $endTime->format('m/d/Y')))
                ->addExcludedRange(new Range($startTime2, $endTime2, true, true, $startTime2->format('m/d/Y'), $endTime2->format('m/d/Y')))
                ->addExcludedRange(new Range($startTime3, $endTime3, true, true, $startTime3->format('m/d/Y'), $endTime3->format('m/d/Y')))
                ->addComparison(new Compare($date3, '>', $date3->format('m/d/Y')))
                ->addComparison(new Compare($date4, '>', $date4->format('m/d/Y')))
            ->end()
            ->getSearchCondition()
        ;

        $this->validator->validate($condition);
        $valuesGroup = $condition->getValuesGroup();

        $this->assertTrue($valuesGroup->hasErrors());
        $this->assertSearchError($valuesGroup->getField('date'), 'singleValues[1].value', 'This value should be {{ limit }} or more.', array('{{ value }}' => $date->format('m/d/Y'), '{{ limit }}' => $dateLimit));
        $this->assertSearchError($valuesGroup->getField('date'), 'singleValues[2].value', 'This value should be {{ limit }} or more.', array('{{ value }}' => $date2->format('m/d/Y'), '{{ limit }}' => $dateLimit));
        $this->assertSearchError($valuesGroup->getField('date'), 'ranges[0].lower', 'This value should be {{ limit }} or more.', array('{{ value }}' => $startTime->format('m/d/Y'), '{{ limit }}' => $dateLimit));
        $this->assertSearchError($valuesGroup->getField('date'), 'ranges[0].upper', 'This value should be {{ limit }} or more.', array('{{ value }}' => $endTime->format('m/d/Y'), '{{ limit }}' => $dateLimit));
        $this->assertSearchError($valuesGroup->getField('date'), 'excludedRanges[0].lower', 'This value should be {{ limit }} or more.', array('{{ value }}' => $startTime2->format('m/d/Y'), '{{ limit }}' => $dateLimit));
        $this->assertSearchError($valuesGroup->getField('date'), 'comparisons[1].value', 'This value should be {{ limit }} or more.', array('{{ value }}' => $date4->format('m/d/Y'), '{{ limit }}' => $dateLimit));

        // No more errors then asserted
        $this->assertCount(6, $valuesGroup->getField('date')->getErrors());
    }

    /**
     * @test
     */
    public function it_validates_matchers()
    {
        $fieldSet = new FieldSetBuilder('test', $this->getFactory());
        $fieldSet->add('username', 'text', array('constraints' => new Assert\NotBlank()));

        $this->fieldSet = $fieldSet->getFieldSet();

        $condition = SearchConditionBuilder::create($this->fieldSet)
            ->field('username')
                ->addPatternMatch(new PatternMatch('foo', PatternMatch::PATTERN_STARTS_WITH))
                ->addPatternMatch(new PatternMatch('bar', PatternMatch::PATTERN_ENDS_WITH))
                ->addPatternMatch(new PatternMatch('', PatternMatch::PATTERN_ENDS_WITH))
            ->end()
            ->getSearchCondition()
        ;

        $this->validator->validate($condition);
        $valuesGroup = $condition->getValuesGroup();

        $this->assertTrue($valuesGroup->hasErrors());
        $this->assertSearchError($valuesGroup->getField('username'), 'patternMatchers[2].value', 'This value should not be blank.', array('{{ value }}' => '""'));

        // No more errors then asserted
        $this->assertCount(1, $valuesGroup->getField('username')->getErrors());
    }

    private function assertSearchError(
        ValuesBag $valuesBag,
        $subPath,
        $messageTemplate,
        array $messageParameters = array(),
        $messagePluralization = null
    ) {
        $this->assertTrue($valuesBag->hasErrors());

        $expectedError = new ValuesError(
            $subPath,
            null, // use empty message to prevent object casting (use template when checking instead)
            $messageTemplate,
            $messageParameters,
            $messagePluralization
        );

        if (!$this->containsError($expectedError, $valuesBag->getErrors())) {
            $this->fail(
                sprintf(
                    "Does not contain expected error: \n%s\n\nIn collection: \n%s",
                    print_r($expectedError, true),
                    print_r($valuesBag->getErrors(), true)
                )
            );
        }
    }

    /**
     * @param ValuesError   $expectedError
     * @param ValuesError[] $errors
     *
     * @return bool
     */
    private function containsError($expectedError, array $errors)
    {
        foreach ($errors as $error) {
            if ($error->getMessageTemplate() !== $expectedError->getMessageTemplate()) {
                continue;
            }

            // Don't use strict comparison because values are casted to strings by the Symfony validator
            // And we can't use a loose comparison because then objects are casted to integers!
            // sometimes I hate type casting...
            $expectedErrorParams = $expectedError->getMessageParameters();

            foreach ($error->getMessageParameters() as $name => $param) {
                if (!array_key_exists($name, $expectedErrorParams)) {
                    continue 2;
                }

                $expectedValue = $expectedErrorParams[$name];

                if ((is_object($expectedValue) xor is_object($param)) || (is_scalar($expectedValue) xor is_scalar($param))) {
                    continue 2;
                }

                if (is_object($expectedValue)) {
                    if (get_class($expectedValue) !== get_class($param)) {
                        continue 2;
                    }

                    if ($expectedValue != $param) {
                        continue 2;
                    }
                }

                if ((string) $expectedValue !== (string) $param) {
                    continue 2;
                }
            }

            if ($error->getSubPath() !== $expectedError->getSubPath()) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * @param \DateTime|\DateTimeInterface $value
     *
     * @return string
     */
    private function formatDateTime($value)
    {
        if (class_exists('IntlDateFormatter')) {
            $locale = \Locale::getDefault();
            $formatter = new \IntlDateFormatter($locale, \IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT);

            // neither the native nor the stub IntlDateFormatter support
            // DateTimeImmutable as of yet
            if (!$value instanceof \DateTime) {
                $value = new \DateTime(
                    $value->format('Y-m-d H:i:s.u e'),
                    $value->getTimezone()
                );
            }

            return $formatter->format($value);
        }

        return $value->format('Y-m-d H:i:s');
    }
}
