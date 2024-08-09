<?php
namespace Rowbot\URL;

use InvalidArgumentException;
use JsonSerializable;
use Rowbot\URL\Exception\TypeError;

use const JSON_UNESCAPED_SLASHES;

use function implode;
use function json_encode;
use function mb_substr;

/**
 * Represents a URL that can be manipulated.
 *
 * @see https://url.spec.whatwg.org/#api
 * @see https://developer.mozilla.org/en-US/docs/Web/API/URL
 *
 * @property string $href
 * @property-read string $origin
 * @property string $protocol
 * @property string $username
 * @property string $password
 * @property string $host
 * @property string $hostname
 * @property string $port
 * @property string $pathname
 * @property string $search
 * @property-read \Rowbot\URL\URLSearchParams $searchParams
 * @property string $hash
 */
class URL implements JsonSerializable
{
    use URLFormEncoded;

    /**
     * @var \Rowbot\URL\URLSearchParams
     */
    private $queryObject;

    /**
     * @var \Rowbot\URL\URLRecord
     */
    private $url;

    /**
     * Constructor.
     *
     * @param self|string $url
     * @param self|string $base
     *
     * @return void
     *
     * @throws \Rowbot\URL\Exception\TypeError
     */
    public function __construct($url, $base = null)
    {
        $parsedBase = null;

        if ($base !== null) {
            $parsedBase = BasicURLParser::parseBasicUrl(URLUtils::strval(
                $base
            ));

            if ($parsedBase === false) {
                throw new TypeError($base . ' is not a valid URL.');
            }
        }

        $parsedURL = BasicURLParser::parseBasicUrl(
            URLUtils::strval($url),
            $parsedBase
        );

        if ($parsedURL === false) {
            throw new TypeError($url . ' is not a valid URL.');
        }

        $this->url = $parsedURL;
        $query = $this->url->query ?: '';
        $this->queryObject = URLSearchParams::create($query, $parsedURL);
    }

    /**
     * @return void
     */
    public function __clone()
    {
        $this->url = clone $this->url;
        $this->queryObject = clone $this->queryObject;
        $this->queryObject->setUrl($this->url);
    }

    /**
     * @param string $name
     *
     * @return string
     *
     * @throws \InvalidArgumentException When an invalid $name value is passed.
     */
    public function __get($name)
    {
        if ($name === 'hash') {
            if ($this->url->fragment === null
                || $this->url->fragment === ''
            ) {
                return '';
            }

            return '#' . $this->url->fragment;
        }

        if ($name === 'host') {
            if ($this->url->host->isNull()) {
                return '';
            }

            if ($this->url->port === null) {
                return (string) $this->url->host;
            }

            return $this->url->host . ':' . $this->url->port;
        }

        if ($name === 'hostname') {
            if ($this->url->host->isNull()) {
                return '';
            }

            return (string) $this->url->host;
        }

        if ($name === 'href') {
            return $this->url->serializeURL();
        }

        if ($name === 'origin') {
            return (string) $this->url->getOrigin();
        }

        if ($name === 'password') {
            return $this->url->password;
        }

        if ($name === 'pathname') {
            if ($this->url->cannotBeABaseUrl) {
                return $this->url->path[0];
            }

            if (empty($this->url->path)) {
                return '';
            }

            return '/' . implode('/', $this->url->path);
        }

        if ($name === 'port') {
            if ($this->url->port === null) {
                return '';
            }

            return (string) $this->url->port;
        }

        if ($name === 'protocol') {
            return $this->url->scheme . ':';
        }

        if ($name === 'search') {
            if ($this->url->query === null || $this->url->query === '') {
                return '';
            }

            return '?' . $this->url->query;
        }

        if ($name === 'searchParams') {
            return $this->queryObject;
        }

        if ($name === 'username') {
            return $this->url->username;
        }

        throw new InvalidArgumentException($name . ' is not a valid property.');
    }

    /**
     * @param string     $name
     * @param string|int $value
     *
     * @return void
     *
     * @throws \InvalidArgumentException       When an invalid $name or $value value is passed.
     * @throws \Rowbot\URL\Exception\TypeError Only when trying to set URL::$searchParams
     */
    public function __set($name, $value)
    {
        if ($name === 'searchParams') {
            throw new TypeError();
        }

        $value = URLUtils::strval($value);

        if ($name === 'hash') {
            if ($value === '') {
                $this->url->fragment = null;

                // Terminate these steps
                return;
            }

            $input = $value;

            if (mb_substr($input, 0, 1, 'UTF-8') === '#') {
                $input = mb_substr($input, 1, null, 'UTF-8');
            }

            $this->url->fragment = '';
            BasicURLParser::parseBasicUrl(
                $input,
                null,
                null,
                $this->url,
                BasicURLParser::FRAGMENT_STATE
            );
        } elseif ($name === 'host') {
            if ($this->url->cannotBeABaseUrl) {
                // Terminate these steps
                return;
            }

            BasicURLParser::parseBasicUrl(
                $value,
                null,
                null,
                $this->url,
                BasicURLParser::HOST_STATE
            );
        } elseif ($name === 'hostname') {
            if ($this->url->cannotBeABaseUrl) {
                // Terminate these steps
                return;
            }

            BasicURLParser::parseBasicUrl(
                $value,
                null,
                null,
                $this->url,
                BasicURLParser::HOSTNAME_STATE
            );
        } elseif ($name === 'href') {
            $parsedURL = BasicURLParser::parseBasicUrl($value);

            if ($parsedURL === false) {
                throw new TypeError($value . ' is not a valid URL.');
            }

            $this->url = $parsedURL;
            $this->queryObject->setUrl($this->url);
            $this->queryObject->clear();

            if ($this->url->query !== null) {
                $this->queryObject->modify($this->urldecodeString(
                    $this->url->query
                ));
            }
        } elseif ($name === 'password') {
            if ($this->url->cannotHaveUsernamePasswordPort()) {
                return;
            }

            $this->url->setPassword($value);
        } elseif ($name === 'pathname') {
            if ($this->url->cannotBeABaseUrl) {
                // Terminate these steps
                return;
            }

            $this->url->path = [];
            BasicURLParser::parseBasicUrl(
                $value,
                null,
                null,
                $this->url,
                BasicURLParser::PATH_START_STATE
            );
        } elseif ($name === 'port') {
            if ($this->url->cannotHaveUsernamePasswordPort()) {
                return;
            }

            if ($value === '') {
                $this->url->port = null;
                return;
            }

            BasicURLParser::parseBasicUrl(
                $value,
                null,
                null,
                $this->url,
                BasicURLParser::PORT_STATE
            );
        } elseif ($name === 'protocol') {
            BasicURLParser::parseBasicUrl(
                $value . ':',
                null,
                null,
                $this->url,
                BasicURLParser::SCHEME_START_STATE
            );
        } elseif ($name === 'search') {
            if ($value === '') {
                $this->url->query = null;
                $this->queryObject->clear();

                return;
            }

            $input = $value;

            if (mb_substr($input, 0, 1, 'UTF-8') === '?') {
                $input = mb_substr($input, 1, null, 'UTF-8');
            }

            $this->url->query = '';
            BasicURLParser::parseBasicUrl(
                $input,
                null,
                null,
                $this->url,
                BasicURLParser::QUERY_STATE
            );
            $this->queryObject->modify($this->urldecodeString($input));
        } elseif ($name === 'username') {
            if ($this->url->cannotHaveUsernamePasswordPort()) {
                return;
            }

            $this->url->setUsername($value);
        } else {
            throw new InvalidArgumentException(
                $name . ' is not a valid property.'
            );
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->url->serializeURL();
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->url->serializeURL();
    }

    /**
     * Returns a JSON encoded string without escaping forward slashes. If you
     * need forward slashes to be escaped, pass the URL object to json_encode()
     * instead of calling this method.
     *
     * @see https://url.spec.whatwg.org/#dom-url-tojson
     *
     * @return string
     */
    public function toJSON()
    {
        // Use JSON_UNESCAPED_SLASHES here since JavaScript's JSON.stringify()
        // method does not escape forward slashes by default.
        return json_encode($this->url->serializeURL(), JSON_UNESCAPED_SLASHES);
    }

    /**
     * Returns the serialized URL for consumption by json_encode(). To match
     * JavaScript's behavior, you should pass the JSON_UNESCAPED_SLASHES option
     * to json_encode().
     *
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->url->serializeURL();
    }
}
