<?php
namespace Rowbot\URL;

use Rowbot\URL\Exception\InvalidParserState;

use function array_pop;
use function array_shift;
use function count;
use function ctype_digit;
use function ctype_xdigit;
use function intval;
use function mb_convert_encoding;
use function mb_strlen;
use function mb_substr;
use function pow;
use function preg_match;
use function preg_replace;
use function rawurlencode;
use function strlen;
use function strtolower;

class BasicURLParser
{
    const SCHEME_START_STATE                     = 1;
    const SCHEME_STATE                           = 2;
    const NO_SCHEME_STATE                        = 3;
    const SPECIAL_RELATIVE_OR_AUTHORITY_STATE    = 4;
    const PATH_OR_AUTHORITY_STATE                = 5;
    const RELATIVE_STATE                         = 6;
    const RELATIVE_SLASH_STATE                   = 7;
    const SPECIAL_AUTHORITY_SLASHES_STATE        = 8;
    const SPECIAL_AUTHORITY_IGNORE_SLASHES_STATE = 9;
    const AUTHORITY_STATE                        = 10;
    const HOST_STATE                             = 11;
    const HOSTNAME_STATE                         = 12;
    const PORT_STATE                             = 13;
    const FILE_STATE                             = 14;
    const FILE_SLASH_STATE                       = 15;
    const FILE_HOST_STATE                        = 16;
    const PATH_START_STATE                       = 17;
    const PATH_STATE                             = 18;
    const CANNOT_BE_A_BASE_URL_PATH_STATE        = 19;
    const QUERY_STATE                            = 20;
    const FRAGMENT_STATE                         = 21;

    const RETURN_OK        = 0;
    const RETURN_CONTINUE  = 1;
    const RETURN_FAILURE   = 2;
    const RETURN_TERMINATE = 3;

    /**
     * @see https://url.spec.whatwg.org/#single-dot-path-segment
     *
     * @var array<string, string>
     */
    private static $singleDotPathSegment = [
        '.'   => '',
        '%2e' => '',
        '%2E' => ''
    ];

    /**
     * @see https://url.spec.whatwg.org/#double-dot-path-segment
     *
     * @var array<string, string>
     */
    private static $doubleDotPathSegment = [
        '..'     => '',
        '.%2e'   => '',
        '.%2E'   => '',
        '%2e.'   => '',
        '%2E.'   => '',
        '%2e%2e' => '',
        '%2E%2E' => ''
    ];

    /**
     * @var bool
     */
    private $atFlag;

    /**
     * @var \Rowbot\URL\URLRecord|null
     */
    private $base;

    /**
     * @var bool
     */
    private $bracketFlag;

    /**
     * @var string
     */
    private $buffer;

    /**
     * @var string
     */
    private $encoding;

    /**
     * @var string|null
     */
    private $encodingOverride;

    /**
     * @var string
     */
    private $input;

    /**
     * @var bool
     */
    private $passwordTokenSeenFlag;

    /**
     * @var int
     */
    private $pointer;

    /**
     * @var int
     */
    private $state;

    /**
     * @var int|null
     */
    private $stateOverride;

    /**
     * @var \Rowbot\URL\URLRecord
     */
    private $url;

    /**
     * Constructor.
     *
     * @return void
     */
    private function __construct()
    {
    }

    /**
     * Parses a string as a URL. The string can be an absolute URL or a relative
     * URL. If a relative URL is given, a base URL must also be given so that a
     * complete URL can be resolved.  It can also parse individual parts of a
     * URL when the state machine starts in a specific state.
     *
     * @see https://url.spec.whatwg.org/#concept-basic-url-parser
     *
     * @param string $input                                 The URL string that is to be parsed.
     *
     * @param \Rowbot\URL\URLRecord|null $base              (optional) This represents the base URL, which in most
     *                                                      cases, is the document's URL, it may also be a node's base
     *                                                      URI or whatever base URL you wish to resolve relative URLs
     *                                                      against. Default is null.
     *
     * @param string|null                 $encodingOverride (optional) Overrides the default encoding, which is UTF-8.
     *
     *
     * @param \Rowbot\URL\URLRecord|null  $url              (optional) This represents an existing URL record object
     *                                                      that should be modified based on the input URL and optional
     *                                                      base URL. Default is null.
     *
     * @param int|null                    $stateOverride    (optional) An integer that determines what state the state
     *                                                      machine will begin parsing the input URL from. Suppling a
     *                                                      value for this parameter will override the default state of
     *                                                      SCHEME_START_STATE. Default is null.
     *
     * @return \Rowbot\URL\URLRecord|false Returns a URL object upon successfully parsing the input or false if parsing
     *                                     input failed.
     */
    public static function parseBasicUrl(
        $input,
        URLRecord $base = null,
        $encodingOverride = null,
        URLRecord $url = null,
        $stateOverride = null
    ) {
        $parser = new self();
        $parser->input = $input;
        $parser->base = $base;
        $parser->encodingOverride = $encodingOverride;
        $parser->stateOverride = $stateOverride;
        $parser->url = $url ?: new URLRecord();

        if ($url === null) {
            // Remove any leading or trailing C0 control and space characters.
            $parser->input = preg_replace(
                '/^[\x00-\x1F\x20]+|[\x00-\x1F\x20]+$/u',
                '',
                $parser->input,
                -1,
                $count
            );

            // A URL should not contain any leading C0 control and space
            // characters.
            if ($count > 0) {
                // Validation error.
            }
        }

        // A URL should not contain any tab or newline characters.
        $parser->input = preg_replace(
            '/[\x09\x0A\x0D]+/u',
            '',
            $parser->input,
            -1,
            $count
        );

        if ($count > 0) {
            // Validation error
        }

        $parser->state = $parser->stateOverride ?: self::SCHEME_START_STATE;
        $parser->encoding = 'UTF-8';

        // TODO: If encoding override is given, set it to the result of the
        // getting an output encoding algorithm.
        if ($parser->encodingOverride !== null) {
            $parser->encoding = $parser->encodingOverride;
        }

        $parser->buffer = '';
        $parser->atFlag = false;
        $parser->bracketFlag = false;
        $parser->passwordTokenSeenFlag = false;
        $parser->pointer = 0;
        $len = mb_strlen($parser->input, $parser->encoding);

        while (true) {
            $c = mb_substr(
                $parser->input,
                $parser->pointer,
                1,
                $parser->encoding
            );

            switch ($parser->state) {
                case self::SCHEME_START_STATE:
                    $retVal = $parser->schemeStartState($c);

                    break;

                case self::SCHEME_STATE:
                    $retVal = $parser->schemeState($c);

                    break;

                case self::NO_SCHEME_STATE:
                    $retVal = $parser->noSchemeState($c);

                    break;

                case self::SPECIAL_RELATIVE_OR_AUTHORITY_STATE:
                    $retVal = $parser->specialRelativeOrAuthorityState($c);

                    break;

                case self::PATH_OR_AUTHORITY_STATE:
                    $retVal = $parser->pathOrAuthorityState($c);

                    break;

                case self::RELATIVE_STATE:
                    $retVal = $parser->relativeState($c);

                    break;

                case self::RELATIVE_SLASH_STATE:
                    $retVal = $parser->relativeSlashState($c);

                    break;

                case self::SPECIAL_AUTHORITY_SLASHES_STATE:
                    $retVal = $parser->specialAuthoritySlashesState($c);

                    break;

                case self::SPECIAL_AUTHORITY_IGNORE_SLASHES_STATE:
                    $retVal = $parser->specialAuthorityIgnnoreSlashesState($c);

                    break;

                case self::AUTHORITY_STATE:
                    $retVal = $parser->authorityState($c);

                    break;

                case self::HOST_STATE:
                case self::HOSTNAME_STATE:
                    $retVal = $parser->hostState($c);

                    break;

                case self::PORT_STATE:
                    $retVal = $parser->portState($c);

                    break;

                case self::FILE_STATE:
                    $retVal = $parser->fileState($c);

                    break;

                case self::FILE_SLASH_STATE:
                    $retVal = $parser->fileSlashState($c);

                    break;

                case self::FILE_HOST_STATE:
                    $retVal = $parser->fileHostState($c);

                    break;

                case self::PATH_START_STATE:
                    $retVal = $parser->pathStartState($c);

                    break;

                case self::PATH_STATE:
                    $retVal = $parser->pathState($c);

                    break;

                case self::CANNOT_BE_A_BASE_URL_PATH_STATE:
                    $retVal = $parser->cannotBeABaseUrlPathState($c);

                    break;

                case self::QUERY_STATE:
                    $retVal = $parser->queryState($c);

                    break;

                case self::FRAGMENT_STATE:
                    $retVal = $parser->fragmentState($c);

                    break;

                default:
                    // This should never happen and indicates an error on my
                    // part as we should be passing in one of the valid states
                    // defined above.
                    throw new InvalidParserState(
                        "Invalid parser state ({$parser->state})."
                    );
            }

            if ($retVal === self::RETURN_FAILURE) {
                return false;
            }

            if ($retVal === self::RETURN_CONTINUE) {
                continue;
            }

            if ($retVal === self::RETURN_TERMINATE
                || $parser->pointer >= $len
            ) {
                break;
            }

            ++$parser->pointer;
        }

        return $parser->url;
    }

    /**
     * @see https://url.spec.whatwg.org/#scheme-start-state
     *
     * @param string $c
     *
     * @return int
     */
    private function schemeStartState($c)
    {
        if (preg_match(URLUtils::REGEX_ASCII_ALPHA, $c) === 1) {
            $this->buffer .= strtolower($c);
            $this->state = self::SCHEME_STATE;

            return self::RETURN_OK;
        }

        if ($this->stateOverride === null) {
            $this->state = self::NO_SCHEME_STATE;
            --$this->pointer;

            return self::RETURN_OK;
        }

        // Validation error.
        // Note: This indication of failture is used exclusively
        // by the Location object's protocol attribute.
        return self::RETURN_FAILURE;
    }

    /**
     * @see https://url.spec.whatwg.org/#scheme-state
     *
     * @param string $c
     *
     * @return int
     */
    private function schemeState($c)
    {
        if (preg_match(URLUtils::REGEX_ASCII_ALPHANUMERIC, $c) === 1
            || $c === '+'
            || $c === '-'
            || $c === '.'
        ) {
            $this->buffer .= strtolower($c);

            return self::RETURN_OK;
        } elseif ($c === ':') {
            if ($this->stateOverride !== null) {
                $bufferIsSpecialScheme = isset(
                    URLUtils::$specialSchemes[$this->buffer]
                );
                $urlIsSpecial = $this->url->isSpecial();

                if ($urlIsSpecial && !$bufferIsSpecialScheme) {
                    return self::RETURN_TERMINATE;
                }

                if (!$urlIsSpecial && $bufferIsSpecialScheme) {
                    return self::RETURN_TERMINATE;
                }

                if ($this->url->includesCredentials()
                    || ($this->url->port !== null && $this->buffer === 'file')
                ) {
                    return self::RETURN_TERMINATE;
                }

                if ($this->url->scheme === 'file'
                    && ($this->url->host->isEmpty()
                        || $this->url->host->isNull())
                ) {
                    return self::RETURN_TERMINATE;
                }
            }

            $this->url->scheme = $this->buffer;

            if ($this->stateOverride !== null) {
                if ($bufferIsSpecialScheme && URLUtils::$specialSchemes[
                    $this->url->scheme
                ] === $this->url->port) {
                    $this->url->port = null;
                }

                return self::RETURN_TERMINATE;
            }

            $this->buffer = '';
            $urlIsSpecial = $this->url->isSpecial();

            if ($this->url->scheme === 'file') {
                if (mb_substr(
                    $this->input,
                    $this->pointer + 1,
                    2,
                    $this->encoding
                ) !== '//') {
                    // Validation error
                }

                $this->state = self::FILE_STATE;
            } elseif ($urlIsSpecial
                && $this->base !== null
                && $this->base->scheme === $this->url->scheme
            ) {
                // This means that base's cannot-be-a-base-URL flag
                // is unset.
                $this->state = self::SPECIAL_RELATIVE_OR_AUTHORITY_STATE;
            } elseif ($urlIsSpecial) {
                $this->state = self::SPECIAL_AUTHORITY_SLASHES_STATE;
            } elseif (mb_substr(
                $this->input,
                $this->pointer + 1,
                1,
                $this->encoding
            ) === '/') {
                $this->state = self::PATH_OR_AUTHORITY_STATE;
                ++$this->pointer;
            } else {
                $this->url->cannotBeABaseUrl = true;
                $this->url->path[] = '';
                $this->state = self::CANNOT_BE_A_BASE_URL_PATH_STATE;
            }

            return self::RETURN_OK;
        } elseif ($this->stateOverride === null) {
            $this->buffer = '';
            $this->state = self::NO_SCHEME_STATE;

            // Reset the pointer to poing at the first code point.
            $this->pointer = 0;

            return self::RETURN_CONTINUE;
        }

        // Validation error.
        // Note: This indication of failure is used exclusively
        // by the Location object's protocol attribute.
        // Furthermore, the non-failure termination earlier in
        // this state is an intentional difference for defining
        // that attribute.
        return self::RETURN_FAILURE;
    }

    /**
     * @see https://url.spec.whatwg.org/#no-scheme-state
     *
     * @param string $c
     *
     * @return int
     */
    private function noSchemeState($c)
    {
        if ($this->base === null
            || ($this->base->cannotBeABaseUrl && $c !== '#')
        ) {
            // Validation error. Return failure.
            return self::RETURN_FAILURE;
        }

        if ($this->base->cannotBeABaseUrl && $c === '#') {
            $this->url->scheme = $this->base->scheme;
            $this->url->path = $this->base->path;
            $this->url->query = $this->base->query;
            $this->url->fragment = '';
            $this->url->cannotBeABaseUrl = true;
            $this->state = self::FRAGMENT_STATE;

            return self::RETURN_OK;
        }

        if ($this->base->scheme !== 'file') {
            $this->state = self::RELATIVE_STATE;
            --$this->pointer;

            return self::RETURN_OK;
        }

        $this->state = self::FILE_STATE;
        --$this->pointer;

        return self::RETURN_OK;
    }

    /**
     * @see https://url.spec.whatwg.org/#special-relative-or-authority-state
     *
     * @param string $c
     *
     * @return int
     */
    private function specialRelativeOrAuthorityState($c)
    {
        if ($c === '/' && mb_substr(
            $this->input,
            $this->pointer + 1,
            1,
            $this->encoding
        ) === '/') {
            $this->state = self::SPECIAL_AUTHORITY_IGNORE_SLASHES_STATE;
            ++$this->pointer;

            return self::RETURN_OK;
        }

        // Validation error.
        $this->state = self::RELATIVE_STATE;
        --$this->pointer;

        return self::RETURN_OK;
    }

    /**
     * @see https://url.spec.whatwg.org/#path-or-authority-state
     *
     * @param string $c
     *
     * @return int
     */
    private function pathOrAuthorityState($c)
    {
        if ($c === '/') {
            $this->state = self::AUTHORITY_STATE;

            return self::RETURN_OK;
        }

        $this->state = self::PATH_STATE;
        --$this->pointer;

        return self::RETURN_OK;
    }

    /**
     * @see https://url.spec.whatwg.org/#relative-state
     *
     * @param string $c
     *
     * @return int
     */
    private function relativeState($c)
    {
        $this->url->scheme = $this->base->scheme;

        if ($c === ''/* EOF */) {
            $this->url->username = $this->base->username;
            $this->url->password = $this->base->password;
            $this->url->host = clone $this->base->host;
            $this->url->port = $this->base->port;
            $this->url->path = $this->base->path;
            $this->url->query = $this->base->query;

            return self::RETURN_OK;
        }

        if ($c === '/') {
            $this->state = self::RELATIVE_SLASH_STATE;

            return self::RETURN_OK;
        }

        if ($c === '?') {
            $this->url->username = $this->base->username;
            $this->url->password = $this->base->password;
            $this->url->host = clone $this->base->host;
            $this->url->port = $this->base->port;
            $this->url->path = $this->base->path;
            $this->url->query = '';
            $this->state = self::QUERY_STATE;

            return self::RETURN_OK;
        }

        if ($c === '#') {
            $this->url->username = $this->base->username;
            $this->url->password = $this->base->password;
            $this->url->host = clone $this->base->host;
            $this->url->port = $this->base->port;
            $this->url->path = $this->base->path;
            $this->url->query = $this->base->query;
            $this->url->fragment = '';
            $this->state = self::FRAGMENT_STATE;

            return self::RETURN_OK;
        }

        if ($this->url->isSpecial() && $c === '\\') {
            // Validation error
            $this->state = self::RELATIVE_SLASH_STATE;

            return self::RETURN_OK;
        }

        $this->url->username = $this->base->username;
        $this->url->password = $this->base->password;
        $this->url->host = clone $this->base->host;
        $this->url->port = $this->base->port;
        $this->url->path = $this->base->path;

        if (!empty($this->url->path)) {
            array_pop($this->url->path);
        }

        $this->state = self::PATH_STATE;
        --$this->pointer;

        return self::RETURN_OK;
    }

    /**
     * @see https://url.spec.whatwg.org/#relative-slash-state
     *
     * @param string $c
     *
     * @return int
     */
    private function relativeSlashState($c)
    {
        if ($this->url->isSpecial() && $c === '/' || $c === '\\') {
            if ($c === '\\') {
                // Validation error.
            }

            $this->state = self::SPECIAL_AUTHORITY_IGNORE_SLASHES_STATE;

            return self::RETURN_OK;
        }

        if ($c === '/') {
            $this->state = self::AUTHORITY_STATE;

            return self::RETURN_OK;
        }

        $this->url->username = $this->base->username;
        $this->url->password = $this->base->password;
        $this->url->host = clone $this->base->host;
        $this->url->port = $this->base->port;
        $this->state = self::PATH_STATE;
        --$this->pointer;

        return self::RETURN_OK;
    }

    /**
     * @see https://url.spec.whatwg.org/#special-authority-slashes-state
     *
     * @param string $c
     *
     * @return int
     */
    private function specialAuthoritySlashesState($c)
    {
        if ($c === '/' && mb_substr(
            $this->input,
            $this->pointer + 1,
            1,
            $this->encoding
        ) === '/') {
            $this->state = self::SPECIAL_AUTHORITY_IGNORE_SLASHES_STATE;
            ++$this->pointer;

            return self::RETURN_OK;
        }

        // Validation error.
        $this->state = self::SPECIAL_AUTHORITY_IGNORE_SLASHES_STATE;
        --$this->pointer;

        return self::RETURN_OK;
    }

    /**
     * @see https://url.spec.whatwg.org/#special-authority-ignore-slashes-state
     *
     * @param string $c
     *
     * @return int
     */
    private function specialAuthorityIgnnoreSlashesState($c)
    {
        if ($c !== '/' && $c !== '\\') {
            $this->state = self::AUTHORITY_STATE;
            --$this->pointer;

            return self::RETURN_OK;
        }

        // Validation error
        return self::RETURN_OK;
    }

    /**
     * @see https://url.spec.whatwg.org/#authority-state
     *
     * @param string $c
     *
     * @return int
     */
    private function authorityState($c)
    {
        if ($c === '@') {
            // Validation error.

            if ($this->atFlag) {
                $this->buffer = '%40' . $this->buffer;
            }

            $this->atFlag = true;
            $length = mb_strlen($this->buffer, $this->encoding);

            for ($i = 0; $i < $length; $i++) {
                $codePoint = mb_substr($this->buffer, $i, 1, $this->encoding);

                if ($codePoint === ':' && !$this->passwordTokenSeenFlag) {
                    $this->passwordTokenSeenFlag = true;
                    continue;
                }

                $encodedCodePoints = URLUtils::utf8PercentEncode(
                    $codePoint,
                    URLUtils::USERINFO_PERCENT_ENCODE_SET
                );

                if ($this->passwordTokenSeenFlag) {
                    $this->url->password .= $encodedCodePoints;
                } else {
                    $this->url->username .= $encodedCodePoints;
                }
            }

            $this->buffer = '';

            return self::RETURN_OK;
        }

        if (($c === ''/* EOF */
            || $c === '/'
            || $c === '?'
            || $c === '#')
            || ($this->url->isSpecial() && $c === '\\')
        ) {
            if ($this->atFlag && $this->buffer === '') {
                // Validation error.
                return self::RETURN_FAILURE;
            }

            $this->pointer -= mb_strlen($this->buffer, $this->encoding) + 1;
            $this->buffer = '';
            $this->state = self::HOST_STATE;

            return self::RETURN_OK;
        }

        $this->buffer .= $c;

        return self::RETURN_OK;
    }

    /**
     * @see https://url.spec.whatwg.org/#host-state
     *
     * @param string $c
     *
     * @return int
     */
    private function hostState($c)
    {
        if ($this->stateOverride !== null && $this->url->scheme === 'file') {
            --$this->pointer;
            $this->state = self::FILE_HOST_STATE;

            return self::RETURN_OK;
        }

        if ($c === ':' && !$this->bracketFlag) {
            if ($this->buffer === '') {
                // Validation error. Return failure.
                return self::RETURN_FAILURE;
            }

            $host = Host::parse($this->buffer, !$this->url->isSpecial());

            if ($host === false) {
                // Return failure.
                return self::RETURN_FAILURE;
            }

            $this->url->host = $host;
            $this->buffer = '';
            $this->state = self::PORT_STATE;

            if ($this->stateOverride === self::HOSTNAME_STATE) {
                return self::RETURN_TERMINATE;
            }

            return self::RETURN_OK;
        }

        if (($c === ''/* EOF */ || $c === '/' || $c === '?' || $c === '#')
            || ($this->url->isSpecial() && $c === '\\')
        ) {
            --$this->pointer;

            if ($this->url->isSpecial() && $this->buffer === '') {
                // Validation error. Return failure.
                return self::RETURN_FAILURE;
            } elseif ($this->stateOverride !== null
                && $this->buffer === ''
                && ($this->url->includesCredentials()
                    || $this->url->port !== null)
            ) {
                // Validation error.
                return self::RETURN_TERMINATE;
            }

            $host = Host::parse($this->buffer, !$this->url->isSpecial());

            if ($host === false) {
                // Return failure.
                return self::RETURN_FAILURE;
            }

            $this->url->host = $host;
            $this->buffer = '';
            $this->state = self::PATH_START_STATE;

            if ($this->stateOverride !== null) {
                return self::RETURN_TERMINATE;
            }

            return self::RETURN_OK;
        }

        if ($c === '[') {
            $this->bracketFlag = true;
        } elseif ($c === ']') {
            $this->bracketFlag = false;
        }

        $this->buffer .= $c;

        return self::RETURN_OK;
    }

    /**
     * @see https://url.spec.whatwg.org/#port-state
     *
     * @param string $c
     *
     * @return int
     */
    private function portState($c)
    {
        if (ctype_digit($c)) {
            $this->buffer .= $c;

            return self::RETURN_OK;
        } elseif (($c === ''/* EOF */ || $c === '/' || $c === '?' || $c === '#')
            || ($this->url->isSpecial() && $c === '\\')
            || $this->stateOverride !== null
        ) {
            if ($this->buffer !== '') {
                $port = intval($this->buffer, 10);

                if ($port > pow(2, 16) - 1) {
                    // Validation error. Return failure.
                    return self::RETURN_FAILURE;
                }

                if (isset(URLUtils::$specialSchemes[$this->url->scheme])
                    && URLUtils::$specialSchemes[$this->url->scheme] === $port
                ) {
                    $this->url->port = null;
                } else {
                    $this->url->port = $port;
                }

                $this->buffer = '';
            }

            if ($this->stateOverride !== null) {
                return self::RETURN_TERMINATE;
            }

            $this->state = self::PATH_START_STATE;
            --$this->pointer;

            return self::RETURN_OK;
        }

        // Validation error. Return failure.
        return self::RETURN_FAILURE;
    }

    /**
     * @see https://url.spec.whatwg.org/#file-state
     *
     * @param string $c
     *
     * @return int
     */
    private function fileState($c)
    {
        $this->url->scheme = 'file';

        if ($c === '/' || $c === '\\') {
            if ($c === '\\') {
                // Validation error
            }

            $this->state = self::FILE_SLASH_STATE;

            return self::RETURN_OK;
        } elseif ($this->base !== null && $this->base->scheme === 'file') {
            if ($c === ''/* EOF */) {
                $this->url->host = clone $this->base->host;
                $this->url->path = $this->base->path;
                $this->url->query = $this->base->query;

                return self::RETURN_OK;
            } elseif ($c === '?') {
                $this->url->host = clone $this->base->host;
                $this->url->path = $this->base->path;
                $this->url->query = '';
                $this->state = self::QUERY_STATE;

                return self::RETURN_OK;
            } elseif ($c === '#') {
                $this->url->host = clone $this->base->host;
                $this->url->path = $this->base->path;
                $this->url->query = $this->base->query;
                $this->url->fragment = '';
                $this->state = self::FRAGMENT_STATE;

                return self::RETURN_OK;
            }

            // This is a (platform-independent) Windows drive
            // letter quirk.
            if (preg_match(
                URLUtils::STARTS_WITH_WINDOWS_DRIVE_LETTER,
                mb_substr(
                    $this->input,
                    $this->pointer,
                    null,
                    $this->encoding
                )
            ) !== 1) {
                $this->url->host = clone $this->base->host;
                $this->url->path = $this->base->path;
                $this->url->shortenPath();
            } else {
                // Validation error.
            }

            $this->state = self::PATH_STATE;
            --$this->pointer;

            return self::RETURN_OK;
        }

        $this->state = self::PATH_STATE;
        --$this->pointer;

        return self::RETURN_OK;
    }

    /**
     * @see https://url.spec.whatwg.org/#file-slash-state
     *
     * @param string $c
     *
     * @return int
     */
    private function fileSlashState($c)
    {
        if ($c === '/' || $c === '\\') {
            if ($c === '\\') {
                // Validation error
            }

            $this->state = self::FILE_HOST_STATE;

            return self::RETURN_OK;
        }

        if ($this->base !== null
            && $this->base->scheme === 'file'
            && preg_match(
                URLUtils::STARTS_WITH_WINDOWS_DRIVE_LETTER,
                mb_substr(
                    $this->input,
                    $this->pointer,
                    null,
                    $this->encoding
                )
            ) !== 1
        ) {
            if (preg_match(
                URLUtils::REGEX_NORMALIZED_WINDOWS_DRIVE_LETTER,
                $this->base->path[0]
            ) === 1) {
                // This is a (platform-independent) Windows
                // drive letter quirk. Both url’s and base’s
                // host are null under these conditions and
                // therefore not copied.
                $this->url->path[] = $this->base->path[0];
            } else {
                $this->url->host = clone $this->base->host;
            }
        }

        $this->state = self::PATH_STATE;
        --$this->pointer;

        return self::RETURN_OK;
    }

    /**
     * @see https://url.spec.whatwg.org/#file-host-state
     *
     * @param string $c
     *
     * @return int
     */
    private function fileHostState($c)
    {
        if ($c === ''/* EOF */
            || $c === '/'
            || $c === '\\'
            || $c === '?'
            || $c === '#'
        ) {
            --$this->pointer;

            if ($this->stateOverride === null
                && preg_match(
                    URLUtils::REGEX_WINDOWS_DRIVE_LETTER,
                    $this->buffer
                ) === 1
            ) {
                // Validation error
                $this->state = self::PATH_STATE;

                return self::RETURN_OK;

                // This is a (platform-independent) Windows drive
                // letter quirk. $buffer is not reset here and
                // instead used in the path state.
            } elseif ($this->buffer === '') {
                $this->url->host->setHost('');

                if ($this->stateOverride !== null) {
                    return self::RETURN_TERMINATE;
                }

                $this->state = self::PATH_START_STATE;

                return self::RETURN_OK;
            }

            $host = Host::parse($this->buffer, !$this->url->isSpecial());

            if ($host === false) {
                // Return failure.
                return self::RETURN_FAILURE;
            }

            if ($host->equals('localhost')) {
                $host->setHost('');
            }

            $this->url->host = $host;

            if ($this->stateOverride !== null) {
                return self::RETURN_TERMINATE;
            }

            $this->buffer = '';
            $this->state = self::PATH_START_STATE;

            return self::RETURN_OK;
        }

        $this->buffer .= $c;

        return self::RETURN_OK;
    }

    /**
     * @see https://url.spec.whatwg.org/#path-start-state
     *
     * @param string $c
     *
     * @return int
     */
    private function pathStartState($c)
    {
        if ($this->url->isSpecial()) {
            if ($c === '\\') {
                // Validation error.
            }

            $this->state = self::PATH_STATE;

            if ($c !== '/' && $c !== '\\') {
                --$this->pointer;
            }
        } elseif ($this->stateOverride === null && $c === '?') {
            $this->url->query = '';
            $this->state = self::QUERY_STATE;
        } elseif ($this->stateOverride === null && $c === '#') {
            $this->url->fragment = '';
            $this->state = self::FRAGMENT_STATE;
        } elseif ($c !== '') {
            $this->state = self::PATH_STATE;

            if ($c !== '/') {
                --$this->pointer;
            }
        }

        return self::RETURN_OK;
    }

    /**
     * @see https://url.spec.whatwg.org/#path-state
     *
     * @param string $c
     *
     * @return int
     */
    private function pathState($c)
    {
        if ($c === ''/* EOF */
            || $c === '/'
            || ($this->url->isSpecial() && $c === '\\')
            || ($this->stateOverride === null && ($c === '?' || $c === '#'))
        ) {
            $urlIsSpecial = $this->url->isSpecial();

            if ($urlIsSpecial && $c === '\\') {
                // Validation error.
            }

            if (isset(self::$doubleDotPathSegment[$this->buffer])) {
                $this->url->shortenPath();

                if ($c !== '/' && !($urlIsSpecial && $c === '\\')) {
                    $this->url->path[] = '';
                }
            } elseif (isset(self::$singleDotPathSegment[$this->buffer])
                && $c !== '/'
                && !($this->url->isSpecial() && $c === '\\')
            ) {
                $this->url->path[] = '';
            } elseif (!isset(
                self::$singleDotPathSegment[$this->buffer]
            )) {
                if ($this->url->scheme === 'file'
                    && empty($this->url->path)
                    && preg_match(
                        URLUtils::REGEX_WINDOWS_DRIVE_LETTER,
                        $this->buffer
                    ) === 1
                ) {
                    if (!$this->url->host->isEmpty()
                        && !$this->url->host->isNull()
                    ) {
                        // Validation error.
                        $this->url->host->setHost('');
                    }

                    // This is a (platform-independent) Windows
                    // drive letter quirk.
                    $this->buffer = mb_substr(
                        $this->buffer,
                        0,
                        1,
                        $this->encoding
                    ) . ':'
                    . mb_substr($this->buffer, 2, null, $this->encoding);
                }

                $this->url->path[] = $this->buffer;
            }

            $this->buffer = '';

            if ($this->url->scheme === 'file'
                && ($c === '' || $c === '?' || $c === '#')
            ) {
                $size = count($this->url->path);

                while ($size-- > 1 && $this->url->path[0] === '') {
                    // Validation error.
                    array_shift($this->url->path);
                }
            }

            if ($c === '?') {
                $this->url->query = '';
                $this->state = self::QUERY_STATE;
            } elseif ($c === '#') {
                $this->url->fragment = '';
                $this->state = self::FRAGMENT_STATE;
            }

            return self::RETURN_OK;
        }

        if (preg_match(URLUtils::REGEX_URL_CODE_POINTS, $c) !== 1
            && $c !== '%'
        ) {
            // Validation error
        }

        if ($c === '%' && !$this->remainingStartsWithTwoAsciiHexDigits()) {
            // Validation error
        }

        $this->buffer .= URLUtils::utf8PercentEncode(
            $c,
            URLUtils::PATH_PERCENT_ENCODE_SET
        );

        return self::RETURN_OK;
    }

    /**
     * @see https://url.spec.whatwg.org/#cannot-be-a-base-url-path-state
     *
     * @param string $c
     *
     * @return int
     */
    private function cannotBeABaseUrlPathState($c)
    {
        if ($c === '?') {
            $this->url->query = '';
            $this->state = self::QUERY_STATE;

            return self::RETURN_OK;
        } elseif ($c === '#') {
            $this->url->fragment = '';
            $this->state = self::FRAGMENT_STATE;

            return self::RETURN_OK;
        }

        if ($c !== ''/* EOF */ &&
            preg_match(URLUtils::REGEX_URL_CODE_POINTS, $c) !== 1
            && $c !== '%'
        ) {
            // Validation error.
        }

        if ($c === '%' && !$this->remainingStartsWithTwoAsciiHexDigits()) {
            // Validation error.
        }

        if ($c !== ''/* EOF */) {
            if (!empty($this->url->path)) {
                $this->url->path[0] .= URLUtils::utf8PercentEncode(
                    $c
                );
            }
        }

        return self::RETURN_OK;
    }

    /**
     * @see https://url.spec.whatwg.org/#query-state
     *
     * @param string $c
     *
     * @return int
     */
    private function queryState($c)
    {
        if ($this->encoding !== 'UTF-8'
            && (!$this->url->isSpecial()
                || $this->url->scheme === 'ws'
                || $this->url->scheme === 'wss')
        ) {
            $this->encoding = 'UTF-8';
        }

        if ($this->stateOverride === null && $c === '#') {
            $this->url->fragment = '';
            $this->state = self::FRAGMENT_STATE;
        } elseif ($c !== ''/* EOF */) {
            if (preg_match(URLUtils::REGEX_URL_CODE_POINTS, $c) !== 1
                && $c !== '%'
            ) {
                // Validation error.
            }

            if ($c === '%' && !$this->remainingStartsWithTwoAsciiHexDigits()) {
                // Validation error.
            }

            $bytes = mb_convert_encoding($c, $this->encoding);

            // This can happen when encoding code points using a non-UTF-8
            // encoding.
            if (mb_substr($bytes, 0, 2, $this->encoding) === '&#'
                && mb_substr($bytes, -1, null, $this->encoding) === ';'
            ) {
                $length = mb_strlen($bytes, $this->encoding);
                $bytes = '%26%23'
                    . mb_substr($bytes, 2, $length - 1, $this->encoding)
                    . '%3B';
                $this->url->query .= $bytes;
            } else {
                $length = strlen($bytes);

                for ($i = 0; $i < $length; ++$i) {
                    if ($bytes[$i] < "\x21"
                        || $bytes[$i] > "\x7E"
                        || $bytes[$i] === "\x22"
                        || $bytes[$i] === "\x23"
                        || $bytes[$i] === "\x3C"
                        || $bytes[$i] === "\x3E"
                        || ($bytes[$i] === "\x27" && $this->url->isSpecial())
                    ) {
                        $this->url->query .= rawurlencode($bytes[$i]);
                    } else {
                        $this->url->query .= $bytes[$i];
                    }
                }
            }
        }

        return self::RETURN_OK;
    }

    /**
     * @see https://url.spec.whatwg.org/#fragment-state
     *
     * @param string $c
     *
     * @return int
     */
    private function fragmentState($c)
    {
        if ($c === ''/* EOF */) {
            // Do nothing.
            return self::RETURN_OK;
        } elseif ($c === "\0") {
            // Validation error.
            return self::RETURN_OK;
        }

        if (preg_match(URLUtils::REGEX_URL_CODE_POINTS, $c) !== 1 &&
            $c !== '%'
        ) {
            // Validation error.
        }

        if ($c === '%' && !$this->remainingStartsWithTwoAsciiHexDigits()) {
            // Validation error.
        }

        $this->url->fragment .= URLUtils::utf8PercentEncode(
            $c,
            URLUtils::FRAGMENT_PERCENT_ENCODE_SET
        );

        return self::RETURN_OK;
    }

    /**
     * Determines if next two characters, starting immediately after the current
     * position in input, are ASCII hex digits.
     *
     * @return bool
     */
    private function remainingStartsWithTwoAsciiHexDigits()
    {
        $remaining = mb_substr(
            $this->input,
            $this->pointer + 1,
            2,
            $this->encoding
        );
        $length = mb_strlen($remaining, $this->encoding);

        return $length === 2 && ctype_xdigit($remaining);
    }
}
