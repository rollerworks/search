<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Record;

use Rollerworks\Bundle\RecordFilterBundle\Record\Sql\WhereBuilder;
use Rollerworks\Bundle\RecordFilterBundle\Mapping\Loader\AnnotationDriver;
use Doctrine\Common\Annotations\AnnotationReader;
use Metadata\MetadataFactory;

class SQLTest extends OrmTestCase
{
    /**
     * @dataProvider provideBasicsTests
     *
     * @param string $filterQuery
     * @param string $expectedSql
     */
    public function testBasics($filterQuery, $expectedSql)
    {
        $input = $this->newInput($filterQuery);
        $this->assertTrue($this->formatter->formatInput($input));

        $annotationReader = new AnnotationReader();
        $container = $this->createContainer();

        $metadataFactory = new MetadataFactory(new AnnotationDriver($annotationReader));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);

        $whereCase = $this->cleanSql($whereBuilder->getWhereClause($input->getFieldSet(), $this->formatter));
        $this->assertEquals($expectedSql, $whereCase);
    }

    public function testEmptyResult()
    {
        $input = $this->newInput('no_field=2;');
        $this->assertTrue($this->formatter->formatInput($input));

        $annotationReader = new AnnotationReader();
        $container = $this->createContainer();

        $metadataFactory = new MetadataFactory(new AnnotationDriver($annotationReader));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);

        $whereCase = $this->cleanSql($whereBuilder->getWhereClause($input->getFieldSet(), $this->formatter));
        $this->assertNull($whereCase);
    }

    /**
     * @dataProvider provideSqlConvertTests
     *
     * @param string $filterQuery
     * @param string $expectedSql
     */
    public function testSqlValueConvert($filterQuery, $expectedSql)
    {
        $input = $this->newInput($filterQuery, 'customer');
        $this->assertTrue($this->formatter->formatInput($input));

        $annotationReader = new AnnotationReader();
        $container = $this->createContainer();
        $container->set('customer_conversion', new \Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\CustomerConversion());

        $metadataFactory = new MetadataFactory(new AnnotationDriver($annotationReader));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);

        $whereCase = $this->cleanSql($whereBuilder->getWhereClause($input->getFieldSet(), $this->formatter));
        $this->assertEquals($expectedSql, $whereCase);
    }

    /**
     * @dataProvider provideFieldConversionTests
     *
     * @param string $filterQuery
     * @param string $expectedSql
     */
    public function testSqlFieldConvert($filterQuery, $expectedSql)
    {
        $input = $this->newInput($filterQuery, 'invoice');
        $this->assertTrue($this->formatter->formatInput($input));

        $annotationReader = new AnnotationReader();
        $annotationReader->addGlobalIgnoredName('Id');
        $annotationReader->addGlobalIgnoredName('Column');
        $annotationReader->addGlobalIgnoredName('GeneratedValue');
        $annotationReader->addGlobalIgnoredName('OneToOne');
        $annotationReader->addGlobalIgnoredName('OneToMany');

        $container = $this->createContainer();
        $container->set('customer_conversion', new \Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\CustomerConversion());

        $metadataFactory = new MetadataFactory(new AnnotationDriver($annotationReader));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);
        $whereBuilder->setConversionForField('invoice_customer', $container->get('customer_conversion'));

        $whereCase = $this->cleanSql($whereBuilder->getWhereClause($input->getFieldSet(), $this->formatter));
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

            // Expects empty as there is no field with that name
            array('(user=2;),(user=2;)', ''),
        );
    }

    public static function provideFieldConversionTests()
    {
        return array(
            array('invoice_customer=2;', '(CAST(customer AS customer_type) IN(2))'),
            array('invoice_label=F2012-4242;', '(label IN(\'F2012-4242\'))'),
            array('invoice_customer=2, 5;', '(CAST(customer AS customer_type) IN(2, 5))'),
            array('invoice_customer=2-5;', '((CAST(customer AS customer_type) BETWEEN 2 AND 5))'),
            array('invoice_customer=2-5, 8;', '(CAST(customer AS customer_type) IN(8) AND (CAST(customer AS customer_type) BETWEEN 2 AND 5))'),
            array('invoice_customer=2-5,!8-10;', '((CAST(customer AS customer_type) BETWEEN 2 AND 5) AND (CAST(customer AS customer_type) NOT BETWEEN 8 AND 10))'),
            array('invoice_customer=2-5, !8;', '(CAST(customer AS customer_type) NOT IN(8) AND (CAST(customer AS customer_type) BETWEEN 2 AND 5))'),
            array('invoice_customer=2-5, >8;', '((CAST(customer AS customer_type) BETWEEN 2 AND 5) AND CAST(customer AS customer_type) > 8)'),

            array('(invoice_customer=2;),(invoice_customer=3;)', '(CAST(customer AS customer_type) IN(2)) OR (CAST(customer AS customer_type) IN(3))'),
            array('(invoice_customer=2,3;),(invoice_customer=3,5;)', '(CAST(customer AS customer_type) IN(2, 3)) OR (CAST(customer AS customer_type) IN(3, 5))'),
            array('(invoice_customer=2,3; invoice_status=Active;),(invoice_customer=3,5;)', '(CAST(customer AS customer_type) IN(2, 3) AND status IN(1)) OR (CAST(customer AS customer_type) IN(3, 5))'),
            array('invoice_date=06/13/2012;', '(pubdate IN(\'2012-06-13\'))'),

            // Expects empty as there is no field with that name
            array('(user=2;),(user=2;)', ''),
        );
    }

    public static function provideSqlConvertTests()
    {
        return array(
            array('customer_id=2;', '(id IN(2))'),
        );
    }
}
