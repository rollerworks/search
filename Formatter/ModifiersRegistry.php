<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Formatter;

use Rollerworks\RecordFilterBundle\FilterConfig;
use Rollerworks\RecordFilterBundle\Formatter\FormatterInterface;
use Rollerworks\RecordFilterBundle\Formatter\Modifier\PreModifierInterface;
use Rollerworks\RecordFilterBundle\Formatter\Modifier\PostModifierInterface;
use Rollerworks\RecordFilterBundle\FilterStruct;

/**
 * The Modifiers Registry keeps all the registered pre and post modifiers.
 *
 * Modifiers (per type) are executed in order of there registration.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ModifiersRegistry
{
    /**
     * @var array
     */
    protected $preModifiers = array();

    /**
     * @var array
     */
    protected $postModifiers = array();

    /**
     * Register an pre-modifier instance.
     * This will be executed before the filters to object conversion.
     *
     * @param PreModifierInterface $modifier
     * @return ModifiersRegistry
     *
     * @api
     */
    public function registerPreModifier(PreModifierInterface $modifier)
    {
        $this->preModifiers[ $modifier->getModifierName() ] = $modifier;

        return $this;
    }

    /**
     * Register an post-modifier instance.
     * This will be executed after the filters to object conversion.
     *
     * @param PostModifierInterface $modifier
     * @return ModifiersRegistry
     *
     * @api
     */
    public function registerPostModifier(PostModifierInterface $modifier)
    {
        $this->postModifiers[ $modifier->getModifierName() ] = $modifier;

        return $this;
    }

    /**
     * Returns the registered pre modifiers.
     * Like: modifier-name => (Modifier Object)
     *
     * @return array
     *
     * @api
     */
    public function getPreModifiers()
    {
        return $this->preModifiers;
    }

    /**
     * Returns the registered pre modifiers.
     * Like: modifier-name => (Modifier Object)
     *
     * @return array
     *
     * @api
     */
    public function getPostModifiers()
    {
        return $this->postModifiers;
    }
}
