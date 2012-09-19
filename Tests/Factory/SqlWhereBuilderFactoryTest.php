<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Factory;

use Rollerworks\Bundle\RecordFilterBundle\FieldSet;
use Rollerworks\Bundle\RecordFilterBundle\Input\FilterQuery;
use Rollerworks\Bundle\RecordFilterBundle\Mapping\Loader\AnnotationDriver;
use Rollerworks\Bundle\RecordFilterBundle\Factory\SqlWhereBuilderFactory;
use Rollerworks\Bundle\RecordFilterBundle\Tests\Doctrine\OrmTestCase;
use Doctrine\Common\Annotations\AnnotationReader;
use Metadata\MetadataFactory;

class SqlWhereBuilderFactoryTest extends OrmTestCase
{
    /**
     * @var SqlWhereBuilderFactory
     */
    protected $factory;

    protected function setUp()
    {
        parent::setUp();

        $annotationReader = new AnnotationReader();
        $annotationReader->addGlobalIgnoredName('Id');
        $annotationReader->addGlobalIgnoredName('Column');
        $annotationReader->addGlobalIgnoredName('GeneratedValue');
        $annotationReader->addGlobalIgnoredName('OneToOne');
        $annotationReader->addGlobalIgnoredName('OneToMany');

        $metadataFactory = new MetadataFactory(new AnnotationDriver($annotationReader));

        $cacheDir = __DIR__ . '/../.cache/record_filter';

        if (!is_dir($cacheDir) && !mkdir($cacheDir, 0777, true)) {
            throw new \RuntimeException('Was unable to create the sub-dir for the RecordFilter::Record::Sql::WhereBuilder.');
        }

        $container = $this->createContainer();
        $container->set('customer_conversion', new \Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\CustomerConversion());

        $this->factory = new SqlWhereBuilderFactory(__DIR__ . '/../.cache/record_filter', 'RecordFilter', true);
        $this->factory->setEntityManager($this->em);
        $this->factory->setMetadataFactory($metadataFactory);
        $this->factory->setContainer($container);
    }

    /**
     * @dataProvider provideBasicsTests
     *
     * @param $filterQuery
     * @param string $expectedSql
     */
    public function testBasics($filterQuery, $expectedSql)
    {
        $fieldSet = $this->getFieldSet('invoice');

        $input = $this->newInput($filterQuery, $fieldSet);
        $this->assertTrue($this->formatter->formatInput($input));

        $whereCase = $this->cleanSql($this->factory->getWhereBuilder($fieldSet)->getWhereClause($input->getFieldSet(), $this->formatter));
        $this->assertEquals($expectedSql, $whereCase);
    }

    public static function provideBasicsTests()
    {
        return array(
            array('invoice_customer=2;', '(customer IN(2))'),
            array('invoice_label=F2012-4242;', '(label IN(\'F2012-4242\'))'),
            array('invoice_customer=2, 5;', '(customer IN(2, 5))'),
            array('invoice_customer=2-5;', '((customer BETWEEN 2 AND 5))'),
            array('invoice_customer=2-5, 8;', '(customer IN(8) AND (customer BETWEEN 2 AND 5))'),
            array('invoice_customer=2-5,!8-10;', '((customer BETWEEN 2 AND 5) AND (customer NOT BETWEEN 8 AND 10))'),
            array('invoice_customer=2-5, !8;', '(customer NOT IN(8) AND (customer BETWEEN 2 AND 5))'),
            array('invoice_customer=2-5, >8;', '((customer BETWEEN 2 AND 5) AND customer > 8)'),

            array('(invoice_customer=2;),(invoice_customer=3;)', '(customer IN(2)) OR (customer IN(3))'),
            array('(invoice_customer=2,3;),(invoice_customer=3,5;)', '(customer IN(2, 3)) OR (customer IN(3, 5))'),
            array('(invoice_customer=2,3; invoice_status=Active;),(invoice_customer=3,5;)', '(customer IN(2, 3) AND status IN(1)) OR (customer IN(3, 5))'),
            array('invoice_date=06/13/2012;', '(pubdate IN(\'2012-06-13\'))'),

            array('(oops=06/13/2012;), (invoice_date=06/13/2012;)', '(pubdate IN(\'2012-06-13\'))'),

            // Expects empty as there is no field with that name
            array('(user=2;),(user=2;)', ''),
        );
    }
}
