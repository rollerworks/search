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

namespace Rollerworks\Bundle\SearchBundle\Tests\Functional\Application\AppBundle\Controller;

use Rollerworks\Bundle\SearchBundle\Tests\Fixtures\FieldSet\UserFieldSet;
use Rollerworks\Component\Search\ConditionErrorMessage;
use Rollerworks\Component\Search\Processor\ProcessorConfig;
use Rollerworks\Component\Search\Processor\SearchProcessor;
use Rollerworks\Component\Search\SearchFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SearchController
{
    private $searchFactory;
    private $searchProcessor;
    private $urlGenerator;

    public function __construct(SearchFactory $searchFactory, SearchProcessor $searchProcessor, UrlGeneratorInterface $urlGenerator)
    {
        $this->searchFactory = $searchFactory;
        $this->searchProcessor = $searchProcessor;
        $this->urlGenerator = $urlGenerator;
    }

    public function __invoke(Request $request)
    {
        $config = new ProcessorConfig($this->searchFactory->createFieldSet(UserFieldSet::class));
        $payload = $this->searchProcessor->processRequest($request, $config);

        if ($payload->isChanged() && $payload->isValid()) {
            return new RedirectResponse($this->urlGenerator->generate('search', ['search' => $payload->searchCode]));
        }

        if ($payload->isValid()) {
            return new Response('VALID: '.($payload->searchCode ?: 'EMPTY'));
        }

        return new Response(
            'INVALID: <ul>'.implode(
                "\n",
                array_map(
                    function (ConditionErrorMessage $e) {
                        return '<li>'.$e->message.'</li>';
                    },
                    $payload->messages
                )
            ).'</ul>', 500
        );
    }
}
