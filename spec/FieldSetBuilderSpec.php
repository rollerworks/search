<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace spec\Rollerworks\Component\Search;

use PhpSpec\ObjectBehavior;
use Rollerworks\Component\Search\Exception\BadMethodCallException;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\Metadata\MetadataReaderInterface;
use Rollerworks\Component\Search\Metadata\SearchField as MappingSearchField;
use Rollerworks\Component\Search\ResolvedFieldTypeInterface;
use Rollerworks\Component\Search\SearchFactoryInterface;
use Rollerworks\Component\Search\SearchField;

// Autoloading is not possible for this
require_once __DIR__.'/Fixtures/Entity/User.php';
require_once __DIR__.'/Fixtures/Entity/Group.php';

class FieldSetBuilderSpec extends ObjectBehavior
{
    public function let(SearchFactoryInterface $searchFactory)
    {
        $this->beConstructedWith('test', $searchFactory->getWrappedObject());
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\FieldSetBuilder');
    }

    public function it_has_a_name()
    {
        $this->getName()->shouldReturn('test');
    }

    public function it_allows_adding_fields()
    {
        $this->add('id', 'integer');
        $this->has('id')->shouldReturn(true);

        $this->get('id')->shouldBeLike(array(
            'type' => 'integer',
            'options' => array(),
            'required' => false,
        ));
    }

    public function it_allows_adding_preconfigured_fields()
    {
        $this->add('id', 'integer');

        $this->has('id')->shouldReturn(true);

        $this->get('id')->shouldBeLike(array(
            'type' => 'integer',
            'options' => array(),
            'required' => false,
        ));
    }

    public function it_allows_removing_fields()
    {
        $this->add('id', 'integer');
        $this->has('id')->shouldReturn(true);

        $this->remove('id');
        $this->has('id')->shouldReturn(false);
    }

    public function it_supports_importing_fields_from_metadata(SearchFactoryInterface $searchFactory, MetadataReaderInterface $mappingReader)
    {
        $this->beConstructedWith('test', $searchFactory->getWrappedObject(), $mappingReader);

        $searchFieldsUser = array(
            'uid' => new MappingSearchField('uid', 'User', 'id', true, 'integer', array('min' => 1)),
            'username' => new MappingSearchField('username', 'User', 'name', false, 'text'),
        );

        $searchFieldsGroup = array(
            'gid' => new MappingSearchField('gid', 'Group', 'id', false, 'integer'),
            'group-name' => new MappingSearchField('group-name', 'Group', 'name', false, 'text'),
        );

        $mappingReader->getSearchFields('User')->willReturn($searchFieldsUser);
        $mappingReader->getSearchFields('Group')->willReturn($searchFieldsGroup);

        $this->importFromClass('User');
        $this->importFromClass('Group');

        $this->has('uid')->shouldReturn(true);
        $this->has('username')->shouldReturn(true);

        $this->has('gid')->shouldReturn(true);
        $this->has('group-name')->shouldReturn(true);

        $this->get('uid')->shouldBeLike(array(
            'type' => 'integer',
            'options' => array(
                'min' => 1,
                'model_class' => 'User',
                'model_property' => 'id'
            ),
            'required' => true,
        ));

        $this->get('username')->shouldBeLike(array(
            'type' => 'text',
            'options' => array(
                'model_class' => 'User',
                'model_property' => 'name'
            ),
            'required' => false,
        ));

        $this->get('gid')->shouldBeLike(array(
            'type' => 'integer',
            'options' => array(
                'model_class' => 'Group',
                'model_property' => 'id'
            ),
            'required' => false,
        ));

        $this->get('group-name')->shouldBeLike(array(
            'type' => 'text',
            'options' => array(
                'model_class' => 'Group',
                'model_property' => 'name'
            ),
            'required' => false,
        ));
    }

    public function it_builds_the_fieldset(SearchFactoryInterface $searchFactory, ResolvedFieldTypeInterface $resolvedType)
    {
        $this->beConstructedWith('test', $searchFactory->getWrappedObject());

        $resolvedType->getName()->willReturn('integer');

        $field1 = new SearchField('id', $resolvedType->getWrappedObject(), array('max' => 5000));
        $field1->setRequired();

        $field2 = new SearchField('gid', $resolvedType->getWrappedObject());
        $field2->setModelRef('Rollerworks\Component\Search\Fixtures\Entity\Group', 'name');

        $searchFactory->createField('id', 'integer', array('max' => 5000), true)->willReturn($field1);
        $searchFactory->createField(
            'gid',
            'integer',
            array(
                'model_class' => 'Rollerworks\Component\Search\Fixtures\Entity\Group',
                'model_property' => 'name'
            ),
            false
        )->willReturn($field2);

        $this->add('id', 'integer', array('max' => 5000), true);
        $this->add(
            'gid',
            'integer',
            array(
                'model_class' => 'Rollerworks\Component\Search\Fixtures\Entity\Group',
                'model_property' => 'name'
            ),
            false
        );

        $expectedFieldSet = new FieldSet('test');

        $expField = new SearchField('id', $resolvedType->getWrappedObject(), array('max' => 5000));
        $expField->setRequired();
        $expectedFieldSet->set('id', $expField);

        $expField = new SearchField('gid', $resolvedType->getWrappedObject());
        $expField->setModelRef('Rollerworks\Component\Search\Fixtures\Entity\Group', 'name');
        $expectedFieldSet->set('gid', $expField);

        $this->getFieldSet()->shouldBeLike($expectedFieldSet);
    }

    public function it_errors_when_calling_methods_after_building(SearchFactoryInterface $searchFactory, ResolvedFieldTypeInterface $resolvedType)
    {
        $this->beConstructedWith('test', $searchFactory->getWrappedObject());

        $resolvedType->getName()->willReturn('integer');

        $field1 = new SearchField('id', $resolvedType->getWrappedObject(), array('max' => 5000));
        $field1->setRequired();

        $field2 = new SearchField('gid', $resolvedType->getWrappedObject());
        $field2->setModelRef('Rollerworks\Component\Search\Fixtures\Entity\Group', 'name');

        $searchFactory->createField('id', 'integer', array('max' => 5000), true)->willReturn($field1);
        $searchFactory->createField(
            'gid',
            'integer',
            array(
                'model_class' => 'Rollerworks\Component\Search\Fixtures\Entity\Group',
                'model_property' => 'name'
            ),
            false
        )->willReturn($field2);

        $this->add('id', 'integer', array('max' => 5000), true);
        $this->add(
            'gid',
            'integer',
            array(
                'model_class' => 'Rollerworks\Component\Search\Fixtures\Entity\Group',
                'model_property' => 'name'
            ),
            false
        );

        $this->getFieldSet();

        $this->shouldThrow(new BadMethodCallException('FieldSetBuilder methods cannot be accessed anymore once the builder is turned into a FieldSet instance.'))->during('get', array('id'));
        $this->shouldThrow(new BadMethodCallException('FieldSetBuilder methods cannot be accessed anymore once the builder is turned into a FieldSet instance.'))->during('has', array('id'));
        $this->shouldThrow(new BadMethodCallException('FieldSetBuilder methods cannot be accessed anymore once the builder is turned into a FieldSet instance.'))->during('remove', array('id'));
    }
}
