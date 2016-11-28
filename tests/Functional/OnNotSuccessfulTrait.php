<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Doctrine\Dbal\Functional;

// Type-hint was removed for PHP 7
if (PHP_MAJOR_VERSION > 5) {
    trait OnNotSuccessfulTrait
    {
        protected function onNotSuccessfulTest($e)
        {
            // Ignore deprecation warnings.
            if ($e instanceof \PHPUnit_Framework_AssertionFailedError ||
                ($e instanceof \PHPUnit_Framework_Warning && strpos($e->getMessage(), ' is deprecated,'))
            ) {
                throw $e;
            }

            if (isset($this->sqlLoggerStack->queries) && count($this->sqlLoggerStack->queries)) {
                $queries = '';
                $i = count($this->sqlLoggerStack->queries);

                foreach (array_reverse($this->sqlLoggerStack->queries) as $query) {
                    $params = array_map(
                        function ($p) {
                            if (is_object($p)) {
                                return get_class($p);
                            } else {
                                return "'".var_export($p, true)."'";
                            }
                        },
                        $query['params'] ?: []
                    );

                    $queries .= ($i + 1).". SQL: '".$query['sql']."' Params: ".implode(', ', $params).PHP_EOL;
                    --$i;
                }

                $trace = $e->getTrace();
                $traceMsg = '';

                foreach ($trace as $part) {
                    if (isset($part['file'])) {
                        if (strpos($part['file'], 'PHPUnit/') !== false) {
                            // Beginning with PHPUnit files we don't print the trace anymore.
                            break;
                        }

                        $traceMsg .= $part['file'].':'.$part['line'].PHP_EOL;
                    }
                }

                $message =
                    '['.get_class($e).'] '.
                    $e->getMessage().
                    PHP_EOL.PHP_EOL.
                    'With queries:'.PHP_EOL.
                    $queries.PHP_EOL.
                    'Trace:'.PHP_EOL.
                    $traceMsg;

                throw new \Exception($message, (int) $e->getCode(), $e);
            }

            throw $e;
        }
    }
} else {
    trait OnNotSuccessfulTrait
    {
        protected function onNotSuccessfulTest(\Exception $e)
        {
            if ($e instanceof \PHPUnit_Framework_AssertionFailedError) {
                throw $e;
            }

            if (isset($this->sqlLoggerStack->queries) && count($this->sqlLoggerStack->queries)) {
                $queries = '';
                $i = count($this->sqlLoggerStack->queries);

                foreach (array_reverse($this->sqlLoggerStack->queries) as $query) {
                    $params = array_map(
                        function ($p) {
                            if (is_object($p)) {
                                return get_class($p);
                            } else {
                                return "'".var_export($p, true)."'";
                            }
                        },
                        $query['params'] ?: []
                    );

                    $queries .= ($i + 1).". SQL: '".$query['sql']."' Params: ".implode(', ', $params).PHP_EOL;
                    --$i;
                }

                $trace = $e->getTrace();
                $traceMsg = '';

                foreach ($trace as $part) {
                    if (isset($part['file'])) {
                        if (strpos($part['file'], 'PHPUnit/') !== false) {
                            // Beginning with PHPUnit files we don't print the trace anymore.
                            break;
                        }

                        $traceMsg .= $part['file'].':'.$part['line'].PHP_EOL;
                    }
                }

                $message =
                    '['.get_class($e).'] '.
                    $e->getMessage().
                    PHP_EOL.PHP_EOL.
                    'With queries:'.PHP_EOL.
                    $queries.PHP_EOL.
                    'Trace:'.PHP_EOL.
                    $traceMsg;

                throw new \Exception($message, (int) $e->getCode(), $e);
            }

            throw $e;
        }
    }
}
