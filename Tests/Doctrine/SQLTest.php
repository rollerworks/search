<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Doctrine;

use Rollerworks\Bundle\RecordFilterBundle\Type\DateTimeExtended;
use Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\WhereBuilder;
use Rollerworks\Bundle\RecordFilterBundle\Mapping\Loader\AnnotationDriver;
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

        $container = $this->createContainer();
        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);

        $whereCase = $this->cleanSql($whereBuilder->getWhereClause($this->formatter));
        $this->assertEquals($expectedSql, $whereCase);
    }

    /**
     * @dataProvider provideWithQueryObjTests
     *
     * @param string $filterQuery
     * @param string $expectedSql
     * @param array  $params
     */
    public function testBasicsWithQueryObj($filterQuery, $expectedSql, array $params)
    {
        $input = $this->newInput($filterQuery);
        $this->assertTrue($this->formatter->formatInput($input));

        $container = $this->createContainer();
        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);

        $rsm = new \Doctrine\ORM\Query\ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'I');

        $query = $this->em->createNativeQuery("SELECT I.* FROM invoices AS I", $rsm);

        $whereCase = $this->cleanSql($whereBuilder->getWhereClause($this->formatter, array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I'), $query));
        $this->assertEquals($expectedSql, $whereCase);
        $this->assertQueryParamsEquals($params, $query);
    }

    public function testEmptyResult()
    {
        $input = $this->newInput('no_field=2;');
        $this->assertTrue($this->formatter->formatInput($input));

        $container = $this->createContainer();
        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);

        $whereCase = $this->cleanSql($whereBuilder->getWhereClause($this->formatter));
        $this->assertNull($whereCase);
    }

    /**
     * @dataProvider provideValueConversionTests
     *
     * @param string $filterQuery
     * @param string $expectedSql
     */
    public function testValueConversion($filterQuery, $expectedSql)
    {
        $input = $this->newInput($filterQuery, 'customer');
        $this->assertTrue($this->formatter->formatInput($input));

        $container = $this->createContainer();
        $container->set('customer_conversion', new \Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\CustomerConversion());

        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);

        $whereCase = $this->cleanSql($whereBuilder->getWhereClause($this->formatter));
        $this->assertEquals($expectedSql, $whereCase);
    }

    /**
     * @dataProvider provideFieldConversionTests
     *
     * @param string $filterQuery
     * @param string $expectedSql
     */
    public function testFieldConversion($filterQuery, $expectedSql)
    {
        $input = $this->newInput($filterQuery, 'invoice');
        $this->assertTrue($this->formatter->formatInput($input));

        $container = $this->createContainer();
        $container->set('customer_conversion', new \Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\CustomerConversion());

        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);
        $whereBuilder->setFieldConversion('invoice_customer', $container->get('customer_conversion'));

        $whereCase = $this->cleanSql($whereBuilder->getWhereClause($this->formatter));
        $this->assertEquals($expectedSql, $whereCase);
    }

    /**
     * @dataProvider provideFieldConversionWithQueryObjTests
     *
     * @param string $filterQuery
     * @param string $expectedDql
     * @param array  $params
     */
    public function testFieldConversionWithQueryObj($filterQuery, $expectedDql, $params)
    {
        $input = $this->newInput($filterQuery, 'invoice');
        $this->assertTrue($this->formatter->formatInput($input));

        $container = $this->createContainer();
        $container->set('customer_conversion', new \Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\CustomerConversion());

        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);
        $whereBuilder->setFieldConversion('invoice_customer', $container->get('customer_conversion'));

        $rsm = new \Doctrine\ORM\Query\ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'I');

        $query = $this->em->createNativeQuery("SELECT I.* FROM invoices AS I", $rsm);

        $whereCase = $this->cleanSql($whereBuilder->getWhereClause(
            $this->formatter,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I'),
            $query
        ));

        $this->assertEquals($expectedDql, $whereCase);
        $this->assertQueryParamsEquals($params, $query);
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

    public static function provideWithQueryObjTests()
    {
        return array(
            array('invoice_customer=2;', '(I.customer IN(:invoice_customer_0))', array('invoice_customer_0' => 2)),
            array('invoice_label=F2012-4242;', '(I.label IN(:invoice_label_0))', array('invoice_label_0' => 'F2012-4242')),
            array('invoice_customer=2, 5;', '(I.customer IN(:invoice_customer_0, :invoice_customer_1))', array('invoice_customer_0' => 2, 'invoice_customer_1' => 5)),
            array('invoice_customer=2-5;', '((I.customer BETWEEN :invoice_customer_0 AND :invoice_customer_1))', array('invoice_customer_0' => 2, 'invoice_customer_1' => 5)),
            array('invoice_customer=2-5, 8;', '(I.customer IN(:invoice_customer_0) AND (I.customer BETWEEN :invoice_customer_1 AND :invoice_customer_2))', array('invoice_customer_0' => 8, 'invoice_customer_1' => 2, 'invoice_customer_2' =>5)),
            array('invoice_customer=2-5,!8-10;', '((I.customer BETWEEN :invoice_customer_0 AND :invoice_customer_1) AND (I.customer NOT BETWEEN :invoice_customer_2 AND :invoice_customer_3))', array('invoice_customer_0' => 2, 'invoice_customer_1' => 5, 'invoice_customer_2' => 8, 'invoice_customer_3' => 10)),
            array('invoice_customer=2-5, !8;', '(I.customer NOT IN(:invoice_customer_0) AND (I.customer BETWEEN :invoice_customer_1 AND :invoice_customer_2))', array('invoice_customer_0' => 8, 'invoice_customer_1' => 2, 'invoice_customer_2' => 5)),
            array('invoice_customer=2-5, >8;', '((I.customer BETWEEN :invoice_customer_0 AND :invoice_customer_1) AND I.customer > :invoice_customer_2)', array('invoice_customer_0' => 2, 'invoice_customer_1' => 5, 'invoice_customer_2' => 8)),

            array('(invoice_customer=2;),(invoice_customer=3;)', '(I.customer IN(:invoice_customer_0)) OR (I.customer IN(:invoice_customer_1))', array('invoice_customer_0' => 2, 'invoice_customer_1' => 3)),
            array('(invoice_customer=2,3;),(invoice_customer=3,5;)', '(I.customer IN(:invoice_customer_0, :invoice_customer_1)) OR (I.customer IN(:invoice_customer_2, :invoice_customer_3))', array('invoice_customer_0' => 2, 'invoice_customer_1' => 3, 'invoice_customer_2' => 3, 'invoice_customer_3' => 5)),
            array('(invoice_customer=2,3; invoice_status=Active;),(invoice_customer=3,5;)', '(I.customer IN(:invoice_customer_0, :invoice_customer_1) AND I.status IN(:invoice_status_0)) OR (I.customer IN(:invoice_customer_2, :invoice_customer_3))', array('invoice_customer_0' => 2, 'invoice_customer_1' => 3, 'invoice_customer_2' => 3, 'invoice_customer_3' => 5, 'invoice_status_0' => 1)),
            array('invoice_date=06/13/2012;', '(I.date IN(:invoice_date_0))', array('invoice_date_0' => new DateTimeExtended('2012-06-13'))),

            // Expects empty as there is no field with that name
            array('(user=2;),(user=2;)', '', array()),
        );
    }

    public static function provideFieldConversionTests()
    {
        return array(
            array('invoice_customer=2;', '(CAST(customer AS customer_type) IN(2))'),
            array('invoice_label=F2012-4242;', '(label IN(\'F2012-4242\'))'),
            array('invoice_customer=2;invoice_label=F2012-4242;', "(CAST(customer AS customer_type) IN(2) AND label IN('F2012-4242'))"),
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

    public static function provideFieldConversionWithQueryObjTests()
    {
        return array(
            array('invoice_customer=2;', "(CAST(I.customer AS customer_type) IN(:invoice_customer_0))", array('invoice_customer_0' => 2)),
            array('invoice_customer=2;invoice_label=F2012-4242;', "(CAST(I.customer AS customer_type) IN(:invoice_customer_0) AND I.label IN(:invoice_label_0))", array('invoice_label_0' => 'F2012-4242', 'invoice_customer_0' => '2')),
            array('invoice_label=F2012-4242;', "(I.label IN(:invoice_label_0))", array('invoice_label_0' => 'F2012-4242')),
            array('invoice_customer=2-5,!8-10;', "((CAST(I.customer AS customer_type) BETWEEN :invoice_customer_0 AND :invoice_customer_1) AND (CAST(I.customer AS customer_type) NOT BETWEEN :invoice_customer_2 AND :invoice_customer_3))", array('invoice_customer_0' => 2, 'invoice_customer_1' => 5, 'invoice_customer_2' => 8, 'invoice_customer_3' => 10)),
            array('invoice_customer=2-5, !8;', "(CAST(I.customer AS customer_type) NOT IN(:invoice_customer_0) AND (CAST(I.customer AS customer_type) BETWEEN :invoice_customer_1 AND :invoice_customer_2))", array('invoice_customer_0' => 8, 'invoice_customer_1' => 2, 'invoice_customer_2' => 5)),
            array('invoice_customer=2, 5;', "(CAST(I.customer AS customer_type) IN(:invoice_customer_0, :invoice_customer_1))", array('invoice_customer_0' => 2, 'invoice_customer_1' => 5)),
            array('invoice_customer=2-5;', "((CAST(I.customer AS customer_type) BETWEEN :invoice_customer_0 AND :invoice_customer_1))", array('invoice_customer_0' => 2, 'invoice_customer_1' => 5)),
            array('invoice_customer=2-5, 8;', "(CAST(I.customer AS customer_type) IN(:invoice_customer_0) AND (CAST(I.customer AS customer_type) BETWEEN :invoice_customer_1 AND :invoice_customer_2))", array('invoice_customer_0' => 8, 'invoice_customer_1' => 2, 'invoice_customer_2' =>5)),
            array('invoice_customer=2-5, >8;', "((CAST(I.customer AS customer_type) BETWEEN :invoice_customer_0 AND :invoice_customer_1) AND CAST(I.customer AS customer_type) > :invoice_customer_2)", array('invoice_customer_0' => 2, 'invoice_customer_1' => 5, 'invoice_customer_2' => 8)),

            array('(invoice_customer=2;),(invoice_customer=3;)', "(CAST(I.customer AS customer_type) IN(:invoice_customer_0)) OR (CAST(I.customer AS customer_type) IN(:invoice_customer_1))", array('invoice_customer_0' => 2, 'invoice_customer_1' => 3)),
            array('(invoice_customer=2,3;),(invoice_customer=3,5;)', "(CAST(I.customer AS customer_type) IN(:invoice_customer_0, :invoice_customer_1)) OR (CAST(I.customer AS customer_type) IN(:invoice_customer_2, :invoice_customer_3))", array('invoice_customer_0' => 2, 'invoice_customer_1' => 3, 'invoice_customer_2' => 3, 'invoice_customer_3' => 5)),
            array('(invoice_customer=2,3; invoice_status=Active;),(invoice_customer=3,5;)', "(CAST(I.customer AS customer_type) IN(:invoice_customer_0, :invoice_customer_1) AND I.status IN(:invoice_status_0)) OR (CAST(I.customer AS customer_type) IN(:invoice_customer_2, :invoice_customer_3))", array('invoice_customer_0' => 2, 'invoice_customer_1' => 3, 'invoice_customer_2' => 3, 'invoice_customer_3' => 5, 'invoice_status_0' => 1)),
            array('invoice_date=06/13/2012;', "(I.date IN(:invoice_date_0))", array('invoice_date_0' => new DateTimeExtended('2012-06-13'))),

            // Expects empty as there is no field with that name
            array('(user=2;),(user=2;)', '', array()),
        );
    }

    public static function provideValueConversionTests()
    {
        return array(
            array('customer_id=2;', '(id IN(2))'),
        );
    }
}
