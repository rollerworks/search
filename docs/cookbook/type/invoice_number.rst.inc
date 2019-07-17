InvoiceNumber value class
-------------------------

First create the InvoiceNumber class that holds an invoice number.

This technique is known as a 'value class', the InvoiceNumber is immutable
meaning its internal values can't be changed.

.. code-block:: php
    :linenos:

    // src/Acme/Invoice/InvoiceNumber.php

    namespace Acme\Invoice;

    final class InvoiceNumber
    {
        private $year;
        private $number;

        private function __construct(int $year, int  $number)
        {
            $this->year = $year;
            $this->number = $number;
        }

        public static function createFromString(string $input): self
        {
            if (!preg_match('/^(?P<year>\d{4})-(?P<number>\d+)$/s', $input, $matches)) {
                throw new \InvalidArgumentException('This not a valid invoice number.');
            }

            return new InvoiceNumber((int) $matches['year'], (int) ltrim($matches['number'], '0'));
        }

        public function equals(InvoiceNumber $input): bool
        {
            return $input == $this;
        }

        public function isHigher(InvoiceNumber $input): bool
        {
            if ($this->year > $input->year) {
                return true;
            }

            if ($input->year === $this->year && $this->number > $input->number) {
                return true;
            }

            return false;
        }

        public function isLower(InvoiceNumber $input): bool
        {
            if ($this->year < $input->year) {
                return true;
            }

            if ($input->year === $this->year && $this->number < $input->number) {
                return true;
            }

            return false;
        }

        public function __toString(): string
        {
            // Return the invoice number with leading zero
            return sprintf('%d-%04d', $this->year, $this->number);
        }
    }
