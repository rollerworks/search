<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\SearchBundle\Tests\Functional\Application\AppBundle\Controller;

use Rollerworks\Bundle\SearchBundle\Form\Type\SearchFormType;
use Rollerworks\Component\Search\Exception\InvalidSearchConditionException;
use Rollerworks\Component\Search\Input\ProcessorConfig;
use Rollerworks\Component\Search\ValuesError;
use Rollerworks\Component\Search\ValuesGroup;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SearchController extends Controller
{
    public function searchAction(Request $request)
    {
        $fieldSet = $this->get('rollerworks_search.fieldset_registry')->get('users');
        $config = new ProcessorConfig($fieldSet);

        $searchProcessor = $this->container->get('rollerworks_search.processor.search_processor_factory')->createProcessor($config, 'user');
        $searchProcessor->processRequest($request);

        if (method_exists('\Symfony\Component\Form\AbstractType', 'getName')) {
            $form = $this->createForm(new SearchFormType(), ['filter' => $searchProcessor->exportSearchCondition('filter_query')]);
        } else {
            $form = $this->createForm('Rollerworks\Bundle\SearchBundle\Form\Type\SearchFormType', ['filter' => $searchProcessor->exportSearchCondition('filter_query')]);
        }

        $form->handleRequest($request);

        if ($searchProcessor->isSubmitted()) {
            return $this->redirect($this->generateUrl('search'), ['filter' => $searchProcessor->getSearchCode()]);
        }

        if ($searchProcessor->isValid()) {
            return new Response('VALID: '.($searchProcessor->getSearchCode() ?: 'EMPTY'));
        }

        return new Response('INVALID: '.$this->displaySearchErrors($searchProcessor->getErrors()));
    }

    private function displaySearchErrors(array $errors)
    {
        $return = '';

        if (!count($errors)) {
            return '';
        }

        $return .= '<ul>';

        foreach ($errors as $error) {
            if ($error instanceof InvalidSearchConditionException) {
                $return .= '<li>'.$this->displaySearchValuesGroupErrors($error->getCondition()->getValuesGroup()).'</li>';
            } elseif ($error instanceof ValuesError) {
                $return .= '<li>'.$this->displaySearchValuesError($error).'</li>';
            }
        }

        $return .= '</ul>';

        return $return;
    }

    private function displaySearchValuesGroupErrors(ValuesGroup $group, $nestingLevel = 0)
    {
        $return = '';

        if (!$group->hasErrors(true)) {
            return '';
        }

        $fields = $group->getFields();

        foreach ($fields as $fieldName => $values) {
            $errors = $values->getErrors();

            if ($values->hasErrors()) {
                $return .= sprintf('<span>Field %s has the following errors: </span>', $fieldName);
                $return .= '<ul>';

                foreach ($errors as $valueError) {
                    $return .= '<li>'.$this->displaySearchValuesError($valueError).'</li>';
                }

                $return .= '</ul>';
            }
        }

        $return .= '<ul>';

        foreach ($group->getGroups() as $subGroup) {
            $return .= '<li>'.$this->displaySearchValuesGroupErrors($subGroup, ++$nestingLevel).'</li>';
        }

        $return .= '</ul>';

        return $return;
    }

    private function displaySearchValuesError(ValuesError $error)
    {
        if ('.' !== $subPath = $error->getSubPath()) {
            return sprintf('<span>%s:</span> %s', $subPath, htmlspecialchars($error->getMessage()));
        }

        return htmlspecialchars($error->getMessage());
    }
}
