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

namespace Rollerworks\Component\Search\Extension\Core\ChoiceList\View;

/**
 * Represents a choice list in templates.
 *
 * A choice list contains choices and optionally preferred choices which are
 * displayed in the very beginning of the list. Both choices and preferred
 * choices may be grouped in {@link ChoiceGroupView} instances.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ChoiceListView
{
    /**
     * The choices.
     *
     * @var ChoiceGroupView[]|ChoiceView[]
     */
    public $choices;

    /**
     * The preferred choices.
     *
     * @var ChoiceGroupView[]|ChoiceView[]
     */
    public $preferredChoices;

    /**
     * All the choices (without grouping).
     *
     * @var ChoiceView[]|null
     */
    public $choicesByLabel;

    /**
     * Label by the choice-value.
     *
     * @var string[]
     */
    public $labelsByValue;

    /**
     * Creates a new choice list view.
     *
     * @param ChoiceGroupView[]|ChoiceView[] $choices          The choice views
     * @param ChoiceGroupView[]|ChoiceView[] $preferredChoices the preferred
     *                                                         choice views
     */
    public function __construct(array $choices = [], array $preferredChoices = [])
    {
        $this->choices = $choices;
        $this->preferredChoices = $preferredChoices;
    }

    public function initChoicesByLabel(): void
    {
        $this->choicesByLabel = [];

        foreach ($this->choices as $view) {
            if ($view instanceof ChoiceGroupView) {
                foreach ($view->choices as $subView) {
                    $this->choicesByLabel[$subView->label] = $subView;
                    $this->labelsByValue[$subView->value] = $subView->label;
                }
            } else {
                $this->choicesByLabel[$view->label] = $view;
                $this->labelsByValue[$view->value] = $view->label;
            }
        }

        foreach ($this->preferredChoices as $view) {
            if ($view instanceof ChoiceGroupView) {
                foreach ($view->choices as $subView) {
                    $this->choicesByLabel[$subView->label] = $subView;
                    $this->labelsByValue[$subView->value] = $subView->label;
                }
            } else {
                $this->choicesByLabel[$view->label] = $view;
                $this->labelsByValue[$view->value] = $view->label;
            }
        }
    }
}
