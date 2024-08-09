<?php
namespace Rowbot\URL;

use function array_pop;
use function count;
use function implode;
use function mb_substr;
use function preg_match;

class URLRecord
{
    /**
     * An ASCII string that identifies the type of URL.
     *
     * @var string
     */
    public $scheme;

    /**
     * An ASCII string identifying a username.
     *
     * @var string
     */
    public $username;

    /**
     * An ASCII string identifying a password.
     *
     * @var string
     */
    public $password;

    /**
     * @var \Rowbot\URL\Host
     */
    public $host;

    /**
     * A 16-bit unsigned integer that identifies a networking port.
     *
     * @var int|null
     */
    public $port;

    /**
     * A list of zero or more ASCII strings holding data.
     *
     * @var array<int, string>
     */
    public $path;

    /**
     * An ASCII string holding data.
     *
     * @var string|null
     */
    public $query;

    /**
     * An ASCII string holding data.
     *
     * @var string|null
     */
    public $fragment;

    /**
     * Identifies whether the URL can act as a base URL.
     *
     * @var bool
     */
    public $cannotBeABaseUrl;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
        $this->scheme = '';
        $this->username = '';
        $this->password = '';
        $this->host = Host::createNullHost();
        $this->port = null;
        $this->path = [];
        $this->query = null;
        $this->fragment = null;
        $this->cannotBeABaseUrl = false;
    }

    /**
     * @return void
     */
    public function __clone()
    {
        $this->host = clone $this->host;
    }

    /**
     * Used to set the URL's username anywhere outside of the URL parser.
     *
     * @see https://url.spec.whatwg.org/#set-the-username
     *
     * @param string $username The URL's username.
     *
     * @return void
     */
    public function setUsername($username)
    {
        $this->username = '';

        while (($codePoint = mb_substr($username, 0, 1, 'UTF-8')) !== '') {
            $this->username .= URLUtils::utf8PercentEncode(
                $codePoint,
                URLUtils::USERINFO_PERCENT_ENCODE_SET
            );
            $username = mb_substr($username, 1, null, 'UTF-8');
        }
    }

    /**
     * Used to set the URL's passwrod anywhere outside of the URL parser.
     *
     * @see https://url.spec.whatwg.org/#set-the-password
     *
     * @param string $password The URL's password.
     *
     * @return void
     */
    public function setPassword($password)
    {
        $this->password = '';

        while (($codePoint = mb_substr($password, 0, 1, 'UTF-8')) !== '') {
            $this->password .= URLUtils::utf8PercentEncode(
                $codePoint,
                URLUtils::USERINFO_PERCENT_ENCODE_SET
            );
            $password = mb_substr($password, 1, null, 'UTF-8');
        }
    }

    /**
     * Returns whether or not the URL's scheme is a special scheme.
     *
     * @see https://url.spec.whatwg.org/#is-special
     *
     * @return bool
     */
    public function isSpecial()
    {
        return isset(URLUtils::$specialSchemes[$this->scheme]);
    }

    /**
     * Whether or not a URL can have a username, password, or port set.
     *
     * @see https://url.spec.whatwg.org/#cannot-have-a-username-password-port
     *
     * @return bool
     */
    public function cannotHaveUsernamePasswordPort()
    {
        return $this->host->isNull()
            || $this->host->equals('')
            || $this->cannotBeABaseUrl
            || $this->scheme === 'file';
    }

    /**
     * Whether or not the URL has a username or password.
     *
     * @see https://url.spec.whatwg.org/#include-credentials
     *
     * @return bool
     */
    public function includesCredentials()
    {
        return $this->username !== '' || $this->password !== '';
    }

    /**
     * Removes the last string from a URL's path if its scheme is not "file"
     * and the path does not contain a normalized Windows drive letter.
     *
     * @see https://url.spec.whatwg.org/#shorten-a-urls-path
     *
     * @return void
     */
    public function shortenPath()
    {
        $size = count($this->path);

        if ($size == 0) {
            return;
        }

        if ($this->scheme === 'file'
            && $size == 1
            && preg_match(
                URLUtils::REGEX_NORMALIZED_WINDOWS_DRIVE_LETTER,
                $this->path[0]
            ) === 1
        ) {
            return;
        }

        array_pop($this->path);
    }

    /**
     * Computes a URL's origin.
     *
     * @see https://url.spec.whatwg.org/#origin
     *
     * @return \Rowbot\URL\Origin
     */
    public function getOrigin()
    {
        if ($this->scheme === 'blob') {
            $url = BasicURLParser::parseBasicUrl($this->path[0]);

            if ($url === false) {
                // Return a new opaque origin
                return Origin::createOpaqueOrigin();
            }

            return $url->getOrigin();
        }

        if ($this->scheme === 'ftp'
            || $this->scheme === 'gopher'
            || $this->scheme === 'http'
            || $this->scheme === 'https'
            || $this->scheme === 'ws'
            || $this->scheme === 'wss'
        ) {
            // Return a tuple consiting of URL's scheme, host, port, and
            // null
            return Origin::createTupleOrigin(
                $this->scheme,
                $this->host,
                $this->port,
                null
            );
        }

        if ($this->scheme === 'file') {
            // Unfortunate as it is, this is left as an exercise to the
            // reader. When in doubt, return a new opaque origin.
            return Origin::createOpaqueOrigin();
        }

        // Return a new opaque origin.
        return Origin::createOpaqueOrigin();
    }

    /**
     * Determines whether two URLs are equal to eachother.
     *
     * @see https://url.spec.whatwg.org/#concept-url-equals
     *
     * @param self $otherUrl        A URL to compare equality against.
     * @param bool $excludeFragment (optional) determines whether a URL's fragment should be factored into equality.
     *
     * @return bool
     */
    public function isEqual(URLRecord $otherUrl, $excludeFragment = false)
    {
        return $this->serializeURL($excludeFragment)
            === $otherUrl->serializeURL($excludeFragment);
    }

    /**
     * Serializes a URL object.
     *
     * @see https://url.spec.whatwg.org/#concept-url-serializer
     *
     * @param bool $excludeFragment (optional) When specified it will exclude the URL's fragment from being serialized.
     *
     * @return string
     */
    public function serializeURL($excludeFragment = false)
    {
        $output = $this->scheme . ':';

        if (!$this->host->isNull()) {
            $output .= '//';

            if ($this->username !== '' || $this->password !== '') {
                $output .= $this->username;

                if ($this->password !== '') {
                    $output .= ':' . $this->password;
                }

                $output .= '@';
            }

            $output .= $this->host;

            if ($this->port !== null) {
                $output .= ':' . $this->port;
            }
        } elseif ($this->host->isNull() && $this->scheme === 'file') {
            $output .= '//';
        }

        if ($this->cannotBeABaseUrl) {
            $output .= $this->path[0];
        } else {
            if (!empty($this->path)) {
                $output .= '/';
            }

            $output .= implode('/', $this->path);
        }

        if ($this->query !== null) {
            $output .= '?' . $this->query;
        }

        if (!$excludeFragment && $this->fragment !== null) {
            $output .= '#' . $this->fragment;
        }

        return $output;
    }
}
