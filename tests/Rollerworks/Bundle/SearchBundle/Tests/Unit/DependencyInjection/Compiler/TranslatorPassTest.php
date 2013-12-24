<?php

/*
 * This file is part of the RollerworksSearchBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\SearchBundle\Tests\Unit\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Rollerworks\Bundle\SearchBundle\DependencyInjection\Compiler\TranslatorPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Finder\Finder;

class TranslatorPassTest extends AbstractCompilerPassTestCase
{
    public function testRegisteringOfTranslations()
    {
        $collectingService = new Definition();
        $collectingService->addMethodCall('setFallback', array('en'));
        $collectingService->addMethodCall('addResource', array('symfony/validator.en.xlf'));
        $collectingService->addMethodCall('addResource', array('symfony/messages.en.xlf'));
        $this->setDefinition('translator.default', $collectingService);

        // Discover translation directories
        $r = new \ReflectionClass('Rollerworks\Component\Search\FieldSet');
        $transDir = dirname($r->getFilename()) . '/Resources/translations';

        $this->compile();

        // Use the finder for better future compatibility
        $finder = Finder::create()
                ->files()
                ->filter(function (\SplFileInfo $file) {
                    return 2 === substr_count($file->getBasename(), '.') && preg_match('/\.\w+$/', $file->getBasename());
                })
                ->in($transDir);

        $expectedCalls = array(
            array('setFallback', array('en')),
        );

        foreach ($finder as $file) {
            // filename is domain.locale.format
            list($domain, $locale, $format) = explode('.', $file->getBasename(), 3);
            $expectedCalls[] = array('addResource', array($format, (string) $file, $locale, $domain));
        }

        $expectedCalls[] = array('addResource', array('symfony/validator.en.xlf'));
        $expectedCalls[] = array('addResource', array('symfony/messages.en.xlf'));

        $collectingService = $this->container->findDefinition('translator.default');
        $this->assertEquals($expectedCalls, $collectingService->getMethodCalls());
    }

    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new TranslatorPass());
    }
}
