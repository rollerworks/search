<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Factory;

use Symfony\Component\Translation\TranslatorInterface;

use Rollerworks\RecordFilterBundle\Formatter\ModifiersRegistry;
use Rollerworks\RecordFilterBundle\Formatter\Modifier\PreModifierInterface;
use Rollerworks\RecordFilterBundle\Formatter\Modifier\PostModifierInterface;
use Rollerworks\RecordFilterBundle\Formatter\FilterConfig;

use \RuntimeException;

/**
 * This factory is used to create 'Domain specific' RecordFilter::Formatter Classes at runtime.
 * The information is read from the Annotations of the Class.
 *
 * The intent of this approach is to provide an interface that is Domain specific.
 * So its safe to assume that the 'correct' filtering configuration is used.
 *
 * This factory annotations uses the public API for registering filters.
 *
 * @api
 */
class FormatterFactory extends AbstractFactory
{
    /**
     * Default translator instance
     *
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    protected $translator;

    /**
     * @var ModifiersRegistry
     */
    protected $modifiersRegistry = null;

    /**
     * Set the default translator for validations messages.
     *
     * @param \Symfony\Component\Translation\TranslatorInterface $translator
     *
     * @api
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Set an ModifiersRegistry instance
     *
     * @param \Rollerworks\RecordFilterBundle\Formatter\ModifiersRegistry $registry
     *
     * @api
     */
    public function setModifiersRegistry(ModifiersRegistry $registry)
    {
        $this->modifiersRegistry = $registry;
    }

    /**
     * Returns whether is an modifier instance registered.
     *
     * @return bool
     *
     * @api
     */
    public function hasModifiersRegistry()
    {
        return $this->modifiersRegistry !== null;
    }

    /**
     * Returns the current ModifiersRegistry instance.
     *
     * When there is no instance an RuntimeException gets thrown.
     *
     * @throws \RuntimeException
     * @return \Rollerworks\RecordFilterBundle\Formatter\ModifiersRegistry
     *
     * @api
     */
    public function getModifiersRegistry()
    {
        if (null === $this->modifiersRegistry) {
            throw new RuntimeException('No ModifiersRegistry instance registered.');
        }

        return $this->modifiersRegistry;
    }

    /**
     * Gets a new instance for the filter Formatter by the given class.
     *
     * The annotations can either be an Class containing the Annotations or an annotations by name.
     * This can't be an Repository object, since the annotations name information is not accessible.
     *
     * @param string|object                                         $class
     * @param \Symfony\Component\Translation\TranslatorInterface    $translator
     * @return \Rollerworks\RecordFilterBundle\Formatter\DomainAwareFormatterInterface
     *
     * @api
     */
    public function getFormatter($class, TranslatorInterface $translator = null)
    {
        if (empty($translator) && empty($this->translator)) {
            throw new \RuntimeException('No Translator configured/given.');
        }
        elseif (empty($translator)) {
            $translator = $this->translator;
        }

        if (is_object($class)) {
            $class = get_class($class);
        }

        $entityNs = str_replace('\\', '', $class);
        $FQN      = $this->namespace . $entityNs . '\Formatter';

        if (!class_exists($FQN, false)) {
            $fileName = $this->classesDir . DIRECTORY_SEPARATOR . $entityNs . DIRECTORY_SEPARATOR . 'Formatter.php';

            if ($this->autoGenerate) {
                $this->generateClass($class, $entityNs, $this->annotationReader->getClassAnnotations(new \ReflectionClass($class)), $this->classesDir);
            }

            require $fileName;
        }

        /** @var \Rollerworks\RecordFilterBundle\Formatter\Formatter $formatter */
        $formatter = new $FQN($translator);

        if (null !== $this->modifiersRegistry) {
            $formatter->setModifiersRegistry($this->modifiersRegistry);
        }

        if (null !== $this->container) {
            $formatter->setContainer($this->container);
        }

        return $formatter;
    }

    /**
     * Generates a annotations file.
     *
     * @param string $class
     * @param string $entityCompactNS
     * @param object $annotations
     * @param string $toDir
     */
    protected function generateClass($class, $entityCompactNS, $annotations, $toDir)
    {
        $validations = $this->generateValidations($annotations);

        $placeholders = array('<namespace>', '<OrigClass>', '<validations>');
        $replacements = array($this->namespace . $entityCompactNS, $class, $validations);

        $file = str_replace($placeholders, $replacements, self::$_ClassTemplate);
        $dir  = $toDir . DIRECTORY_SEPARATOR . $entityCompactNS;

        if (!file_exists($dir) && !mkdir($dir, 0777, true)) {
            throw new \RuntimeException('Was unable to create the Entity sub-dir for the RecordFilter::Formatter.');
        }

        file_put_contents($dir . DIRECTORY_SEPARATOR . 'Formatter.php', $file, LOCK_EX);
    }

    /**
     * Get validations array from the Class Annotations
     *
     * @param array $annotations
     * @return string
     */
    protected function generateValidations(array $annotations)
    {
        $validations = '';

        foreach ($annotations as $annotation) {
            if (!$annotation instanceof \Rollerworks\RecordFilterBundle\Annotation\Field) {
                continue;
            }

            /** @var \Rollerworks\RecordFilterBundle\Annotation\Field $annotation */

            $filterType = $annotation->getType();

            if (!empty($filterType) && false === strpos($filterType, '\\')) {
                $filterType = '\\Rollerworks\\RecordFilterBundle\\Formatter\\Type\\' . ucfirst($filterType);
            }
            elseif (!empty($filterType)) {
                $filterType = '\\'. ltrim($filterType, '\\');
            }

            $validations .= '       $this->setField(';
            $validations .= var_export($annotation->getName(), true) . ',';

            if (!empty($filterType)) {
                $constructParams = '';

                if (!class_exists($filterType)) {
                    throw new \InvalidArgumentException('Failed to find the filter-type annotations: ' . $filterType);
                }
                elseif ($annotation->hasParams()) {
                    $classReflection = new \ReflectionClass($filterType);

                    if ($classReflection->isAbstract() || $classReflection->isInterface()) {
                        throw new \InvalidArgumentException('Filter-type annotations can not be abstract or interface: ' . $filterType);
                    }
                    elseif (!$classReflection->implementsInterface('\\Rollerworks\\RecordFilterBundle\\Formatter\\FilterTypeInterface')) {
                        throw new \InvalidArgumentException('Filter-type annotations does seem to implement the FilterTypeInterface interface: ' . $filterType);
                    }

                    if ($classReflection->hasMethod('__construct')) {
                        $constructParams = $this->compileConstructorParams($classReflection->getMethod('__construct'), $annotation->getParams(), $filterType);
                    }
                }

                $validations .= sprintf('new %s (%s), ', $filterType, $constructParams);
            }
            else {
                $validations .= 'null,';
            }

            $validations .= var_export($annotation->isRequired(), true) . ',';
            $validations .= var_export($annotation->acceptsRanges(), true) . ',';
            $validations .= var_export($annotation->acceptsCompares(), true);

            $validations .= ");\n";
        }

        return rtrim($validations);
    }

    /** Formatter annotations code template */
    private static $_ClassTemplate =
'<?php

namespace <namespace>;

use \Rollerworks\RecordFilterBundle\Formatter\Formatter as FormatterBase;
use \Rollerworks\RecordFilterBundle\Formatter\DomainAwareFormatterInterface;
use \Rollerworks\RecordFilterBundle\Formatter\FilterConfig;

/**
 * THIS CLASS WAS GENERATED BY Rollerworks::RecordFilterBundle. DO NOT EDIT THIS FILE.
 */
class Formatter extends FormatterBase implements DomainAwareFormatterInterface
{
    protected function __init()
    {
<validations>
    }

    public function getBaseClassName()
    {
        return \'<OrigClass>\';
    }
}';
}
