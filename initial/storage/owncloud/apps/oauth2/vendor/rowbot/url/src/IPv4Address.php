<?php
namespace Rowbot\URL;

use function array_pop;
use function count;
use function ctype_digit;
use function ctype_xdigit;
use function decoct;
use function explode;
use function floor;
use function gmp_cmp;
use function gmp_init;
use function gmp_pow;
use function intval;
use function is_string;
use function octdec;
use function strlen;
use function substr;

class IPv4Address implements NetworkAddress
{
    /**
     * @var \GMP
     */
    private $address;

    /**
     * Constructor.
     *
     * @param \GMP $address
     *
     * @return void
     */
    protected function __construct($address)
    {
        $this->address = $address;
    }

    /**
     * @return void
     */
    public function __clone()
    {
        // Since GMP is an object and not an int, we need to clone that object.
        $this->address = clone $this->address;
    }

    /**
     * Takes a string and parses it as an IPv4 address.
     *
     * @see https://url.spec.whatwg.org/#concept-ipv4-parser
     *
     * @param string $input A string representing an IPv4 address.
     *
     * @return self|string|bool Returns a IPv4Address object if the input is a valid IPv4 address or a string if the
     *                          input is determined to be a domain. This will return false if the input is neither a
     *                          domain or IPv4 address.
     */
    public static function parse($input)
    {
        $validationError = false;
        $parts = explode('.', $input);
        $len = count($parts);

        // If the last item in parts is an empty string, that is a syntax
        // violation, remove it from parts.
        if ($parts[$len - 1] === '') {
            $validationError = true;

            if ($len > 1) {
                array_pop($parts);
                $len--;
            }
        }

        // If there are more that 4 parts, this clearly isn't an IPv4 address
        // return the input given to us as this is probably a domain name.
        if ($len > 4) {
            return $input;
        }

        $numbers = [];

        foreach ($parts as $part) {
            // If any of the parts are an empty string, then this probably a
            // domain, so return the original input.
            if ($part === '') {
                return $input;
            }

            $n = self::parseIPv4Number($part, $validationError);

            // If the part is not a number, then this is probably a domain, so
            // return the original input.
            if ($n === false) {
                return $input;
            }

            $numbers[] = $n;
        }

        if ($validationError) {
            // Validation error
        }

        foreach ($numbers as $number) {
            if ($number > 255) {
                // Validation error
                break;
            }
        }

        $len = count($numbers);

        for ($i = 0; $i < $len - 1; $i++) {
            if ($numbers[$i] > 255) {
                return false;
            }
        }

        $cmp = gmp_cmp($numbers[$len - 1], gmp_pow('256', (5 - $len)));

        if ($cmp >= 0) {
            // Validation error
            return false;
        }

        $ipv4 = array_pop($numbers);
        $counter = 0;

        foreach ($numbers as $n) {
            $ipv4 += gmp_mul($n, gmp_pow('256', (3 - $counter)));
            $counter++;
        }

        return new self($ipv4);
    }

    /**
     * {@inheritDoc}
     *
     * @see https://url.spec.whatwg.org/#concept-ipv4-serializer
     */
    public function __toString()
    {
        $output = '';
        $n = $this->address;

        for ($i = 0; $i < 4; $i++) {
            $output = intval($n % 256, 10) . $output;

            if ($i < 3) {
                $output = '.' . $output;
            }

            $n = floor($n / 256);
        }

        return $output;
    }

    /**
     * {@inheritDoc}
     */
    public function equals($address)
    {
        if ($address instanceof self) {
            return $this->address == $address->address;
        }

        if (is_string($address)) {
            $parsed = self::parse($address);

            return $parsed instanceof self
                && $this->address == $parsed->address;
        }

        return false;
    }

    /**
     * Takes a string and parses it as a valid IPv4 number.
     *
     * @see https://url.spec.whatwg.org/#ipv4-number-parser
     *
     * @param string $input           A string of numbers to be parsed.
     * @param bool   $validationError A flag that represents if there was a validation error while parsing.
     *
     * @return \GMP|false Returns false on failure and an GMP object otherwise.
     */
    protected static function parseIPv4Number($input, &$validationError)
    {
        $R = 10;
        $len = strlen($input);

        if ($len > 1
            && (substr($input, 0, 2) === '0x' || substr($input, 0, 2) === '0X')
        ) {
            $validationError = true;
            $input = substr($input, 2);
            $R = 16;
        } elseif ($len > 1 && $input[0] === '0') {
            $validationError = true;
            $input = substr($input, 1);
            $R = 8;
        }

        // Check for $input being false here since substr() will return false
        // if the start position is the same as the string's length on
        // PHP 5.6.
        if ($input === '' || $input === false) {
            return gmp_init(0, 10);
        }

        if (($R == 10 && !ctype_digit($input)) ||
            ($R == 16 && !ctype_xdigit($input)) ||
            ($R == 8 && decoct(octdec($input)) != $input)) {
            return false;
        }

        // Return the mathematical integer value that is represented by
        // input in radix-R notation, using ASCII hex digits for digits with
        // values 0 through 15.
        return gmp_init($input, $R);
    }
}
