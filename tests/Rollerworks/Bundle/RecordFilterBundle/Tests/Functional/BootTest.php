<?php

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Functional;

class BootTest extends BaseTestCase
{
    /**
     * Tests to make sure the kernel boots with the default configuration.
     *
     * @runInSeparateProcess
     */
    public function testDefaultConfig()
    {
        $client = $this->createClient();

        $this->assertTrue($client->getContainer()->has('rollerworks_record_filter.formatter'));
    }

    /**
     * Tests to make sure the configuration is processed.
     *
     * @runInSeparateProcess
     */
    public function testAutoGenerate()
    {
        $client = $this->createClient(array('config' => 'enable_auto_generate.yml'));

        $this->assertTrue($client->getContainer()->has('rollerworks_record_filter.fieldset_factory'));

        /** @var \Rollerworks\Bundle\RecordFilterBundle\Factory\FieldSetFactory $fieldSetFactory */
        $fieldSetFactory = $client->getContainer()->get('rollerworks_record_filter.fieldset_factory');

        $this->assertInstanceOf('Rollerworks\Bundle\RecordFilterBundle\FieldSet', $fieldSetFactory->getFieldSet('users'));
    }
}
