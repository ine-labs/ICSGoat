<?php
namespace Rowbot\URL;

use const IDNA_CHECK_BIDI;
use const IDNA_CHECK_CONTEXTJ;
use const IDNA_ERROR_BIDI;
use const IDNA_ERROR_CONTEXTJ;
use const IDNA_ERROR_DISALLOWED;
use const IDNA_ERROR_DOMAIN_NAME_TOO_LONG;
use const IDNA_ERROR_EMPTY_LABEL;
use const IDNA_ERROR_HYPHEN_3_4;
use const IDNA_ERROR_INVALID_ACE_LABEL;
use const IDNA_ERROR_LABEL_HAS_DOT;
use const IDNA_ERROR_LABEL_TOO_LONG;
use const IDNA_ERROR_LEADING_COMBINING_MARK;
use const IDNA_ERROR_LEADING_HYPHEN;
use const IDNA_ERROR_PUNYCODE;
use const IDNA_ERROR_TRAILING_HYPHEN;
use const INTL_IDNA_VARIANT_UTS46;
use const IDNA_NONTRANSITIONAL_TO_ASCII;
use const IDNA_NONTRANSITIONAL_TO_UNICODE;
use const IDNA_USE_STD3_RULES;

use function idn_to_ascii;
use function idn_to_utf8;

final class IDN
{
    const CHECK_HYPHENS              = 1;
    const CHECK_BIDI                 = 2;
    const CHECK_JOINERS              = 4;
    const USE_STD3_ASCII_RULES       = 8;
    const VERIFY_DNS_LENGTH          = 16;
    const NONTRANSITIONAL_PROCESSING = 32;

    /**
     * @var self
     */
    private static $instance;

    /**
     * @see https://secure.php.net/manual/en/intl.constants.php
     *
     * @var int
     */
    private static $errors = IDNA_ERROR_EMPTY_LABEL
        | IDNA_ERROR_LABEL_TOO_LONG
        | IDNA_ERROR_DOMAIN_NAME_TOO_LONG
        | IDNA_ERROR_LEADING_HYPHEN
        | IDNA_ERROR_TRAILING_HYPHEN
        | IDNA_ERROR_HYPHEN_3_4
        | IDNA_ERROR_LEADING_COMBINING_MARK
        | IDNA_ERROR_DISALLOWED
        | IDNA_ERROR_PUNYCODE
        | IDNA_ERROR_LABEL_HAS_DOT
        | IDNA_ERROR_INVALID_ACE_LABEL
        | IDNA_ERROR_BIDI
        | IDNA_ERROR_CONTEXTJ;

    /**
     * Constructor.
     *
     * @return void
     */
    private function __construct()
    {
    }

    /**
     * Gets the instance.
     *
     * @return self
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * The domain name to be converted to ASCII.
     *
     * @param string $domainName A UTF-8 string.
     * @param int    $flags      A bitmask of flags.
     *
     * @return string|false
     */
    public function toASCII($domainName, $flags = 0)
    {
        $options = $this->options($flags);

        if (($flags & self::NONTRANSITIONAL_PROCESSING) !== 0) {
            $options |= IDNA_NONTRANSITIONAL_TO_ASCII;
        }

        $result = idn_to_ascii(
            $domainName,
            $options,
            INTL_IDNA_VARIANT_UTS46,
            $info
        );
        $whitelistedErrors = 0;

        // We died a horrible death and can't recover. There is currently a bug
        // in PHP's idn_to_* functions where this can occur when the given
        // domain exceeds 254 bytes.
        if (empty($info)) {
            return false;
        }

        // There is currently no way to disable the check on the domain's
        // length. So, whitelist some errors to check against. Normally, a
        // domain name is restricted to between 1 and 253 bytes. This excludes
        // the root domain and its '.' delimiter.
        if (($flags & self::VERIFY_DNS_LENGTH) === 0) {
            // If the domain name is the empty string, simply return the empty
            // string as no other rules can possibly apply to it.
            if ($domainName === '') {
                return $domainName;
            }

            $whitelistedErrors |= IDNA_ERROR_LABEL_TOO_LONG
                | IDNA_ERROR_DOMAIN_NAME_TOO_LONG
                | IDNA_ERROR_EMPTY_LABEL;
        }

        $whitelistedErrors |= $this->maybeWhitelistHyphenErrors($flags);

        if ($result === false
            && ((self::$errors & ~$whitelistedErrors) & $info['errors']) !== 0
        ) {
            return false;
        }

        return $info['result'];
    }

    /**
     * The domain name to be converted to UTF-8.
     *
     * @param string $domainName An ASCII string.
     * @param int    $flags      A bitmask of flags.
     *
     * @return string|false
     */
    public function toUnicode($domainName, $flags = 0)
    {
        $options = $this->options($flags);

        if (($flags & self::NONTRANSITIONAL_PROCESSING) !== 0) {
            $options |= IDNA_NONTRANSITIONAL_TO_UNICODE;
        }

        $result = idn_to_utf8(
            $domainName,
            $options,
            INTL_IDNA_VARIANT_UTS46,
            $info
        );

        // We died a horrible death and can't recover. There is currently a bug
        // in PHP's idn_to_* functions where this can occur when the given
        // domain exceeds 254 bytes.
        if (empty($info)) {
            return false;
        }

        $whitelistedErrors = $this->maybeWhitelistHyphenErrors($flags);

        if ($result === false
            && ((self::$errors & ~$whitelistedErrors) & $info['errors']) !== 0
        ) {
            return false;
        }

        return $info['result'];
    }

    /**
     * Translates the wrapper object option flags to the equivilant IDNA_*
     * option flags.
     *
     * @param int $flags A bitmask of flags.
     *
     * @return int
     */
    private function options($flags)
    {
        $options = 0;

        if (($flags & self::CHECK_BIDI) !== 0) {
            $options |= IDNA_CHECK_BIDI;
        }

        if (($flags & self::CHECK_JOINERS) !== 0) {
            $options |= IDNA_CHECK_CONTEXTJ;
        }

        if (($flags & self::USE_STD3_ASCII_RULES) !== 0) {
            $options |= IDNA_USE_STD3_RULES;
        }

        return $options;
    }

    /**
     * Checks to see if the user wants to validate hyphens and if not, adds the
     * appropriate errors to a whitelist.
     *
     * @param int $flags
     *
     * @return int
     */
    private function maybeWhitelistHyphenErrors($flags)
    {
        // There is currently no way to disable the check for hyphens in the
        // 3rd and 4th spots in a domain, however, we can look for the
        // error and add it to a whitelist of errors to check against.
        if (($flags & self::CHECK_HYPHENS) !== 0) {
            return 0;
        }

        return IDNA_ERROR_HYPHEN_3_4
            | IDNA_ERROR_LEADING_HYPHEN
            | IDNA_ERROR_TRAILING_HYPHEN;
    }
}
