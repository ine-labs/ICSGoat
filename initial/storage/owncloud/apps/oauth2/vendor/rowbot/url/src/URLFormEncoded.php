<?php
namespace Rowbot\URL;

use UConverter;

use function explode;
use function mb_strpos;
use function rawurldecode;
use function rawurlencode;
use function str_replace;
use function strlen;

trait URLFormEncoded
{
    /**
     * Decodes a application/x-www-form-urlencoded string and returns the
     * decoded pairs as a list.
     *
     * Note: A legacy server-oriented implementation might have to support
     * encodings other than UTF-8 as well as have special logic for tuples of
     * which the name is `_charset_`. Such logic is not described here as only
     * UTF-8' is conforming.
     *
     * @see https://url.spec.whatwg.org/#concept-urlencoded-parser
     *
     * @param string $input
     *
     * @return array<int, array<string, string>>
     */
    private function urldecode($input)
    {
        // Let sequences be the result of splitting input on 0x26 (&).
        $sequences = explode('&', $input);

        // Let output be an initially empty list of name-value tuples where
        // both name and value hold a string.
        $output = [];

        foreach ($sequences as $bytes) {
            if ($bytes === '') {
                continue;
            }

            // If bytes contains a 0x3D (=), then let name be the bytes from the
            // start of bytes up to but excluding its first 0x3D (=), and let
            // value be the bytes, if any, after the first 0x3D (=) up to the
            // end of bytes. If 0x3D (=) is the first byte, then name will be
            // the empty byte sequence. If it is the last, then value will be
            // the empty byte sequence. Otherwise, let name have the value of
            // bytes and let value be the empty byte sequence.
            $name = $bytes;
            $value = '';

            if (mb_strpos($bytes, '=', 0, 'UTF-8') !== false) {
                list($name, $value) = explode('=', $bytes, 2);
            }

            // Replace any 0x2B (+) in name and value with 0x20 (SP).
            list($name, $value) = str_replace('+', "\x20", [$name, $value]);

            // Let nameString and valueString be the result of running UTF-8
            // decode without BOM on the percent decoding of name and value,
            // respectively.
            $name = UConverter::transcode(
                rawurldecode($name),
                'UTF-8',
                'UTF-8'
            );
            $value = UConverter::transcode(
                rawurldecode($value),
                'UTF-8',
                'UTF-8'
            );
            $output[] = ['name'  => $name, 'value' => $value];
        }

        return $output;
    }

    /**
     * @see https://url.spec.whatwg.org/#concept-urlencoded-string-parser
     *
     * @param string $input
     *
     * @return array<int, array<string, string>>
     */
    private function urldecodeString($input)
    {
        return $this->urldecode($input);
    }

    /**
     * Encodes a string to be a valid application/x-www-form-urlencoded
     * string.
     *
     * @see https://url.spec.whatwg.org/#concept-urlencoded-byte-serializer
     *
     * @param string $input
     *
     * @return string
     */
    private function urlencode($input)
    {
        $output = '';
        $length = strlen($input);

        for ($i = 0; $i < $length; ++$i) {
            if ($input[$i] === "\x20") {
                $output .= '+';
            } elseif ($input[$i] === "\x2A"
                || $input[$i] === "\x2D"
                || $input[$i] === "\x2E"
                || ($input[$i] >= "\x30" && $input[$i] <= "\x39")
                || ($input[$i] >= "\x41" && $input[$i] <= "\x5A")
                || $input[$i] === "\x5F"
                || ($input[$i] >= "\x61" && $input[$i] <= "\x7A")
            ) {
                $output .= $input[$i];
            } else {
                $output .= rawurlencode($input[$i]);
            }
        }

        return $output;
    }

    /**
     * Encodes a list of tuples as a valid application/x-www-form-urlencoded
     * string.
     *
     * @see https://url.spec.whatwg.org/#concept-urlencoded-serializer
     *
     * @param array<int, array<string, string>> $tuples
     * @param ?string                      $encodingOverride (optional)
     *
     * @return string
     */
    private function urlencodeList(array $tuples, $encodingOverride = null)
    {
        $encoding = 'UTF-8';

        if ($encodingOverride !== null) {
            $encoding = $encodingOverride;
        }

        $output = '';

        foreach ($tuples as $key => $tuple) {
            $name = $this->urlencode($tuple['name']);
            $value = $tuple['value'];

            // TODO: If $value is a file, then set $value to $value's filename.
            // The HTML standard invokes this algorithm with values that are
            // files, however, this is unused in the URL standard.

            $value = $this->urlencode($value);

            if ($key > 0) {
                $output .= '&';
            }

            $output .= $name . '=' . $value;
        }

        return $output;
    }
}
