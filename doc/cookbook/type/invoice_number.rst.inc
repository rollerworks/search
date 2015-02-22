InvoiceNumber value class
-------------------------

First create the InvoiceNumber class that holds an invoice number.

This technique is known as a 'value class', the InvoiceNumber is immutable
meaning its internal values can't be changed.

.. code-block:: php
    :linenos:

    // src/Acme/Invoice/InvoiceNumber.php

    namespace Acme\Invoice;

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
            if (!is_string($input) || !preg_match('/^(?P<year>\d{4})-(?P<number>\d+)$/s', $input, $matches)) {
                throw new \InvalidArgumentException('This not a valid invoice number.');
            }

            return new InvoiceNumber((int) $matches['year'], (int) ltrim($matches['number'], '0'));
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
