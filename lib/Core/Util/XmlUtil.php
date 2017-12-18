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

namespace Rollerworks\Component\Search\Util;

/**
 * XMLUtils is a bunch of utility methods to XML operations.
 *
 * This class contains static methods only and is not meant to be instantiated.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Martin Hasoň <martin.hason@gmail.com>
 *
 * @see https://raw.github.com/symfony/symfony/master/src/Symfony/Component/Config/Util/XmlUtils.php
 *
 * @internal
 */
final class XmlUtil
{
    /**
     * Parses an XML document.
     *
     * @param string      $content        An XML document as string
     * @param string|null $schema         An XSD schema file path
     * @param string      $defaultMessage
     *
     * @throws \InvalidArgumentException When loading of XML document returns an error
     *
     * @return \DOMDocument
     */
    public static function parseXml(string $content, string $schema = null, string $defaultMessage = 'The XML file is not valid.'): \DOMDocument
    {
        $internalErrors = libxml_use_internal_errors(true);
        $disableEntities = libxml_disable_entity_loader(true);
        libxml_clear_errors();

        try {
            $dom = new \DOMDocument();
            $dom->validateOnParse = true;

            if (!$dom->loadXML($content, LIBXML_NONET | (defined('LIBXML_COMPACT') ? LIBXML_COMPACT : 0))) {
                throw new \InvalidArgumentException(implode("\n", static::getXmlErrors()));
            }

            $dom->normalizeDocument();

            foreach ($dom->childNodes as $child) {
                if ($child->nodeType === XML_DOCUMENT_TYPE_NODE) {
                    throw new \InvalidArgumentException('Document types are not allowed.');
                }
            }

            if (null !== $schema) {
                // Allow to schema validator to load the DTD of the XSD schema
                libxml_disable_entity_loader(false);

                if (!@$dom->schemaValidate($schema)) {
                    throw new \InvalidArgumentException(implode("\n", static::getXmlErrors() ?? [$defaultMessage]));
                }
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($internalErrors);
            libxml_disable_entity_loader($disableEntities);
        }

        return $dom;
    }

    private static function getXmlErrors(): array
    {
        $errors = [];

        foreach (libxml_get_errors() as $error) {
            $errors[] = sprintf(
                '[%s %s] %s (in %s - line %d, column %d)',
                LIBXML_ERR_WARNING === $error->level ? 'WARNING' : 'ERROR',
                $error->code,
                trim($error->message),
                $error->file ?: 'n/a',
                $error->line,
                $error->column
            );
        }

        return $errors;
    }

    /**
     * This class should not be instantiated.
     */
    private function __construct()
    {
    }
}
