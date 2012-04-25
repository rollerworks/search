<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Metadata;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Metadata\MetadataFactoryInterface;

/**
 * AbstractConfigProcessor
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractConfigProcessor
{
    /**
     * @var \Metadata\MetadataFactoryInterface
     */
    protected $metadataFactory;

    /**
     * DIC container instance
     *
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * Construct.
     *
     * @param \Metadata\MetadataFactoryInterface $metadataFactory
     */
    public function __construct(MetadataFactoryInterface $metadataFactory)
    {
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * Set the DIC container for types that need it
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Returns the final parameters list for the filter-type constructor.
     *
     * @param array                  $parameters
     * @param string                 $className
     * @param \ReflectionParameter[] $reflectionParameters
     * @return array
     * @throws \RuntimeException
     */
    protected function doGetArguments(array $parameters, $className, array $reflectionParameters)
    {
        $arguments = array();

        foreach ($reflectionParameters as $param) {
            if (array_key_exists($param->getName(), $parameters)) {
                $argument = $parameters[$param->getName()];
            }
            elseif ($param->isDefaultValueAvailable()) {
                $argument = $param->getDefaultValue();
            }
            else {
                throw new \RuntimeException(sprintf('Type "%s" requires that you provide a value for the "$%s" argument (because there is no default value or because there is a non optional argument after this one).', $className, $param->getName()));
            }

            if (is_scalar($argument)) {
                if ('%' === $argument[0]) {
                    $argument = $this->callServiceMethod($argument, $parameters);
                }
                elseif ('\\' === $argument[0]) {
                    $argument = substr($argument, 1);
                }
            }

            $arguments[] = $argument;
        }

        return $arguments;
    }

    /**
     * Finds service method-call and returns the result.
     *
     * An service method-call is as "%service_name%:method"
     * When no method call was found it returns the original input.
     *
     * The method will receive the $parameters as-is (not processed).
     *
     * @param string  $input
     * @param array   $parameters
     * @return mixed
     *
     * @throws \RuntimeException
     */
    private function callServiceMethod($input, array $parameters)
    {
        if (!preg_match('/^%(?P<service>[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff.-]*)%:(?P<method>[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$/us', $input, $serviceCall)) {
            return $input;
        }

        if (null === $this->container) {
            throw new \RuntimeException('One or arguments require that a container is set.');
        }

        return call_user_func(array($this->container->get($serviceCall['service']), $serviceCall['method']), $parameters);
    }
}
