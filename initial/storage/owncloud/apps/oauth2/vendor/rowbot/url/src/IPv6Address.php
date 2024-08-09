<?php
namespace Rowbot\URL;

use function base_convert;
use function ctype_digit;
use function ctype_xdigit;
use function intval;
use function is_string;
use function mb_strtolower;
use function mb_substr;

class IPv6Address implements NetworkAddress
{
    /**
     * @var array<int, int>
     */
    private $address;

    /**
     * Constructor.
     *
     * @param array<int, int> $address
     *
     * @return void
     */
    protected function __construct($address)
    {
        $this->address = $address;
    }

    /**
     * Parses an IPv6 string.
     *
     * @see https://url.spec.whatwg.org/#concept-ipv6-parser
     *
     * @param string $input An IPv6 address.
     *
     * @return self|false Returns an instance of itself, or false if the input is not a valid IPv6 address.
     */
    public static function parse($input)
    {
        $address = [0, 0, 0, 0, 0, 0, 0, 0];
        $pieceIndex = 0;
        $compress = null;
        $pointer = 0;
        $c = mb_substr($input, $pointer, 1, 'UTF-8');

        if ($c === ':') {
            if (mb_substr($input, $pointer + 1, 1, 'UTF-8') !== ':') {
                // Validation error.
                return false;
            }

            $pointer += 2;
            $pieceIndex++;
            $compress = $pieceIndex;
            $c = mb_substr($input, $pointer, 1, 'UTF-8');
        }

        while ($c !== '') {
            if ($pieceIndex == 8) {
                // Validation error.
                return false;
            }

            if ($c === ':') {
                if ($compress !== null) {
                    // Validation error.
                    return false;
                }

                $c = mb_substr($input, ++$pointer, 1, 'UTF-8');
                $pieceIndex++;
                $compress = $pieceIndex;
                continue;
            }

            $value = 0;
            $length = 0;

            while ($length < 4 && ctype_xdigit($c)) {
                $value = $value * 0x10 + intval($c, 16);
                $length++;
                $c = mb_substr($input, ++$pointer, 1, 'UTF-8');
            }

            if ($c === '.') {
                if ($length == 0) {
                    // Validation error.
                    return false;
                }

                $pointer -= $length;
                $c = mb_substr($input, $pointer, 1, 'UTF-8');

                if ($pieceIndex > 6) {
                    // Validation error.
                    return false;
                }

                $numbersSeen = 0;

                while ($c !== '') {
                    $ipv4Piece = null;

                    if ($numbersSeen > 0) {
                        if ($c === '.' && $numbersSeen < 4) {
                            $c = mb_substr($input, ++$pointer, 1, 'UTF-8');
                        } else {
                            // Validation error.
                            return false;
                        }
                    }

                    if (!ctype_digit($c)) {
                        // Validation error.
                        return false;
                    }

                    do {
                        $number = (int) $c;

                        if ($ipv4Piece === null) {
                            $ipv4Piece = $number;
                        } elseif ($ipv4Piece === 0) {
                            // Validation error.
                            return false;
                        } else {
                            $ipv4Piece = $ipv4Piece * 10 + $number;
                        }

                        if ($ipv4Piece > 255) {
                            // Validation error.
                            return false;
                        }

                        $c = mb_substr($input, ++$pointer, 1, 'UTF-8');
                    } while (ctype_digit($c));

                    $address[$pieceIndex] = $address[
                        $pieceIndex
                    ] * 0x100 + $ipv4Piece;
                    $numbersSeen++;

                    if ($numbersSeen == 2 || $numbersSeen == 4) {
                        $pieceIndex++;
                    }
                }

                if ($numbersSeen != 4) {
                    // Validation error.
                    return false;
                }

                break;
            }

            if ($c === ':') {
                $c = mb_substr($input, ++$pointer, 1, 'UTF-8');

                if ($c === '') {
                    // Validation error.
                    return false;
                }
            } elseif ($c !== '') {
                // Validation error.
                return false;
            }

            $address[$pieceIndex] = $value;
            $pieceIndex++;
        }

        if ($compress !== null) {
            $swaps = $pieceIndex - $compress;
            $pieceIndex = 7;

            while ($pieceIndex != 0 && $swaps > 0) {
                $temp = $address[$pieceIndex];
                $address[$pieceIndex] = $address[$compress + $swaps - 1];
                $address[$compress + $swaps - 1] = $temp;
                $pieceIndex--;
                $swaps--;
            }
        } elseif ($pieceIndex != 8) {
            // Validation error.
            return false;
        }

        return new self($address);
    }

    /**
     * Finds the longest sequence, with a length greater than 1, of 16-bit
     * pieces that are 0 and sets $compress to the first 16-bit piece in that
     * sequence, otherwise $compress will remain null.
     *
     * @return array<int, int|null> The first item is the compress pointer, which indicates where in the address it can
     *                              start compression, or null if the address isn't compressable. The second item is the
     *                              the length of the longest sequence of zeroes.
     */
    private function getLongestSequence()
    {
        $i = 0;
        $longestSequence = 1;
        $compress = null;

        while ($i < 8) {
            if ($this->address[$i] == 0) {
                $sequenceLength = 0;

                while ($i < 8 && $this->address[$i] == 0) {
                    ++$sequenceLength;
                    ++$i;
                }

                // We are only interested in sequences with a length greater
                // than one. We also only want to note the first of those
                // sequences since there may be multiple sequences of zero that
                // have the same length.
                if ($sequenceLength > $longestSequence) {
                    $longestSequence = $sequenceLength;
                    $compress = $i - $sequenceLength;
                }
            }

            ++$i;
        }

        return [$compress, $longestSequence];
    }

    /**
     * {@inheritDoc}
     *
     * @see https://url.spec.whatwg.org/#concept-ipv6-serializer
     */
    public function __toString()
    {
        $output = '';
        list($compress, $longestSequence) = $this->getLongestSequence();
        $pieceIndex = 0;

        while ($pieceIndex < 8) {
            if ($compress === $pieceIndex) {
                $output .= $pieceIndex == 0 ? '::' : ':';

                // Advance the pointer to $compress + $longestSequence
                // to skip over all 16-bit pieces that are 0 that immediately
                // follow the piece at $compress.
                $pieceIndex = $compress + $longestSequence;
                continue;
            }

            $output .= mb_strtolower(base_convert(
                (string) $this->address[$pieceIndex],
                10,
                16
            ), 'UTF-8');

            if ($pieceIndex < 7) {
                $output .= ':';
            }

            ++$pieceIndex;
        }

        return $output;
    }

    /**
     * {@inheritDoc}
     */
    public function equals($address)
    {
        if ($address instanceof self) {
            return $this->address === $address->address;
        }

        if (is_string($address)) {
            $parsed = self::parse($address);

            return $parsed instanceof self
                && $this->address === $parsed->address;
        }

        return false;
    }
}
