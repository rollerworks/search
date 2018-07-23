<?php

declare(strict_types=1);

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search;

use Rollerworks\Component\Search\Exception\UnknownFieldException;
use Rollerworks\Component\Search\Field\FieldConfig;

/**
 * A FieldSet holds all the search fields and there configuration.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class GenericFieldSet implements FieldSetWithView
{
    private $fields = [];
    private $name;
    private $viewBuilder;

    /**
     * Constructor.
     *
     * @param FieldConfig[] $fields
     * @param string|null   $name        FQCN of the FieldSet configurator
     * @param callable      $viewBuilder A callable to finalize the FieldSetView
     */
    public function __construct(array $fields, ?string $name = null, callable $viewBuilder = null)
    {
        $this->fields = $fields;
        $this->name = $name;
        $this->viewBuilder = $viewBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getSetName(): ?string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $name): FieldConfig
    {
        if (!isset($this->fields[$name])) {
            throw new UnknownFieldException($name);
        }

        return $this->fields[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return $this->fields;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $name): bool
    {
        return isset($this->fields[$name]);
    }

    public function isPrivate(string $name): bool
    {
        return '_' === $name[0];
    }

    /**
     * {@inheritdoc}
     */
    public function createView(): FieldSetView
    {
        $view = new FieldSetView();

        foreach ($this->fields as $name => $field) {
            $view->fields[$name] = $field->createView($view);
        }

        if (null !== $this->viewBuilder) {
            call_user_func($this->viewBuilder, $view);
        }

        return $view;
    }
}
