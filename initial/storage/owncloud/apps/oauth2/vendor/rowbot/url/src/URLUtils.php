<?php
namespace Rowbot\URL;

use InvalidArgumentException;
use UConverter;

use function gettype;
use function is_object;
use function method_exists;
use function pack;
use function preg_match;
use function rawurlencode;
use function sprintf;
use function strlen;
use function substr;

abstract class URLUtils
{
    const REGEX_C0_CONTROLS = '/[\x{0000}-\x{001F}]/';
    const REGEX_ASCII_ALPHA = '/[\x{0041}-\x{005A}\x{0061}-\x{007A}]/';
    const REGEX_ASCII_ALPHANUMERIC = '/[\x{0030}-\x{0039}\x{0041}-\x{005A}'
        . '\x{0061}-\x{007A}]/';
    const REGEX_URL_CODE_POINTS = '/[\x{0030}-\x{0039}\x{0041}-\x{005A}'
        . '\x{0061}-\x{007A}'
        . '!$&\'()*+,\-.\/:;=?@_~'
        . '\x{00A0}-\x{D7DD}'
        . '\x{E000}-\x{FDCF}'
        . '\x{FDF0}-\x{FFFD}'
        . '\x{10000}-\x{1FFFD}'
        . '\x{20000}-\x{2FFFD}'
        . '\x{30000}-\x{3FFFD}'
        . '\x{40000}-\x{4FFFD}'
        . '\x{50000}-\x{5FFFD}'
        . '\x{60000}-\x{6FFFD}'
        . '\x{70000}-\x{7FFFD}'
        . '\x{80000}-\x{8FFFD}'
        . '\x{90000}-\x{9FFFD}'
        . '\x{A0000}-\x{AFFFD}'
        . '\x{B0000}-\x{BFFFD}'
        . '\x{C0000}-\x{CFFFD}'
        . '\x{D0000}-\x{DFFFD}'
        . '\x{E0000}-\x{EFFFD}'
        . '\x{F0000}-\x{FFFFD}'
        . '\x{100000}-\x{10FFFD}'
        . ']/u';
    const REGEX_WINDOWS_DRIVE_LETTER = '/^[A-Za-z][:|]$/u';
    const REGEX_NORMALIZED_WINDOWS_DRIVE_LETTER = '/^[A-Za-z]:$/u';
    const STARTS_WITH_WINDOWS_DRIVE_LETTER = '/^[A-Za-z][:|](?:$|[\/\\\?#])/u';

    const C0_CONTROL_PERCENT_ENCODE_SET = '\x00-\x1F\x7E-\x{10FFFF}';
    const FRAGMENT_PERCENT_ENCODE_SET   = self::C0_CONTROL_PERCENT_ENCODE_SET
        . '\x20"<>`';
    const PATH_PERCENT_ENCODE_SET       = self::FRAGMENT_PERCENT_ENCODE_SET
        . '#?{}';
    const USERINFO_PERCENT_ENCODE_SET   = self::PATH_PERCENT_ENCODE_SET
        . '\/:;=@[\\\\\]^|';

    /**
     * @see https://url.spec.whatwg.org/#special-scheme
     *
     * @var array<string, int|string>
     */
    public static $specialSchemes = [
        'ftp'    => 21,
        'file'   => '',
        'gopher' => 70,
        'http'   => 80,
        'https'  => 443,
        'ws'     => 80,
        'wss'    => 443
    ];

    /**
     * Decodes a percent encoded byte into a code point.
     *
     * @see https://url.spec.whatwg.org/#percent-decode
     *
     * @param string $byteSequence A byte sequence to be decoded.
     *
     * @return string
     */
    public static function percentDecode($byteSequence)
    {
        $output = '';

        for ($i = 0, $len = strlen($byteSequence); $i < $len; $i++) {
            if ($byteSequence[$i] !== '%') {
                $output .= $byteSequence[$i];
            } elseif (preg_match(
                '/%[A-Fa-f0-9]{2}/',
                substr($byteSequence, $i, 3)
            ) !== 1) {
                $output .= $byteSequence[$i];
            } else {
                // TODO: utf-8 decode without BOM
                $bytePoint = pack('H*', substr($byteSequence, $i + 1, 2));
                $output .= $bytePoint;
                $i += 2;
            }
        }

        return $output;
    }

    /**
     * Encodes a code point stream if the code point is not part of the
     * specified encode set.
     *
     * @see https://url.spec.whatwg.org/#utf-8-percent-encode
     *
     * @param string $codePoint         A code point stream to be encoded.
     * @param string $percentEncodedSet The encode set used to decide whether or
     *                                  not the code point should be percent
     *                                  encoded.
     *
     * @return string
     */
    public static function utf8PercentEncode(
        $codePoint,
        $percentEncodedSet = self::C0_CONTROL_PERCENT_ENCODE_SET
    ) {
        if (preg_match('/[' . $percentEncodedSet . ']/u', $codePoint) !== 1) {
            return $codePoint;
        }

        return rawurlencode($codePoint);
    }

    /**
     * Casts arguments to a string and attempts to fix invalid byte sequences.
     *
     * @param mixed $arg A value to cast to a string.
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public static function strval($arg)
    {
        if (!is_scalar($arg)
            || is_object($arg) && !method_exists($arg, '__toString')
        ) {
            throw new InvalidArgumentException(sprintf(
                'Only scalar values and objects with a __toString() method are'
                . ' considered valid input. Given value was of type %s.',
                gettype($arg)
            ));
        }

        return UConverter::transcode((string) $arg, 'UTF-8', 'UTF-8');
    }
}
