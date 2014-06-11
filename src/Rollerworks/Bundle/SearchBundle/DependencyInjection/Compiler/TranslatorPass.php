<?php

/**
 * This file is part of the RollerworksSearchBundle package.
 *
 * (c) 2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\SearchBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;

/**
 * Register Search Component translations.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class TranslatorPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('translator.default')) {
            return;
        }

        $translator = $container->findDefinition('translator.default');

        // Discover translation directories
        $dirs = array();
        if (class_exists('Rollerworks\Component\Search\FieldSet')) {
            $r = new \ReflectionClass('Rollerworks\Component\Search\FieldSet');

            $dirs[] = dirname($r->getFilename()) . '/Resources/translations';
        }

        // Register translation resources
        // Note. Translations are prepended to ensure they can be overwritten
        if ($dirs) {
            $methodCalls = array();

            foreach ($dirs as $dir) {
                $container->addResource(new DirectoryResource($dir));
            }

            $finder = Finder::create()
                ->files()
                ->filter(function (\SplFileInfo $file) {
                    return 2 === substr_count($file->getBasename(), '.') && preg_match('/\.\w+$/', $file->getBasename());
                })
                ->in($dirs)
            ;

            $calls = $translator->getMethodCalls();
            // Ensure the setFallbackLocale() is called first
            foreach ($calls as $i => $call) {
                if ('addResource' !== $call[0]) {
                    $methodCalls[] = array($call[0], $call[1]);

                    unset($calls[$i]);
                }
            }

            foreach ($finder as $file) {
                // filename is domain.locale.format
                list($domain, $locale, $format) = explode('.', $file->getBasename(), 3);
                $methodCalls[] = array('addResource', array($format, (string) $file, $locale, $domain));
            }

            $calls = array_merge($methodCalls, $calls);
            $translator->setMethodCalls($calls);
        }
    }
}
