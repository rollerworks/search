<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\Rollerworks\Component\Search\Metadata\Driver;

use Metadata\Driver\AdvancedFileLocatorInterface;
use Metadata\MergeableClassMetadata;
use PhpSpec\ObjectBehavior;
use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Rollerworks\Component\Search\Metadata\PropertyMetadata;

// Autoloading is not possible for this
require_once __DIR__ . '/../../Fixtures/Entity/User.php';
require_once __DIR__ . '/../../Fixtures/Entity/Group.php';

class YamlFileDriverSpec extends ObjectBehavior
{
    function let(AdvancedFileLocatorInterface $locator)
    {
        $this->beConstructedWith($locator);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\Metadata\Driver\YamlFileDriver');
        $this->shouldImplement('Metadata\Driver\DriverInterface');
    }

    function it_reads_the_metadata(AdvancedFileLocatorInterface $locator)
    {
        $this->beConstructedWith($locator);

        $reflection = new \ReflectionClass('Rollerworks\Component\Search\Fixtures\Entity\User');
        $locator->findFileForClass($reflection, 'yml')->willReturn(__DIR__ . '/../../Fixtures/Config/Entity.User.yml');

        $classMetadata = new MergeableClassMetadata($reflection->name);
        $classMetadata->createdAt = null;
        $classMetadata->reflection = null;

        $propertyMetadata = new PropertyMetadata($reflection->name, 'id');
        $propertyMetadata->reflection = null;
        $propertyMetadata->fieldName = 'uid';
        $propertyMetadata->required = true;
        $propertyMetadata->type = 'integer';
        $classMetadata->addPropertyMetadata($propertyMetadata);

        $propertyMetadata = new PropertyMetadata($reflection->name, 'name');
        $propertyMetadata->reflection = null;
        $propertyMetadata->fieldName = 'username';
        $propertyMetadata->type = 'text';
        $propertyMetadata->options = array('name' => 'doctor', 'last' => array('who', 'zeus'));

        $classMetadata->addPropertyMetadata($propertyMetadata);

        $this->loadMetadataForClass($reflection, true)->shouldBeLike($classMetadata);
    }

    function it_validates_the_metadata(AdvancedFileLocatorInterface $locator)
    {
        $this->beConstructedWith($locator);

        $reflection = new \ReflectionClass('Rollerworks\Component\Search\Fixtures\Entity\User');
        $locator->findFileForClass($reflection, 'yml')->willReturn(__DIR__ . '/../../Fixtures/Config/Entity.User-invalid.yml');

        $this->shouldThrow(new InvalidArgumentException('No "type" found in property metadata of class "Rollerworks\Component\Search\Fixtures\Entity\User" property "name".'))->during('loadMetadataForClass', array($reflection, true));
    }
}
