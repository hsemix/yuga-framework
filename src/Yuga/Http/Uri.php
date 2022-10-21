<?php

namespace Yuga\Http;

use Yuga\Http\Exceptions\BadFormedUrlException;

class Uri
{
    private $originalUrl;
    private $scheme;
    private $host;
    private $port;
    private $username;
    private $password;
    private $path;
    private $params;
    private $fragment;

    /**
     * Url constructor.
     *
     * @param string $url
     *
     * @throws BadFormedUrlException
     */
    public function __construct($url)
    {
        $this->originalUrl = $url;

        if ($url !== null && $url !== '/') {
            $data = $this->parseUrl($url);

            $this->scheme = isset($data['scheme']) ? $data['scheme'] : null;
            $this->host = isset($data['host']) ? $data['host'] : null;
            $this->port = isset($data['port']) ? $data['port'] : null;
            $this->username = isset($data['user']) ? $data['user'] : null;
            $this->password = isset($data['pass']) ? $data['pass'] : null;

            if (isset($data['path']) === true) {
                $this->setPath($data['path']);
            }

            $this->fragment = isset($data['fragment']) ? $data['fragment'] : null;

            if (isset($data['query']) === true) {
                $params = [];
                parse_str($data['query'], $params);
                $this->setParams($params);
            }
        }
    }

    /**
     * Check if url is using a secure protocol like https.
     *
     * @return bool
     */
    public function isSecure()
    {
        return strtolower($this->getScheme()) === 'https';
    }

    /**
     * Checks if url is relative.
     *
     * @return bool
     */
    public function isRelative()
    {
        return $this->getHost() === null;
    }

    /**
     * Get url scheme.
     *
     * @return string|null
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Set the scheme of the url.
     *
     * @param string $scheme
     *
     * @return static
     */
    public function setScheme($scheme)
    {
        $this->scheme = $scheme;

        return $this;
    }

    /**
     * Get url host.
     *
     * @return string|null
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Set the host of the url.
     *
     * @param string $host
     *
     * @return static
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Get url port.
     *
     * @return int|null
     */
    public function getPort()
    {
        return ($this->port !== null) ? (int) $this->port : null;
    }

    /**
     * Set the port of the url.
     *
     * @param int $port
     *
     * @return static
     */
    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * Parse username from url.
     *
     * @return string|null
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set the username of the url.
     *
     * @param string $username
     *
     * @return static
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Parse password from url.
     *
     * @return string|null
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set the url password.
     *
     * @param string $password
     *
     * @return static
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get path from url.
     *
     * @return string
     */
    public function getPath()
    {
        return isset($this->path) ? $this->path : '/';
    }

    /**
     * Set the url path.
     *
     * @param string $path
     *
     * @return static
     */
    public function setPath($path)
    {
        $this->path = rtrim($path, '/').'/';

        return $this;
    }

    /**
     * Get query-string from url.
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Merge parameters array.
     *
     * @param array $params
     *
     * @return static
     */
    public function mergeParams(array $params)
    {
        return $this->setParams(array_merge($this->getParams(), $params));
    }

    /**
     * Set the url params.
     *
     * @param array $params
     *
     * @return static
     */
    public function setParams(array $params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Get query-string params as string.
     *
     * @return string
     */
    public function getQueryString()
    {
        return static::arrayToParams($this->getParams());
    }

    /**
     * Get fragment from url (everything after #).
     *
     * @return string|null
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * Set url fragment.
     *
     * @param string $fragment
     *
     * @return static
     */
    public function setFragment($fragment)
    {
        $this->fragment = $fragment;

        return $this;
    }

    /**
     * @return string
     */
    public function getOriginalUrl()
    {
        return $this->originalUrl;
    }

    /**
     * Get position of value.
     * Returns -1 on failure.
     *
     * @param string $value
     *
     * @return int
     */
    public function indexOf($value)
    {
        $index = stripos($this->getOriginalUrl(), $value);

        return ($index === false) ? -1 : $index;
    }

    /**
     * Check if url contains value.
     *
     * @param string $value
     *
     * @return bool
     */
    public function contains($value)
    {
        return stripos($this->getOriginalUrl(), $value) !== false;
    }

    /**
     * Check if url contains parameter/query string.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasParam($name)
    {
        return \in_array($name, $this->getParams(), true);
    }

    /**
     * Removes parameter from query-string.
     *
     * @param string $name
     */
    public function removeParam($name)
    {
        if ($this->hasParam($name) === true) {
            $params = $this->getParams();
            $key = \array_search($name, $params, true);

            if ($key === true) {
                unset($params[$key]);
                $this->setParams($params);
            }
        }
    }

    /**
     * Get parameter by name.
     * Returns parameter value or default value.
     *
     * @param string      $name
     * @param string|null $defaultValue
     *
     * @return string|null
     */
    public function getParam($name, $defaultValue = null)
    {
        $output = null;

        if ($this->hasParam($name) === true) {
            $params = $this->getParams();
            $key = \array_search($name, $params, true);

            if ($key === true) {
                $output = $params[$key];
            }
        }

        return $output ?: $defaultValue;
    }

    /**
     * UTF-8 aware parse_url() replacement.
     *
     * @param string $url
     * @param int    $component
     *
     * @throws BadFormedUrlException
     *
     * @return array
     */
    public function parseUrl($url, $component = -1)
    {
        $encodedUrl = preg_replace_callback(
            '/[^:\/@?&=#]+/u',
            function ($matches) {
                return urlencode($matches[0]);
            },
            $url
        );

        $parts = parse_url($encodedUrl, $component);

        if ($parts === false) {
            throw new BadFormedUrlException(sprintf('Failed to parse url: "%s"', $url));
        }

        return array_map('urldecode', $parts);
    }

    /**
     * Convert array to query-string params.
     *
     * @param array $getParams
     * @param bool  $includeEmpty
     *
     * @return string
     */
    public static function arrayToParams(array $getParams = null, $includeEmpty = true)
    {
        if ($getParams) {
            if ($includeEmpty === false) {
                $getParams = array_filter($getParams, function ($item) {
                    return trim($item) !== '';
                });
            }

            return http_build_query($getParams);
        }

        return '';
    }

    /**
     * Returns the relative url.
     *
     * @return string
     */
    public function getRelativeUrl()
    {
        $params = $this->getQueryString();

        $path = isset($this->path) ? $this->path : '';
        $query = $params !== '' ? '?'.$params : '';
        $fragment = $this->fragment !== null ? '#'.$this->fragment : '';

        return $path.$query.$fragment;
    }

    /**
     * Returns the absolute url.
     *
     * @return string
     */
    public function getAbsoluteUrl()
    {
        $scheme = $this->scheme !== null ? $this->scheme.'://' : '';
        $host = isset($this->host) ? $this->host : '';
        $port = $this->port !== null ? ':'.$this->port : '';
        $user = isset($this->username) ? $this->username : '';
        $pass = $this->password !== null ? ':'.$this->password : '';
        $pass = ($user || $pass) ? $pass.'@' : '';

        return $scheme.$user.$pass.$host.$port.$this->getRelativeUrl();
    }

    public function __toString()
    {
        return $this->getRelativeUrl();
    }
}
