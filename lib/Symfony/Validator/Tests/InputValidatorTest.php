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

namespace Rollerworks\Component\Search\Extension\Symfony\Validator\Tests;

use Rollerworks\Component\Search\ConditionErrorMessage;
use Rollerworks\Component\Search\ErrorList;
use Rollerworks\Component\Search\Extension\Core\Type\DateType;
use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
use Rollerworks\Component\Search\Extension\Core\Type\TextType;
use Rollerworks\Component\Search\Extension\Symfony\Validator\InputValidator;
use Rollerworks\Component\Search\Extension\Symfony\Validator\ValidatorExtension;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
use Rollerworks\Component\Search\Value\PatternMatch;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validation;

/**
 * @internal
 */
final class InputValidatorTest extends SearchIntegrationTestCase
{
    private $sfValidator;

    /**
     * @var InputValidator
     */
    private $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $validatorBuilder = Validation::createValidatorBuilder();
        $validatorBuilder->disableAnnotationMapping();

        $this->sfValidator = $validatorBuilder->getValidator();
        $this->validator = new InputValidator($this->sfValidator);
    }

    protected function getFieldSet(bool $build = true)
    {
        $fieldSet = $this->getFactory()->createFieldSetBuilder();
        $fieldSet->add('id', IntegerType::class, ['constraints' => new Assert\Range(['min' => 5])]);
        $fieldSet->add('date', DateType::class, [
            'constraints' => [
                new Assert\Range(
                    ['min' => new \DateTimeImmutable('2014-12-20 14:35:05 UTC')]
                ),
            ],
        ]);
        $fieldSet->add('type', TextType::class);

        return $build ? $fieldSet->getFieldSet() : $fieldSet;
    }

    protected function getExtensions(): array
    {
        return [new ValidatorExtension()];
    }

    /** @test */
    public function it_validates_fields_with_constraints(): void
    {
        $fieldSet = $this->getFieldSet();

        $errorList = new ErrorList();
        $this->validator->initializeContext($fieldSet->get('id'), $errorList);

        $this->validator->validate(10, 'simple', 10, 'simpleValues[0]');
        $this->validator->validate(3, 'simple', 3, 'simpleValues[1]');
        $this->validator->validate(4, 'simple', 4, 'simpleValues[2]');

        $errorList2 = new ErrorList();
        $this->validator->initializeContext($fieldSet->get('date'), $errorList2);

        $this->validator->validate($d1 = new \DateTimeImmutable('2014-12-13 14:35:05 UTC'), 'simple', '2014-12-13 14:35:05', 'simpleValues[0]');
        $this->validator->validate($d2 = new \DateTimeImmutable('2014-12-21 14:35:05 UTC'), 'simple', '2014-12-17 14:35:05', 'simpleValues[1]');
        $this->validator->validate($d3 = new \DateTimeImmutable('2014-12-10 14:35:05 UTC'), 'simple', '2014-12-10 14:35:05', 'simpleValues[2]');

        $errorList3 = new ErrorList();
        $this->validator->initializeContext($fieldSet->get('type'), $errorList3);

        $this->validator->validate('something', 'simple', 'something', 'simpleValues[0]');

        $this->assertContainsErrors(
            [
                new ConditionErrorMessage('simpleValues[1]', 'This value should be 5 or more.', 'This value should be {{ limit }} or more.', ['{{ value }}' => '3', '{{ limit }}' => '5']),
                new ConditionErrorMessage('simpleValues[2]', 'This value should be 5 or more.', 'This value should be {{ limit }} or more.', ['{{ value }}' => '4', '{{ limit }}' => '5']),
            ],
            $errorList
        );

        $minDate = self::formatDateTime(new \DateTimeImmutable('2014-12-20 14:35:05 UTC'));

        $this->assertContainsErrors(
            [
                new ConditionErrorMessage('simpleValues[0]', 'This value should be ' . $minDate . ' or more.', 'This value should be {{ limit }} or more.', ['{{ value }}' => self::formatDateTime($d1), '{{ limit }}' => $minDate]),
                new ConditionErrorMessage('simpleValues[2]', 'This value should be ' . $minDate . ' or more.', 'This value should be {{ limit }} or more.', ['{{ value }}' => self::formatDateTime($d3), '{{ limit }}' => $minDate]),
            ],
            $errorList2
        );

        self::assertEmpty($errorList3);
    }

    /** @test */
    public function it_validates_matchers(): void
    {
        $fieldSet = $this->getFieldSet(false);
        $fieldSet->add('username', TextType::class, ['constraints' => new Assert\NotBlank()]);
        $fieldSet = $fieldSet->getFieldSet();

        $errorList = new ErrorList();
        $this->validator->initializeContext($fieldSet->get('username'), $errorList);

        $this->validator->validate('foo', PatternMatch::class, 'foo', 'patternMatch[0].value');
        $this->validator->validate('bar', PatternMatch::class, 'foo', 'patternMatch[1].value');
        $this->validator->validate('', PatternMatch::class, 'foo', 'patternMatch[2].value');

        $this->assertContainsErrors(
            [
                new ConditionErrorMessage('patternMatch[2].value', 'This value should not be blank.', 'This value should not be blank.', ['{{ value }}' => '""']),
            ],
            $errorList
        );
    }

    private function assertContainsErrors(array $expectedErrors, ErrorList $errors): void
    {
        foreach ($errors as $error) {
            self::assertInstanceOf(ConstraintViolation::class, $error->cause);

            // Remove cause to make assertion possible.
            $error->cause = null;
        }

        self::assertEquals($expectedErrors, $errors->getArrayCopy());
    }

    /**
     * @param \DateTimeImmutable $value
     *
     * @return string
     */
    private static function formatDateTime($value)
    {
        if (\class_exists('IntlDateFormatter')) {
            $locale = \Locale::getDefault();
            $formatter = new \IntlDateFormatter($locale, \IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT);

            // neither the native nor the stub IntlDateFormatter support
            // DateTimeImmutable as of yet
            if (! $value instanceof \DateTime) {
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
