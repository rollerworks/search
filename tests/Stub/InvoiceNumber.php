<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Doctrine\Dbal\Stub;

class InvoiceNumber
{
    private $year;
    private $number;

    private function __construct($year, $number)
    {
        $this->year = $year;
        $this->number = $number;
    }

    public static function createFromString($input)
    {
        if (!is_string($input) || !preg_match('/^(?P<year>\d{4})-(?P<number>\d+)$/', $input, $matches)) {
            throw new \InvalidArgumentException('This not a valid invoice number.');
        }

        return new self((int) $matches['year'], (int) ltrim($matches['number'], '0'));
    }

    public function equals(InvoiceNumber $input)
    {
        return $input == $this;
    }

    public function isHigher(InvoiceNumber $input)
    {
        if ($this->year > $input->year) {
            return true;
        }

        if ($input->year === $this->year && $this->number > $input->number) {
            return true;
        }

        return false;
    }

    public function isLower(InvoiceNumber $input)
    {
        if ($this->year < $input->year) {
            return true;
        }

        if ($input->year === $this->year && $this->number < $input->number) {
            return true;
        }

        return false;
    }

    public function __toString()
    {
        // Return the invoice number with leading zero
        return sprintf('%d-%04d', $this->year, $this->number);
    }
}
