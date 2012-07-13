<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Factory;

use Symfony\Component\Translation\TranslatorInterface;
use Rollerworks\Bundle\RecordFilterBundle\Mapping\FilterTypeConfig;
use Rollerworks\Bundle\RecordFilterBundle\FilterField;
use Rollerworks\Bundle\RecordFilterBundle\FieldSet;

/**
 * This factory is used to creating FieldSet Classes at runtime.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FieldSetFactory extends AbstractFactory
{
    /**
     * @var FilterTypeFactory
     */
    protected $typeFactory;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var string
     */
    protected $translatorPrefix;

    /**
     * @var string
     */
    protected $translatorDomain = 'filter';

    /**
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param FilterTypeFactory $factory
     */
    public function setTypesFactory(FilterTypeFactory $factory)
    {
        $this->typeFactory = $factory;
    }

    /**
     * Set the resolving of an field name to label, using the translator beginning with prefix.
     *
     * @param string $pathPrefix This prefix is added before every search, like filters.labels.
     * @param string $domain
     *
     * @return self
     *
     * @throws \InvalidArgumentException
     */
    public function setLabelResolver($pathPrefix, $domain = 'filter')
    {
        if (!is_string($pathPrefix) && null !== $pathPrefix) {
            throw new \InvalidArgumentException('Prefix must be an string or null.');
        }

        if (!is_string($domain) || empty($domain)) {
            throw new \InvalidArgumentException('Domain must be an string and can not be empty.');
        }

        $this->translatorPrefix = $pathPrefix;
        $this->translatorDomain = $domain;

        return $this;
    }

    /**
     * Returns an WhereBuilder instance based on the given FieldSet.
     *
     * @param string $name
     *
     * @return FieldSet
     *
     * @throws \InvalidArgumentException
     */
    public function getFieldSet($name)
    {
        if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name)) {
            throw new \InvalidArgumentException(sprintf('Unknown FieldSet "%s", (must be legal an class-name).', $name));
        }

        $fqn = $this->namespace . $name . '\FieldSet';

        if (!class_exists($fqn, false)) {
            $fileName = $this->classesDir . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . 'FieldSet.php';

            if (!file_exists($fileName)) {
                throw new \InvalidArgumentException(sprintf('Unknown FieldSet "%s", file does not exist.', $name));
            }

            require $fileName;
        }

        return new $fqn($this->typeFactory, $this->translator, $this->translatorPrefix, $this->translatorDomain);
    }

    /**
     * Generates all the FieldSet Classes.
     *
     * The FilterType of an FilterField must be an FilterTypeConfig object.
     *
     * @param FieldSet[] $classes
     * @param string     $toDir   The target directory of the Classes. If not specified, the directory configured by this factory is used.
     *
     * @throws \InvalidArgumentException
     */
    public function generateClasses(array $classes, $toDir = null)
    {
        if (null === $toDir) {
            $toDir = $this->classesDir;
        }

        foreach ($classes as $fieldSet) {
            if (null === $fieldSet->getSetName()) {
                throw new \InvalidArgumentException('FieldSet must have a unique-name.');
            }

            $this->generateClass($fieldSet->getSetName(), $fieldSet, $toDir);
        }
    }

    /**
     * @param FieldSet $fieldSet
     *
     * @return string
     */
    protected function generateFieldsList(FieldSet $fieldSet)
    {
        $fields = '';

        $fieldTemplate = '        $this->fields[%s] = FilterField::create(%s, %s, %s, %s, %s)';

        foreach ($fieldSet->all() as $fieldName => $field) {
            $fieldNameSafe = var_export($fieldName, true);

            $fields .= sprintf(
                $fieldTemplate,
                $fieldNameSafe,
                '$translator->trans($transPrefix . ' . $fieldNameSafe . ', array(), $transDomain)',
                $this->generateType($field->getType()),
                var_export($field->isRequired(), true),
                var_export($field->acceptRanges(), true),
                var_export($field->acceptCompares(), true)
            );

            if (null !== $field->getPropertyRefClass()) {
               $fields .= sprintf('->setPropertyRef()', var_export($field->getPropertyRefClass(), true), var_export($field->getPropertyRefField(), true));
            }

            $fields .= ";\n";
        }

        return rtrim($fields);
    }

    /**
     * Generates a FieldSet class file.
     *
     * @param string   $ns
     * @param FieldSet $fieldSet
     * @param string   $toDir
     *
     * @throws \RuntimeException
     */
    protected function generateClass($ns, FieldSet $fieldSet, $toDir)
    {
        $constructor = $this->generateFieldsList($fieldSet);

        $placeholders = array('<namespace>', '<set_name>', '<fields>');
        $replacements = array($this->namespace . $ns, $fieldSet->getSetName(), $constructor);

        $file = str_replace($placeholders, $replacements, self::$classTemplate);
        $dir  = $toDir . DIRECTORY_SEPARATOR . $ns;

        if (!is_dir($dir) && !mkdir($dir)) {
            throw new \RuntimeException('Was unable to create the sub-dir for the RecordFilter::FieldSet.');
        }

        file_put_contents($dir . DIRECTORY_SEPARATOR . 'FieldSet.php', $file, LOCK_EX);
    }

    /**
     * @param FilterTypeConfig $type
     *
     * @return string
     *
     * @throws \InvalidArgumentException on invalid type
     */
    private function generateType(FilterTypeConfig $type = null)
    {
        if (null === $type) {
            return 'null';
        }

        return sprintf('$typeFactory->newInstance(%s, %s)', var_export($type->getName(), true), ($type->hasParams() ? var_export($type->getParams(), true) : 'array()'));
    }

    /**
     * Class code template
     */
    private static $classTemplate =
'<?php

namespace <namespace>;

use Symfony\Component\Translation\TranslatorInterface;
use Rollerworks\Bundle\RecordFilterBundle\FilterField;
use Rollerworks\Bundle\RecordFilterBundle\FieldSet as BaseFieldSet;
use Rollerworks\Bundle\RecordFilterBundle\Factory\FilterTypeFactory;

/**
 * THIS CLASS WAS GENERATED BY Rollerworks/RecordFilterBundle. DO NOT EDIT THIS FILE.
 */
class FieldSet extends BaseFieldSet
{
    public function __construct(FilterTypeFactory $typeFactory, TranslatorInterface $translator, $transPrefix, $transDomain)
    {
        parent::__construct(\'<set_name>\');

<fields>
    }

    public function set($name, FilterField $config)
    {
        throw new \LogicException(\'Impossible to call set() on a frozen FieldSet.\');
    }

    public function replace($name, FilterField $config)
    {
        throw new \LogicException(\'Impossible to call replace() on a frozen FieldSet.\');
    }

    public function remove($name)
    {
        throw new \LogicException(\'Impossible to call remove() on a frozen FieldSet.\');
    }
}
';
}
