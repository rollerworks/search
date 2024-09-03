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

namespace Rollerworks\Bundle\SearchBundle\Tests;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Rollerworks\Bundle\SearchBundle\TranslatorBasedAliasResolver;
use Rollerworks\Bundle\SearchBundle\Type\TranslatableFieldTypeExtension;
use Rollerworks\Bundle\SearchBundle\Type\TranslatableOrderFieldTypeExtension;
use Rollerworks\Component\Search\Extension\Core\Type\TextType;
use Rollerworks\Component\Search\Field\OrderFieldType;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
final class TranslatorBasedAliasResolverTest extends SearchIntegrationTestCase
{
    use ProphecyTrait;

    /** @test */
    public function it_uses_field_name_when_label_is_empty(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->never())->method('trans');

        $resolver = new TranslatorBasedAliasResolver($translator);
        $searchFactory = $this->getFactory();

        $this->assertEquals('id', $resolver($searchFactory->createField('id', TextType::class)));
        $this->assertEquals('name', $resolver($searchFactory->createField('name', TextType::class)));
        $this->assertEquals('@id', $resolver($searchFactory->createField('@id', OrderFieldType::class)));
    }

    /** @test */
    public function it_keeps_label_as_is_when_not_translatable(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->never())->method('trans');

        $resolver = new TranslatorBasedAliasResolver($translator);
        $searchFactory = $this->getFactory();

        $this->assertEquals('id2', $resolver($searchFactory->createField('id', TextType::class, ['label' => 'id2'])));
        $this->assertEquals('name2', $resolver($searchFactory->createField('name', TextType::class, ['label' => 'name2'])));
        $this->assertEquals('@id2', $resolver($searchFactory->createField('@id', OrderFieldType::class, ['label' => '@id2'])));
        $this->assertEquals('@id2', $resolver($searchFactory->createField('@id', OrderFieldType::class, ['label' => 'id2'])));
    }

    /** @test */
    public function it_translates_label_when_translatable(): void
    {
        $translator = $this->prophesize(TranslatorInterface::class);
        $translator->trans(Argument::cetera())->will(static fn(array $args): string => $args[0][0] === '@' ? $args[0] . 'T' : 'T' . $args[0]);

        $resolver = new TranslatorBasedAliasResolver($translator->reveal());
        $searchFactory = $this->getFactory();

        $this->assertEquals('Tid2', $resolver($searchFactory->createField('id', TextType::class, ['label' => new TranslatableMessage('id2')])));
        $this->assertEquals('Tname2', $resolver($searchFactory->createField('name', TextType::class, ['label' => new TranslatableMessage('name2')])));
        $this->assertEquals('@id2T', $resolver($searchFactory->createField('@id', OrderFieldType::class, ['label' => new TranslatableMessage('@id2')])));
        $this->assertEquals('@Tid2', $resolver($searchFactory->createField('@id', OrderFieldType::class, ['label' => new TranslatableMessage('id2')])));
    }

    protected function getTypeExtensions(): array
    {
        return [
            new TranslatableFieldTypeExtension(),
            new TranslatableOrderFieldTypeExtension(),
        ];
    }
}
