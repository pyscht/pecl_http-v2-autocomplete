<?php
/**
 * Helper autocomplete for pecl_http 2.x extension
 *
 * @link   https://mdref.m6w6.name/http
 *
 * @author N.V.
 */

namespace
{

    /**
     * Use HTTP/1.0 protocol version.
     */
    define('http\Client\Curl\HTTP_VERSION_1_0', 1);

    /**
     * Use HTTP/1.1 protocol version.
     */
    define('http\Client\Curl\HTTP_VERSION_1_1', 2);

    /**
     * Use HTTP/2 protocol version. Available if libcurl is v7.33.0 or more recent and was built with nghttp2 support.
     */
    define('http\Client\Curl\HTTP_VERSION_2_0', 3);

    /**
     * Use any HTTP protocol version.
     */
    define('http\Client\Curl\HTTP_VERSION_ANY', 0);

    /**
     * Use any TLS v1 encryption.
     */
    defined('http\Client\Curl\SSL_VERSION_TLSv1', 1);

    /**
     * Use TLS v1.0 encryption.
     */
    defined('http\Client\Curl\SSL_VERSION_TLSv1_0', 4);

    /**
     * Use TLS v1.1 encryption.
     */
    defined('http\Client\Curl\SSL_VERSION_TLSv1_1', 5);

    /**
     * Use TLS v1.2 encryption.
     */
    defined('http\Client\Curl\SSL_VERSION_TLSv1_2', 6);

    /**
     * Use SSL v2 encryption.
     */
    defined('http\Client\Curl\SSL_VERSION_SSLv2', 2);

    /**
     * Use SSL v3 encryption.
     */
    defined('http\Client\Curl\SSL_VERSION_SSLv3', 3);

    /**
     * Use any encryption.
     */
    defined('http\Client\Curl\SSL_VERSION_ANY', 0);

    /**
     * Use TLS SRP authentication. Available if libcurl is v7.21.4 or more recent and was built with gnutls or openssl with TLS-SRP support.
     */
    defined('http\Client\Curl\TLSAUTH_SRP', 1);

    /**
     * Use IPv4 resolver.
     */
    defined('http\Client\Curl\IPRESOLVE_V4', 1);

    /**
     * Use IPv6 resolver.
     */
    defined('http\Client\Curl\IPRESOLVE_V6', 2);

    /**
     * Use any resolver.
     */
    defined('http\Client\Curl\IPRESOLVE_ANY', 0);

    /**
     * Use Basic authentication.
     */
    defined('http\Client\Curl\AUTH_BASIC', 1);

    /**
     * Use Digest authentication.
     */
    defined('http\Client\Curl\AUTH_DIGEST', 2);

    /**
     * Use IE (lower v7) quirks with Digest authentication. Available if libcurl is v7.19.3 or more recent.
     */
    defined('http\Client\Curl\AUTH_DIGEST_IE', 16);

    /**
     * Use NTLM authentication.
     */
    defined('http\Client\Curl\AUTH_NTLM', 8);

    /**
     * Use GSS-Negotiate authentication.
     */
    defined('http\Client\Curl\AUTH_GSSNEG', 4);

    /**
     * Use HTTP Negotiate authentication (SPNEGO, RFC4559). Available if libcurl is v7.38.0 or more recent.
     * NOTE: constant is not defined
     */
    defined('http\Client\Curl\AUTH_SPNEGO', null);

    /**
     * Use any authentication.
     */
    defined('http\Client\Curl\AUTH_ANY', -17);

    /**
     * Use SOCKSv4 proxy protocol.
     */
    defined('http\Client\Curl\PROXY_SOCKS4', 4);

    /**
     * Use SOCKSv4a proxy protocol.
     */
    defined('http\Client\Curl\PROXY_SOCKS4A', 5);

    /**
     * Use SOCKS5h proxy protocol.
     */
    defined('http\Client\Curl\PROXY_SOCKS5_HOSTNAME', 5);

    /**
     * Use SOCKS5 proxy protoccol.
     */
    defined('http\Client\Curl\PROXY_SOCKS5', 5);

    /**
     * Use HTTP/1.1 proxy protocol.
     */
    defined('http\Client\Curl\PROXY_HTTP', 0);

    /**
     * Use HTTP/1.0 proxy protocol. Available if libcurl is v7.19.4 or more recent.
     */
    defined('http\Client\Curl\PROXY_HTTP_1_0', 1);

    /**
     * Keep POSTing on 301 redirects. Available if libcurl is v7.19.1 or more recent.
     */
    defined('http\Client\Curl\POSTREDIR_301', 1);

    /**
     * Keep POSTing on 302 redirects. Available if libcurl is v7.19.1 or more recent.
     */
    defined('http\Client\Curl\POSTREDIR_302', 2);

    /**
     * Keep POSTing on 303 redirects. Available if libcurl is v7.19.1 or more recent.
     */
    defined('http\Client\Curl\POSTREDIR_303', 4);

    /**
     * Keep POSTing on any redirect. Available if libcurl is v7.19.1 or more recent.
     */
    defined('http\Client\Curl\POSTREDIR_ALL', 7);
}

namespace http
{

    use http\Client\Request;
    use http\Client\Response;
    use Serializable;
    use SplObjectStorage;
    use SplObserver;
    use SplSubject;
    use Countable;
    use http\Exception\InvalidArgumentException;
    use http\Exception\UnexpectedValueException;
    use http\Message\Body;
    use http\Exception\BadHeaderException;
    use http\Exception\BadMessageException;
    use http\Exception\BadMethodCallException;
    use Iterator;
    use ArrayAccess;
    use http\Exception\BadConversionException;
    use http\Exception\BadQueryStringException;
    use IteratorAggregate;
    use RecursiveArrayIterator;
    use http\Exception\BadUrlException;

    interface Exception
    {
    }

    /**
     * Client
     *
     * Helper autocomplete for pecl_http 2.x extension
     *
     * The HTTP client.
     *
     * @see    http\Client\Curl’s options which is the only driver currently supported.
     *
     * @link   https://mdref.m6w6.name/http/Client
     *
     * @author N.V.
     */
    class Client implements SplSubject, Countable
    {

        /**
         * Attached observers.
         *
         * @var SplObjectStorage
         */
        private $observers = null;

        /**
         * Set options.
         *
         * @var array
         */
        protected $options = null;

        /**
         * Request/response history.
         *
         * @var Message
         */
        protected $history = null;

        /**
         * Whether to record history in http\Client::$history.
         *
         * @var bool
         */
        public $recordHistory = false;

        /**
         * Create a new HTTP client.
         *
         * @link https://mdref.m6w6.name/http/Client/__construct
         *
         * @param string $driver               The HTTP client driver to employ. Currently only the default driver, “curl”, is supported.
         * @param string $persistent_handle_id If supplied, created curl handles will be persisted with this identifier for later reuse.
         *
         * @throws Exception\InvalidArgumentException
         * @throws Exception\UnexpectedValueException
         * @throws Exception\RuntimeException
         */
        public function __construct($driver = null, $persistent_handle_id = null)
        {
        }

        /**
         * Add custom cookies.
         *
         * @link https://mdref.m6w6.name/http/Client/addCookies
         *
         * @param array $cookies Custom cookies to add.
         *
         * @return Client
         *
         * @throws Exception\InvalidArgumentException
         */
        public function addCookies($cookies = null)
        {
        }

        /**
         * Add specific SSL options.
         *
         * @link https://mdref.m6w6.name/http/Client/addSslOptions
         *
         * @param array $ssl_options optional Add this SSL options.
         *
         * @return Client
         *
         * @throws Exception\InvalidArgumentException
         */
        public function addSslOptions($ssl_options = null)
        {
        }

        /**
         * {@inheritdoc}
         * @see  SplSubject::attach()
         *
         * @link https://mdref.m6w6.name/http/Client/attach
         *
         * @return Client
         *
         * @throws Exception\InvalidArgumentException
         * @throws Exception\UnexpectedValueException
         */
        public function attach(SplObserver $observer)
        {
        }

        /**
         * Configure the client’s low level options.
         *
         * @link https://mdref.m6w6.name/http/Client/configure
         *
         * @see  https://mdref.m6w6.name/http/Client/Curl#Configuration:
         *
         * @param array $configuration Key/value pairs of low level options.
         *
         * @return Client
         *
         * @throws Exception\InvalidArgumentException
         * @throws Exception\UnexpectedValueException
         */
        public function configure(array $configuration)
        {
        }

        /**
         * Retrieve the number of enqueued requests.
         * Note: The enqueued requests are counted without regard whether they are finished or not.
         *
         * @link https://mdref.m6w6.name/http/Client/count
         *
         * @return int Number of enqueued requests
         */
        public function count()
        {
        }

        /**
         * Dequeue the http\Client\Request $request.
         *
         * See Client::requeue(), if you want to requeue the request, instead of calling Client::dequeue() and then Client::enqueue().
         *
         * @link https://mdref.m6w6.name/http/Client/dequeue
         *
         * @param Request $request The request to cancel.
         *
         * @return Client
         *
         * @throws Exception\InvalidArgumentException
         * @throws Exception\BadMethodCallException
         * @throws Exception\RuntimeException
         */
        public function dequeue(Request $request)
        {
        }

        /**
         * {@inheritdoc}
         * @see  SplSubject::attach()
         *
         * @link https://mdref.m6w6.name/http/Client/detach
         *
         * @return Client
         *
         * @throws Exception\InvalidArgumentException
         * @throws Exception\UnexpectedValueException
         */
        public function detach(SplObserver $observer)
        {
        }

        /**
         * Enable usage of an event library like libevent, which might improve performance with big socket sets.
         *
         * @deprecated since 2.3.0, please use http\Client::configure() instead.
         *
         * @link       https://mdref.m6w6.name/http/Client/enableEvents
         *
         * @param bool $enable Whether to enable libevent usage.
         *
         * @return Client
         *
         * @throws Exception\InvalidArgumentException
         * @throws Exception\UnexpectedValueException
         */
        public function enableEvents($enable = true)
        {
        }

        /**
         * Add another http\Client\Request to the request queue. If the optional callback $cb returns true, the request will be automatically dequeued.
         *
         * @link https://mdref.m6w6.name/http/Client/enqueue
         *
         * @see  http\Client::send()
         * @see  http\Client::dequeue()
         *
         * @param Request  $request The request to enqueue.
         * @param callable $cb      optional A callback to automatically call when the request has finished.
         *
         * @return Client
         *
         * @throws Exception\InvalidArgumentException
         * @throws Exception\BadMethodCallException
         * @throws Exception\RuntimeException
         */
        public function enqueue(Request $request, callable $cb = null)
        {
        }

        /**
         * Enable sending pipelined requests to the same host if the driver supports it.
         *
         * @deprecated since 2.3.0, please use http\Client::configure() instead.
         *
         * @link       https://mdref.m6w6.name/http/Client/enablePipelining
         *
         * @param bool $enable Whether to enable pipelining.
         *
         * @return Client
         *
         * @throws Exception\InvalidArgumentException
         * @throws Exception\UnexpectedValueException
         */
        public function enablePipelining($enable = true)
        {
        }

        /**
         * Get a list of available configuration options and their default values.
         *
         * @link https://mdref.m6w6.name/http/Client/getAvailableConfiguration
         *
         * @see  https://mdref.m6w6.name/http/Client/Curl#Configuration:
         *
         * @return array List of key/value pairs of available configuarion options and their default values.
         *
         * @throws Exception\InvalidArgumentException
         */
        public function getAvailableConfiguration()
        {
        }

        /**
         * List available drivers.
         *
         * @link https://mdref.m6w6.name/http/Client/getAvailableDrivers
         *
         * @return array List of supported drivers.
         *
         * @throws Exception\InvalidArgumentException
         */
        public function getAvailableDrivers()
        {
        }

        /**
         * Retrieve a list of available request options and their default values.
         *
         * @link https://mdref.m6w6.name/http/Client/getAvailableOptions
         *
         * @see  https://mdref.m6w6.name/http/Client/Curl#Configuration:
         *
         * @return array Returns list of key/value pairs of available request options and their default values.
         *
         * @throws Exception\InvalidArgumentException
         */
        public function getAvailableOptions()
        {
        }

        /**
         * Get priorly set custom cookies.
         *
         * @link https://mdref.m6w6.name/http/Client/getCookies
         *
         * @see  http\Client::setCookies()
         *
         * @return array Custom cookies.
         */
        public function getCookies()
        {
        }

        /**
         * Simply returns the http\Message chain representing the request/response history.
         * Note: The history is only recorded while http\Client::$recordHistory is true.
         *
         * @link https://mdref.m6w6.name/http/Client/getHistory
         *
         * @return Message Returns request/response message chain representing the client’s history.
         *
         * @throws Exception\InvalidArgumentException
         */
        public function getHistory()
        {
        }

        /**
         * Get priorly set options.
         *
         * @link https://mdref.m6w6.name/http/Client/getOptions
         *
         * @see  http\Client::setOptions()
         *
         * @return array
         */
        public function getOptions()
        {
        }

        /**
         * Retrieve the progress information for $request.
         *
         * @link https://mdref.m6w6.name/http/Client/getProgressInfo
         *
         * @param Request $request The request to retrieve the current progress information for.
         *
         * @return object|null Instance holding progress information, NULL, if $request is not enqueued.
         *
         * @throws Exception\InvalidArgumentException
         * @throws Exception\UnexpectedValueException
         */
        public function getProgressInfo(Request $request)
        {
        }

        /**
         * Retrieve the corresponding reponse of an already finished request, or the last received response if $request is not set.
         * Note: If $request is NULL, then the response is removed from the internal storage (stack-like operation).
         *
         * @link https://mdref.m6w6.name/http/Client/getResponse
         *
         * @param Request $request The request to fetch the stored response for.
         *
         * @return Response The stored response for the request, or the last that was received, NULL, if no more response was available to pop, when no $request was given.
         *
         * @throws Exception\InvalidArgumentException
         * @throws Exception\UnexpectedValueException
         */
        public function getResponse(Request $request = null)
        {
        }

        /**
         * Retrieve priorly set SSL options.
         *
         * @link https://mdref.m6w6.name/http/Client/getSslOptions
         *
         * @see  http\Client::setSslOptions()
         * @see  http\Client::getOptions()
         *
         * @return array SSL options.
         */
        public function getSslOptions()
        {
        }

        /**
         * Get transfer related informatioin for a running or finished request.
         *
         * @link https://mdref.m6w6.name/http/Client/getTransferInfo
         *
         * @param Request $request The request to probe for transfer info.
         *
         * @return object Instance holding transfer related information.
         *
         * @throws Exception\InvalidArgumentException
         * @throws Exception\UnexpectedValueException
         */
        public function getTransferInfo(Request $request)
        {
        }

        /**
         * {@inheritdoc}
         * @see  SplSubject::notify()
         *
         * @link https://mdref.m6w6.name/http/Client/notify
         *
         * @param Request $request  The request to notify about.
         * @param object  $progress Instance holding progress information.
         *
         * @return Client Returns self.
         *
         * @throws Exception\InvalidArgumentException
         * @throws Exception\UnexpectedValueException
         */
        public function notify(Request $request = null, $progress = null)
        {
        }

        /**
         * Perform outstanding transfer actions.
         * @see  http\Client::wait() for the completing interface.
         *
         * @link https://mdref.m6w6.name/http/Client/once
         *
         * @return bool True if there are more transfers to complete.
         */
        public function once()
        {
        }

        /**
         * Requeue an http\Client\Request.
         * The difference simply is, that this method, in contrast to http\Client::enqueue(), does not throw an http\Exception when the request to queue is already enqueued and dequeues it automatically prior enqueueing it again.
         *
         * @link https://mdref.m6w6.name/http/Client/requeue
         *
         * @param Request $request The request to queue.
         *
         * @return Client Returns self.
         *
         * @throws Exception\InvalidArgumentException
         * @throws Exception\RuntimeException
         */
        public function requeue(Request $request)
        {
        }

        /**
         * Reset the client to the initial state.
         *
         * @link https://mdref.m6w6.name/http/Client/reset
         *
         * @return Client Returns self.
         */
        public function reset()
        {
        }

        /**
         * Send all enqueued requests.
         *
         * @link https://mdref.m6w6.name/http/Client/send
         *
         * @see  http\Client::once()
         * @see  http\Client::wait() for a more fine grained interface.
         *
         * @return Client Returns self.
         *
         * @throws Exception\InvalidArgumentException
         * @throws Exception\RuntimeException
         */
        public function send()
        {
        }

        /**
         * Set custom cookies.
         *
         * @link https://mdref.m6w6.name/http/Client/setCookies
         *
         * @see  http\Client::addCookies()
         * @see  http\Client::getCookies()
         *
         * @param array $cookies Set the custom cookies to this array.
         *
         * @return Client Returns self.
         *
         * @throws Exception\InvalidArgumentException
         */
        public function setCookies(array $cookies = null)
        {
        }

        /**
         * Set client options.
         * Note: Only options specified prior enqueueing a request are applied to the request.
         *
         * @link https://mdref.m6w6.name/http/Client/setOptions
         *
         * @see  http\Client\Curl
         *
         * @param array $options The options to set.
         *
         * @return Client Returns self.
         *
         * @throws Exception\InvalidArgumentException
         */
        public function setOptions(array $options = null)
        {
        }

        /**
         * Specifically set SSL options.
         *
         * @link https://mdref.m6w6.name/http/Client/setSslOptions
         *
         * @see  http\Client::setOptions()
         * @see  http\Client\Curl::$ssl
         *
         * @param array $ssl_options
         *
         * @return Client Returns self.
         *
         * @throws Exception\InvalidArgumentException
         */
        public function setSslOptions(array $ssl_options = null)
        {
        }

        /**
         * Wait for $timeout seconds for transfers to provide data. This is the completion call to http\Client::once().
         *
         * @link https://mdref.m6w6.name/http/Client/wait
         *
         * @param float $timeout Seconds to wait for data on open sockets.
         *
         * @return bool Success.
         */
        public function wait($timeout = .0)
        {
        }
    }

    class Cookie {

    }

    class Encoding {

    }

    /**
     * Env
     *
     * Helper autocomplete for pecl_http 2.x extension
     *
     * The http\Env class provides static methods to manipulate and inspect the server’s current request’s HTTP environment.
     *
     * Request startup
     * In versions lower than 2.4.0, the http\Env module extends PHP’s builtin POST data parser to be run also if the request method is not POST. Additionally it will handle application/json payloads if ext/json is available. Successfully parsed JSON will be put right into the $_POST array.
     * This functionality has been separated into two distict extensions, pecl/apfd and pecl/json_post.
     *
     * @author N.V.
     */
    class Env
    {
        /**
         * Retreive the current HTTP request’s body.
         *
         * @param string $body_class_name optional A user class extending http\Message\Body.
         *
         * @return Body Returns instance representing the request body
         *
         * @throws InvalidArgumentException
         * @throws UnexpectedValueException
         */
        public static function getRequestBody($body_class_name = 'http\Message\Body') { }

        /**
         * Retrieve one or all headers of the current HTTP request.
         *
         * @param string $header_name optional The key of a header to retrieve.
         *
         * @return string|array|null Returns null, if $header_name was not found or string, the compound header when $header_name was found or array of all headers if $header_name was not specified
         */
        public static function getRequestHeader($header_name = null) { }

        /**
         * Get the HTTP response code to send.
         *
         * @return int Returns the HTTP response code.
         */
        public static function getResponseCode() { }

        /**
         * Get one or all HTTP response headers to be sent.
         *
         * @param string $header_name optional The name of the response header to retrieve.
         *
         * @return string|array|null Returns string, the compound value of the response header to send or NULL, if the header was not found or array, of all response headers, if $header_name was not specified
         */
        public static function getResponseHeader($header_name = null) { }

        /**
         * Retrieve a list of all known HTTP response status.
         *
         * @return array Returns mapping of the form:
         *  [
         *  ...
         *      int $code => string $status
         *  ...
         *  ]
         */
        public static function getResponseStatusForAllCodes() { }

        /**
         * Retrieve the string representation of specified HTTP response code.
         *
         * @param int $code The HTTP response code to get the string representation for.
         *
         * @return string Returns string, the HTTP response status message or empty string, if no message for this code was found
         */
        public static function getResponseStatusForCode($code) { }

        /**
         * Generic negotiator.
         * For specific client negotiation see http\Env::negotiateContentType() and related methods.
         * Note: The first element of $supported serves as a default if no operand matches.
         *
         * @param string $params HTTP header parameter’s value to negotiate.
         * @param array $supported List of supported negotiation operands.
         * @param string $prim_typ_sep optional A “primary type separator”, i.e. that would be a hyphen for content language negotiation (en-US, de-DE, etc.).
         * @param array $result optional Out parameter recording negotiation results.
         *
         * @return string Returns null, if negotiation fails or string, the closest match negotiated, or the default (first entry of $supported).
         */
        public static function negotiate($params, array $supported, $prim_typ_sep = 'en_US', array &$result = null) { }

        /**
         * Negotiate the client’s preferred character set.
         * Note: The first element of $supported character sets serves as a default if no character set matches.
         *
         * @param array $supported List of supported content character sets.
         * @param array $result optional Out parameter recording negotiation results.
         *
         * @return string Returns null, if negotiation fails or string, the negotiated character set.
         */
        public static function negotiateCharset(array $supported, array &$result = null) { }

        /**
         * Negotiate the client’s preferred MIME content type.
         * Note: The first element of $supported content types serves as a default if no content-type matches.
         *
         * @param array $supported List of supported MIME content types.
         * @param array $result optional Out parameter recording negotiation results.
         *
         * @return string Returns null, if negotiation fails or string, the negotiated content type.
         */
        public static function negotiateContentType(array $supported, array &$result = null) { }

        /**
         * Negotiate the client’s preferred encoding.
         * Note: The first element of $supported encodings serves as a default if no encoding matches.
         *
         * @param array $supported List of supported content encodings.
         * @param array $result optional Out parameter recording negotiation results.
         *
         * @return string Returns null, if negotiation fails or string, the negotiated encoding.
         */
        public static function negotiateEncoding(array $supported, array &$result = null) { }

        /**
         * Negotiate the client’s preferred language.
         * Note: The first element of $supported languages serves as a default if no language matches.
         *
         * @param array $supported List of supported content languages.
         * @param array $result optional Out parameter recording negotiation results.
         *
         * @return string Returns NULL, if negotiation fails or string, the negotiated language.
         */
        public static function negotiateLanguage(array $supported, array &$result) { }

        /**
         * Set the HTTP response code to send.
         *
         * @param int $code The HTTP response status code.
         *
         * @return bool Returns success.
         */
        public static function setResponseCode($code) { }

        /**
         * Set a response header, either replacing a prior set header, or appending the new header value, depending on $replace.
         * If no $header_value is specified, or $header_value is NULL, then a previously set header with the same key will be deleted from the list.
         * If $response_code is not 0, the response status code is updated accordingly.
         *
         * @param string $header_name The name of the response header.
         * @param mixed $header_value optional The he header value.
         * @param int  $response_code optional Any HTTP response status code to set.
         * @param bool $replace optional Whether to replace a previously set response header with the same name.
         *
         * @return bool Returns success.
         */
        public static function setResponseHeader($header_name, $header_value = null, $response_code = 0, $replace = true) { }
    }

    /**
     * Header
     *
     * Helper autocomplete for pecl_http 2.x extension
     *
     * The http\Header class provides methods to manipulate, match, negotiate and serialize HTTP headers.
     *
     * @author N.V.
     */
    class Header implements Serializable
    {

        /**
         * None of the following match constraints applies.
         */
        const MATCH_LOOSE = 0;

        /**
         * Perform case sensitive matching.
         */
        const MATCH_CASE = 1;

        /**
         * Match only on word boundaries (according by CType alpha-numeric).
         */
        const MATCH_WORD = 16;

        /**
         * Match the complete string.
         */
        const MATCH_FULL = 32;

        /**
         * Case sensitively match the full string (same as MATCH_CASE|MATCH_FULL).
         */
        const MATCH_STRICT = 33;

        /**
         * The name of the HTTP header.
         *
         * @var string
         */
        public $name = null;

        /**
         * The value of the HTTP header.
         *
         * @var mixed
         */
        public $value = null;

        /**
         * Create an http\Header instance for use of simple matching or negotiation. If the value of the header is an array it may be compounded to a single comma separated string.
         *
         * @param string $name optional The HTTP header name.
         * @param string $value optional The value of the header.
         *
         * @throws Exception
         */
        public function __construct($name = null, $value = null) { }

        /**
         * String cast handler. Alias of http\Header::serialize().
         *
         * @return string Returns the serialized form of the HTTP header (i.e. “Name: value”).
         */
        public function __toString() { }

        /**
         * Create a parameter list out of the HTTP header value.
         *
         * @param string $ps optional The parameter separator(s).
         * @param string $as optional The argument separator(s).
         * @param string $vs optional The value separator(s).
         * @param int $flags optional The modus operandi. See http\Params constants.
         *
         * @return Params
         */
        public function getParams($ps = Params::DEF_PARAM_SEP, $as = Params::DEF_ARG_SEP, $vs = Params::DEF_VAL_SEP, $flags = Params::PARSE_DEFAULT) { }

        /**
         * Match the HTTP header’s value against provided $value according to $flags.
         *
         * @param string $value The comparison value.
         * @param int $flags optional The modus operandi. See http\Header constants.
         *
         * @return bool Returns whether $value matches the header value according to $flags.
         */
        public function match($value, $flags = Header::MATCH_LOOSE) { }

        /**
         * Negotiate the header’s value against a list of supported values in $supported.
         * Negotiation operation is adopted according to the header name, i.e. if the header being negotiated is Accept, then a slash is used as primary type separator, and if the header is Accept-Language respectively, a hyphen is used instead.
         * Note: The first elemement of $supported serves as a default if no operand matches.
         *
         * @param array $supported The list of supported values to negotiate.
         * @param array $result optional Out parameter recording the negotiation results.
         *
         * @return string|null Returns NULL if negotiation fails or string, the closest match negotiated, or the default (first entry of $supported).
         */
        public function negotiate(array $supported, array &$result = null) { }

        /**
         * Parse HTTP headers.
         *
         * @param string $header The complete string of headers.
         * @param string $header_class optional A class extending http\Header.
         *
         * @return array|bool Returns array of parsed headers, where the elements are instances of $header_class if specified or false, if parsing fails.
         *
         * @warning If the header parser fails.
         */
        public static function parse($header, $header_class = null) { }

        /**
         * {@inheritdoc}
         * @see Serializable::serialize()
         */
        public function serialize() { }

        /**
         * Convenience method. Alias of http\Header::serialize().
         *
         * @return string Returns the serialized form of the HTTP header (i.e. “Name: value”).
         */
        public function toString() { }

        /**
         * {@inheritdoc}
         * @see Serializable::unserialize()
         */
        public function unserialize($serialized) { }
    }

    /**
     * Message
     *
     * Helper autocomplete for pecl_http 2.x extension
     *
     * The Message class builds the foundation for any request and response message.
     *
     * @see http\Client\Request
     * @see http\Client\Response
     * @see http\Env\Request
     * @see http\Env\Response
     *
     * @author N.V.
     */
    class Message implements Countable, Serializable, Iterator
    {

        /**
         * No specific type of message.
         */
        const TYPE_NONE = 0;

        /**
         * A request message.
         */
        const TYPE_REQUEST = 1;

        /**
         * A response message.
         */
        const TYPE_RESPONSE = 2;

        /**
         * The message type. See http\Message::TYPE_* constants.
         *
         * @var int
         */
        protected $type = Message::TYPE_NONE;

        /**
         * The message’s body.
         *
         * @var Body
         */
        protected $body = null;

        /**
         * The request method if the message is of type request.
         *
         * @var string
         */
        protected $requestMethod = "";

        /**
         * The request url if the message is of type request.
         *
         * @var string
         */
        protected $requestUrl = "";

        /**
         * The respose status phrase if the message is of type response.
         *
         * @var string
         */
        protected $responseStatus = "";

        /**
         * The response code if the message is of type response.
         *
         * @var int
         */
        protected $responseCode = 0;

        /**
         * A custom HTTP protocol version.
         *
         * @var string
         */
        protected $httpVersion = null;

        /**
         * Any message headers.
         *
         * @var array
         */
        protected $headers = null;

        /**
         * Any parent message.
         *
         * @var Message
         */
        protected $parentMessage;

        /**
         * Create a new HTTP message.
         *
         * @param mixed $message Either a resource or a string, representing the HTTP message.
         * @param bool  $greedy Whether to read from a $message resource until EOF.
         *
         * @throws InvalidArgumentException
         * @throws BadMessageException
         */
        public function __construct($message = null, $greedy = true) { }

        /**
         * Retrieve the message serialized to a string.
         * Alias of http\Message::toString()
         *
         * @see Message::toString()
         *
         * @return string Returns the single serialized HTTP message.
         */
        public function __toString() { }

        /**
         * Append the data of $body to the message’s body.
         *
         * @see http\Message::setBody()
         * @see http\Message\Body::append()
         *
         * @param Body $body The message body to add.
         *
         * @return Message
         */
        public function addBody(Body $body) { }

        /**
         * Add an header, appending to already existing headers.
         *
         * @see http\Message::addHeaders()
         * @see http\Message::setHeader()
         *
         * @param string $name The header name.
         * @param string $value The header value.
         *
         * @return Message
         */
        public function addHeader($name, $value) { }

        /**
         * Add headers, optionally appending values, if header keys already exist.
         *
         * @see http\Message::addHeader()
         * @see http\Message::setHeaders()
         *
         * @param array $headers The HTTP headers to add.
         * @param bool  $append Whether to append values for existing headers.
         *
         * @return Message
         */
        public function addHeaders(array $headers, $append = false) { }

        /**
         * {@inheritdoc}
         * @see Countable::count()
         *
         * @return int Returns count of messages in the chain above the current message.
         */
        public function count() { }

        /**
         * {@inheritdoc}
         * @see Iterator::current()
         */
        public function current() { }

        /**
         * Detach a clone of this message from any message chain.
         *
         * @return Message Returns clone of this message
         *
         * @throws InvalidArgumentException
         */
        public function detach() { }

        /**
         * Retrieve the message’s body.
         *
         * @see http\Message::setBody()
         *
         * @return Body Returns the message body.
         *
         * @throws InvalidArgumentException
         * @throws UnexpectedValueException
         */
        public function getBody() { }

        /**
         * Retrieve a single header, optionally hydrated into a http\Header extending class.
         *
         * @param string $header The header’s name.
         * @param string $into_class optional The name of a class extending http\Header.
         *
         * @return mixed Returns the header value if $into_class is NULL or http\Header, descendant.
         *
         * @warning If $into_class is specified but is not a descendant of http\Header.
         */
        public function getHeader($header, $into_class = null) { }

        /**
         * Retrieve all message headers.
         *
         * @see http\Message::setHeaders()
         * @see http\Message::getHeader()
         *
         * @return array Returns the message’s headers.
         */
        public function getHeaders() { }

        /**
         * Retreive the HTTP protocol version of the message.
         *
         * @see http\Message::setHttpVersion()
         *
         * @return string Returns the HTTP protocol version, e.g. “1.0”; defaults to “1.1”.
         */
        public function getHttpVersion() { }

        /**
         * Retrieve the first line of a request or response message.
         *
         * @see http\Message::setInfo()
         * @see http\Message::getType()
         * @see http\Message::getHttpVersion()
         * @see http\Message::getResponseCode()
         * @see http\Message::getResponseStatus()
         * @see http\Message::getRequestMethod()
         * @see http\Message::getRequestUrl()
         *
         * @return string Returns the HTTP message information or NULL, if the message is neither of type request nor response.
         */
        public function getInfo() { }

        /**
         * Retrieve any parent message.
         *
         * @see http\Message::reverse()
         *
         * @return Message Returns http/Message, the parent message.
         *
         * @throws InvalidArgumentException
         * @throws BadMethodCallException
         */
        public function getParentMessage() { }

        /**
         * Retrieve the request method of the message.
         *
         * @see http\Message::setRequestMethod()
         * @see http\Message::getRequestUrl()
         *
         * @return string Returns the request method or false, if the message was not of type request.
         *
         * @warning If the message is not of type request.
         */
        public function getRequestMethod() { }

        /**
         * Retrieve the request URL of the message.
         *
         * @see http\Message::setRequestUrl()
         *
         * @return string Returns the request URL; usually the path and the querystring or false, if the message was not of type request.
         *
         * @warning If the message is not of type request.
         */
        public function getRequestUrl() { }

        /**
         * Retrieve the response code of the message.
         *
         * @see http\Message::setResponseCode()
         * @see http\Massage::getResponseStatus()
         *
         * @return int Returns the response status code or false, if the message is not of type response.
         *
         * @warning If the message is not of type response.
         */
        public function getResponseCode() { }

        /**
         * Retrieve the response status of the message.
         *
         * @see http\Message::setResponseStatus()
         * @see http\Message::getResponseCode()
         *
         * @return string Returns the response status phrase or false, if the message is not of type response.
         *
         * @warning If the message is not of type response.
         */
        public function getResponseStatus() { }

        /**
         * Retrieve the type of the message.
         *
         * @see http\Message::setType()
         * @see http\Message::getInfo()
         *
         * @return int Returns the message type. See http\Message::TYPE_* constants.
         */
        public function getType() { }

        /**
         * Check whether this message is a multipart message based on it’s content type.
         * If the message is a multipart message and a reference $boundary is given, the boundary string of the multipart message will be stored in $boundary.
         *
         * @see http\Message::splitMultipartBody()
         *
         * @param string $boundary A reference where the boundary string will be stored
         *
         * @return bool Returns whether this is a message with a multipart “Content-Type”
         */
        public function isMultipart(&$boundary = null) { }

        /**
         * {@inheritdoc}
         * @see Iterator::key()
         */
        public function key() { }

        /**
         * {@inheritdoc}
         * @see Iterator::next()
         */
        public function next() { }

        /**
         * Prepend message(s) $message to this message, or the top most message of this message chain.
         * Note: The message chains must not overlap.
         *
         * @param Message $message The message (chain) to prepend as parent messages.
         * @param bool    $top Whether to prepend to the top-most parent message.
         *
         * @return Message
         *
         * @throws InvalidArgumentException
         * @throws UnexpectedValueException
         */
        public function prepend(Message $message, $top = true) { }

        /**
         * Reverse the message chain and return the former top-most message.
         * Note: Message chains are ordered in reverse-parsed order by default, i.e. the last parsed message is the message you’ll receive from any call parsing HTTP messages.
         * This call re-orders the messages of the chain and returns the message that was parsed first with any later parsed messages re-parentized.
         *
         * @return Message Returns the other end of the message chain.
         *
         * @throws InvalidArgumentException
         */
        public function reverse() { }

        /**
         * {@inheritdoc}
         * @see Iterator::rewind()
         */
        public function rewind() { }

        /**
         * {@inheritdoc}
         * @see Serializable::serialize()
         */
        public function serialize() { }

        /**
         * Set the message’s body.
         *
         * @see http\Message::getBody()
         * @see http\Message::addBody()
         *
         * @param Body $body The new message body.
         *
         * @return Message
         *
         * @throws InvalidArgumentException
         * @throws UnexpectedValueException
         */
        public function setBody(Body $body) { }

        /**
         * Set a single header.
         *
         * @see http\Message::getHeader()
         * @see http\Message::addHeader()
         *
         * @param string $header The header’s name.
         * @param mixed $value optional The header’s value. Removes the header if NULL.
         *
         * @return Message
         */
        public function setHeader($header, $value = null) { }

        /**
         * Set the message headers.
         *
         * @see http\Message::getHeaders()
         * @see http\Message::addHeaders()
         *
         * @param array $headers The message’s headers.
         *
         * @return Message
         */
        public function setHeaders(array $headers = NULL) { }

        /**
         * Set the HTTP protocol version of the message.
         *
         * @see http\Message::getHttpVersion()
         *
         * @param string $http_version The protocol version, e.g. “1.1”, optionally prefixed by “HTTP/”.
         *
         * @return Message
         *
         * @throws InvalidArgumentException
         * @throws BadHeaderException
         *
         * @notice If a non-standard version separator is encounted.
         */
        public function setHttpVersion($http_version) { }

        /**
         * Set the complete message info, i.e. type and response resp. request information, at once.
         *
         * Format of the message info:
         * The message info looks similar to the following line for a response, see also http\Message::setResponseCode() and http\Message::setResponseStatus():
         * HTTP/1.1 200 Ok
         *
         * The message info looks similar to the following line for a request, see also http\Message::setRequestMethod() and http\Message::setRequestUrl():
         * GET / HTTP/1.1
         *
         * @see http\Message::setHttpVersion()
         * @see http\Message::getInfo()
         *
         * @param string $http_info The message info (first line of an HTTP message).
         *
         * @return Message
         *
         * @throws InvalidArgumentException
         * @throws BadHeaderException
         */
        public function setInfo($http_info) { }

        /**
         * Set the request method of the message.
         *
         * @see http\Message::getRequestMethod()
         * @see http\Message::setRequestUrl()
         *
         * @param string $method The request method.
         *
         * @return Message
         *
         * @throws InvalidArgumentException
         * @throws BadMethodCallException
         */
        public function setRequestMethod($method) { }

        /**
         * Set the request URL of the message.
         * Note: The request URL in a request message usually only consists of the path and the querystring.
         *
         * @see http\Message::getRequestUrl()
         * @see http\Message::setRequestMethod()
         *
         * @param string $url The request URL.
         *
         * @return Message
         *
         * @throws InvalidArgumentException
         * @throws BadMethodCallException
         */
        public function setRequestUrl($url) { }

        /**
         * Set the response status code.
         * Note: This method also resets the response status phrase to the default for that code.
         *
         * @see http\Message::getResponseCode()
         * @see http\Message::setResponseStatus()
         *
         * @param int $response_code The response code.
         * @param bool $strict optional Whether to check that the response code is between 100 and 599 inclusive.
         *
         * @return Message
         *
         * @throws InvalidArgumentException
         * @throws BadMethodCallException
         */
        public function setResponseCode($response_code, $strict = true) { }

        /**
         * Set the response status phrase.
         *
         * @see http\Message::getResponseStatus()
         * @see http\Message::setResponseCode()
         *
         * @param string $response_status The status phrase.
         *
         * @return Message
         *
         * @throws InvalidArgumentException
         * @throws BadMethodCallException
         */
        public function setResponseStatus($response_status) { }

        /**
         * Set the message type and reset the message info.
         *
         * @see http\Message::getType()
         * @see http\Message::setInfo()
         *
         * @param int $type The desired message type. See the http\Message::TYPE_* constants.
         *
         * @return Message
         */
        public function setType($type) { }

        /**
         * Splits the body of a multipart message.
         *
         * @see http\Message::isMultipart()
         * @see http\Message\Body::addPart()
         *
         * @return Message Returns a message chain of all messages of the multipart body.
         *
         * @throws InvalidArgumentException
         * @throws BadMethodCallException
         * @throws BadMessageException
         */
        public function splitMultipartBody() { }

        /**
         * Stream the message through a callback.
         *
         * @param callable $callback The callback of the form function(http\Message $from, string $data).
         * @param int      $offset optional Start to stream from this offset.
         * @param int      $maxlen optional Stream at most $maxlen bytes, or all if $maxlen is less than 1.
         *
         * @return Message
         */
        public function toCallback(callable $callback, $offset = 0, $maxlen = 0) { }

        /**
         * Stream the message into stream $stream, starting from $offset, streaming $maxlen at most.
         *
         * @param resource  $stream The resource to write to.
         * @param int       $offset optional The starting offset.
         * @param int       $maxlen optional The maximum amount of data to stream. All content if less than 1.
         *
         * @return Message
         */
        public function toStream($stream, $offset = 0, $maxlen = 0) { }

        /**
         * Retrieve the message serialized to a string.
         *
         * @param bool $include_parent optional Whether to include all parent messages.
         *
         * @return string Returns the HTTP message chain serialized to a string.
         */
        public function toString($include_parent = false) { }

        /**
         * {@inheritdoc}
         * @see Serializable::unserialize()
         */
        public function unserialize($serialized) { }

        /**
         * {@inheritdoc}
         * @see Iterator::valid()
         */
        public function valid() { }
    }

    /**
     * Params
     *
     * Helper autocomplete for pecl_http 2.x extension
     *
     * Parse, interpret and compose HTTP (header) parameters.
     *
     * @author N.V.
     */
    class Params
    {

        /**
         * The default parameter separator (",").
         */
        const DEF_PARAM_SEP = ",";

        /**
         * The default argument separator (";").
         */
        const DEF_ARG_SEP = ";";

        /**
         * The default value separator ("=").
         */
        const DEF_VAL_SEP = "=";

        /**
         * Empty param separator to parse cookies.
         */
        const COOKIE_PARAM_SEP = "";

        /**
         * Do not interpret the parsed parameters.
         */
        const PARSE_RAW = 0;

        /**
         * http\Params constant
         */
        const PARSE_ESCAPED = 1;

        /**
         * Interpret input as default formatted parameters.
         */
        const PARSE_DEFAULT = 17;

        /**
         * Urldecode single units of parameters, arguments and values.
         */
        const PARSE_URLENCODED = 4;

        /**
         * Parse sub dimensions indicated by square brackets.
         */
        const PARSE_DIMENSION = 8;

        /**
         * Parse URL querystring (same as http\Params::PARSE_URLENCODED|http\Params::PARSE_DIMENSION).
         */
        const PARSE_QUERY = 12;

        /**
         * Parse RFC5987 style encoded character set and language information embedded in HTTP header params.
         */
        const PARSE_RFC5987 = 16;

        /**
         * Parse RFC5988 (Web Linking) tags of Link headers.
         */
        const PARSE_RFC5988 = 32;

        /**
         * The (parsed) parameters.
         *
         * @var array
         */
        public $params = NULL;

        /**
         * The parameter separator(s).
         *
         * @var array
         */
        public $param_sep = Params::DEF_PARAM_SEP;

        /**
         * The argument separator(s).
         *
         * @var array
         */
        public $arg_sep = Params::DEF_ARG_SEP;

        /**
         * The value separator(s).
         *
         * @var array
         */
        public $val_sep = Params::DEF_VAL_SEP;

        /**
         * The modus operandi of the parser. See http\Params::PARSE_* constants.
         *
         * @var int
         */
        public $flags = Params::PARSE_DEFAULT;

        public function __construct($params = null, $ps = Params::DEF_PARAM_SEP, $as = Params::DEF_ARG_SEP, $vs = Params::DEF_VAL_SEP, $flags = Params::PARSE_DEFAULT) { }

        /**
         * {@inheritdoc}
         * @see ArrayAccess::offsetExists()
         */
        public function offsetExists($offset) { }

        /**
         * {@inheritdoc}
         * @see ArrayAccess::offsetGet()
         */
        public function offsetGet($offset) { }

        /**
         * {@inheritdoc}
         * @see ArrayAccess::offsetSet()
         */
        public function offsetSet($offset, $value) { }

        /**
         * {@inheritdoc}
         * @see ArrayAccess::offsetUnset()
         */
        public function offsetUnset($offset) { }

        /**
         * Convenience method that simply returns http\Params::$params.
         *
         * @return  array   Returns array of parameters.
         */
        public function toArray() { }

        /**
         * Returns a stringified version of the parameters.
         *
         * @return string
         */
        public function toString() { }

        /**
         * String cast handler.
         *
         * @see http\Params::toString()
         *
         * @return  string  Returns a stringified version of the parameters.
         */
        public function __toString() { }
    }

    /**
     * QueryString
     *
     * Helper autocomplete for pecl_http 2.x extension
     *
     * The class provides versatile facilities to retrieve, use and manipulate query strings and form data.
     *
     * @author N.V.
     */
    class QueryString implements Serializable, ArrayAccess, IteratorAggregate
    {

        /**
         * Cast requested value to bool.
         */
        const TYPE_BOOL = 3;

        /**
         * Cast requested value to int.
         */
        const TYPE_INT = 1;

        /**
         * Cast requested value to float.
         */
        const TYPE_FLOAT = 2;

        /**
         * Cast requested value to string.
         */
        const TYPE_STRING = 6;

        /**
         * Cast requested value to an array.
         */
        const TYPE_ARRAY = 4;

        /**
         * Cast requested value to an object.
         */
        const TYPE_OBJECT = 5;

        /**
         * The global instance. See http\QueryString::getGlobalInstance().
         *
         * @var QueryString
         */
        private $instance = null;

        /**
         * The data.
         *
         * @var array
         */
        private $queryArray = null;

        /**
         * Create an independent querystring instance.
         *
         * @param mixed $params optional The query parameters to use or parse
         *
         * @throws BadQueryStringException
         */
        public function __construct($params = null) { }

        /**
         * Retrieve an querystring value.
         *
         * @see http\QueryString::TYPE_* constants.
         *
         * @param string $name optional The key to retrieve the value for.
         * @param mixed $type optional The type to cast the value to. See http\QueryString::TYPE_* constants.
         * @param mixed $defval optional The default value to return if the key $name does not exist.
         * @param bool $delete optional Whether to delete the entry from the querystring after retrieval.
         *
         * @return QueryString|string|mixed Returns
         *                                  http\QueryString, if called without arguments.
         *                                  string, the whole querystring if $name is of zero length.
         *                                  mixed, $defval if the key $name does not exist.
         *                                  mixed, the querystring value cast to $type if $type was specified and the key $name exists.
         *                                  string, the querystring value if the key $name exists and $type is not specified or equals http\QueryString::TYPE_STRING.
         */
        public function get($name = null, $type = null, $defval = null, $delete = false) { }

        /**
         * Retrieve an array value with at offset $name.
         *
         * @param string $name The key to look up.
         * @param mixed $defval optional The default value to return if the offset $name does not exist.
         * @param bool $delete optional Whether to remove the key and value from the querystring after retrieval.
         *
         * @return array Returns the (casted) value or mixed $defval if offset $name does not exist.
         */
        public function getArray($name, $defval = null, $delete = false) { }

        /**
         * Retrieve a boolean value at offset $name.
         *
         * @param string $name The key to look up.
         * @param mixed $defval optional The default value to return if the offset $name does not exist.
         * @param bool $delete optional Whether to remove the key and value from the querystring after retrieval.
         *
         * @return bool Returns the (casted) value or mixed $defval if offset $name does not exist.
         */
        public function getBool($name, $defval = null, $delete = false) { }

        /**
         * Retrieve a float value at offset $name.
         *
         * @param string $name The key to look up.
         * @param mixed $defval optional The default value to return if the offset $name does not exist.
         * @param bool $delete optional Whether to remove the key and value from the querystring after retrieval.
         *
         * @return float Returns the (casted) value or mixed $defval if offset $name does not exist.
         */
        public function getFloat($name, $defval = null, $delete = false) { }

        /**
         * Retrieve the global querystring instance referencing $_GET.
         *
         * @return QueryString Returns http\QueryString, the http\QueryString::$instance
         *
         * @throws UnexpectedValueException
         */
        public static function getGlobalInstance() { }

        /**
         * Retrieve a int value at offset $name.
         *
         * @param string $name The key to look up.
         * @param mixed $defval optional The default value to return if the offset $name does not exist.
         * @param bool $delete optional Whether to remove the key and value from the querystring after retrieval.
         *
         * @return int Returns the (casted) value or mixed $defval if offset $name does not exist.
         */
        public function getInt($name, $defval = null, $delete = false) { }

        /**
         * {@inheritdoc}
         * @see IteratorAggregate::getIterator()
         *
         * @return RecursiveArrayIterator
         *
         * @throws InvalidArgumentException
         */
        public function getIterator() { }

        /**
         * Retrieve a object value with at offset $name.
         *
         * @param string $name The key to look up.
         * @param mixed $defval optional The default value to return if the offset $name does not exist.
         * @param bool $delete optional Whether to remove the key and value from the querystring after retrieval.
         *
         * @return object Returns the (casted) value or mixed $defval if offset $name does not exist.
         */
        public function getObject($name, $defval = null, $delete = false) { }

        /**
         * Retrieve a string value with at offset $name.
         *
         * @param string $name The key to look up.
         * @param mixed $defval optional The default value to return if the offset $name does not exist.
         * @param bool $delete optional Whether to remove the key and value from the querystring after retrieval.
         *
         * @return string Returns the (casted) value or mixed $defval if offset $name does not exist.
         */
        public function getString($name, $defval = null, $delete = false) { }

        /**
         * Set additional $params to a clone of this instance.
         * Note: This method returns a clone (copy) of this instance.
         *
         * @param object|array|string $params Additional params as object, array or string to parse.
         *
         * @return QueryString Returns clone.
         *
         * @throws BadQueryStringException
         */
        public function mod($params = null) { }

        /**
         * {@inheritdoc}
         * @see ArrayAccess::offsetExists()
         */
        public function offsetExists($name) { }

        /**
         * {@inheritdoc}
         * @see ArrayAccess::offsetGet()
         */
        public function offsetGet($name) { }

        /**
         * {@inheritdoc}
         * @see ArrayAccess::offsetSet()
         */
        public function offsetSet($name, $data) { }

        /**
         * {@inheritdoc}
         * @see ArrayAccess::offsetUnset()
         */
        public function offsetUnset($name) { }

        /**
         * {@inheritdoc}
         * @see Serializable::serialize()
         */
        public function serialize() { }

        /**
         * Set additional querystring entries.
         *
         * @param object|array|string $params Additional params as object, array or string to parse.
         *
         * @return QueryString Returns self.
         */
        public function set($params) { }

        /**
         * Simply returns http\QueryString::$queryArray.
         *
         * @return array Returns the $queryArray property.
         */
        public function toArray() { }

        /**
         * Get the string represenation of the querystring (x-www-form-urlencoded).
         *
         * @return string Returns the x-www-form-urlencoded querystring.
         */
        public function toString() { }

        /**
         * {@inheritdoc}
         * @see Serializable::unserialize()
         */
        public function unserialize($serialized) { }

        /**
         * Translate character encodings of the querystring with ext/iconv.
         * Note: This method is only available when ext/iconv support was enabled at build time.
         *
         * @param string $from_enc The encoding to convert from.
         * @param string $to_enc The encoding to convert to.
         *
         * @return QueryString Returns self.
         *
         * @throws InvalidArgumentException
         * @throws BadConversionException
         */
        public function xlate($from_enc, $to_enc) { }
    }

    /**
     * Url
     *
     * Helper autocomplete for pecl_http 2.x extension
     *
     * The class provides versatile means to parse, construct and manipulate URLs.
     *
     * @author N.V.
     */
    class Url
    {

        /**
         * Replace parts of the old URL with parts of the new.
         */
        const REPLACE = 0x0; //0

        /**
         * Whether a relative path should be joined into the old path.
         */
        const JOIN_PATH = 0x1; //1

        /**
         * Whether the querystrings should be joined.
         */
        const JOIN_QUERY = 0x2; //2

        /**
         * Strip the user information from the URL.
         */
        const STRIP_USER = 0x4; //4

        /**
         * Strip the password from the URL.
         */
        const STRIP_PASS = 0x8; //8

        /**
         * Strip user and password information from URL (same as STRIP_USER|STRIP_PASS).
         */
        const STRIP_AUTH = 0xc; //12

        /**
         * Do not include the port.
         */
        const STRIP_PORT = 0x20; //32

        /**
         * Do not include the URL path.
         */
        const STRIP_PATH = 0x40; //64

        /**
         * Do not include the URL querystring.
         */
        const STRIP_QUERY = 0x80; //128

        /**
         * Strip the fragment (hash) from the URL.
         */
        const STRIP_FRAGMENT = 0x100; //256

        /**
         * Strip everything except scheme and host information.
         */
        const STRIP_ALL = 0x1ec; //492

        /**
         * Import initial URL parts from the SAPI environment.
         */
        const FROM_ENV = 0x1000; //4096

        /**
         * Whether to sanitize the URL path (consolidate double slashes, directory jumps etc.)
         */
        const SANITIZE_PATH = 0x2000; //8192

        /**
         * Parse locale encoded multibyte sequences (on systems with wide character support).
         */
        const PARSE_MBLOC = 0x10000; //65536

        /**
         * Parse UTF-8 encododed multibyte sequences.
         */
        const PARSE_MBUTF8 = 0x20000; //131072

        /**
         * Parse and convert multibyte hostnames according to IDNA (with libidn support).
         */
        const PARSE_TOIDN = 0x100000; //1048576

        /**
         * Percent encode multibyte sequences in the userinfo, path, query and fragment parts of the URL.
         */
        const PARSE_TOPCT = 0x200000; //2097152

        /**
         * The URL’s scheme.
         *
         * @var string
         */
        public $scheme = null;

        /**
         * Authenticating user.
         *
         * @var string
         */
        public $user = null;

        /**
         * Authentication password.
         *
         * @var string
         */
        public $pass = null;

        /**
         * Hostname/domain.
         *
         * @var string
         */
        public $host = null;

        /**
         * Port.
         *
         * @var string
         */
        public $port = null;

        /**
         * URL path.
         *
         * @var string
         */
        public $path = null;

        /**
         * URL querystring.
         *
         * @var string
         */
        public $query = null;

        /**
         * URL fragment (hash).
         *
         * @var string
         */
        public $fragment = null;

        /**
         * Create an instance of an http\Url.
         *
         * @param mixed $old_url optional Initial URL parts. Either an array, object, http\Url instance or string to parse.
         * @param mixed $new_url optional Overriding URL parts. Either an array, object, http\Url instance or string to parse.
         * @param int $flags optional The modus operandi of constructing the url. See http\Url constants.
         *
         * @throws InvalidArgumentException
         * @throws BadUrlException
         */
        public function __construct($old_url = null,  $new_url = null,  $flags = Url::FROM_ENV) { }

        /**
         * String cast handler.
         *
         * @see http\Url::toString()
         *
         * @return string Returns the URL as string.
         */
        public function __toString() { }

        /**
         * Clone this URL and apply $parts to the cloned URL.
         * Note: This method returns a clone (copy) of this instance.
         *
         * @param mixed $parts optional New URL parts.
         * @param int $flags optional Modus operandi of URL construction. See http\Url constants. (default: Url::JOIN_PATH | Url::JOIN_QUERY | Url::SANITIZE_PATH)
         *
         * @return Url Returns clone.
         *
         * @throws InvalidArgumentException
         * @throws BadUrlException
         */
        public function mod($parts = null, $flags = 0x2003) { }

        /**
         * Retrieve the URL parts as array.
         *
         * @return array Returns the URL parts.
         */
        public function toArray() { }

        /**
         * Get the string representation of the URL.
         *
         * @return string Returns the URL as string.
         */
        public function toString() { }
    }
}

namespace http\Client
{
    use http\Exception\BadQueryStringException;
    use http\Exception\InvalidArgumentException;
    use http\Exception\UnexpectedValueException;
    use http\Message;
    use http\Message\Body;
    use http\QueryString;
    use http\Cookie;
    use http\Exception\BadMethodCallException;
    use http\Url;

    /**
     * Client
     *
     * Helper autocomplete for pecl_http 2.x extension
     *
     * Provides an HTTP message implementation tailored to represent a request message to be sent by the client.
     *
     * @link https://mdref.m6w6.name/http/Client/Request
     *
     * @author N.V.
     */
    class Request extends Message
    {

        /**
         * Array of options for this request, which override client options.
         *
         * @var array
         */
        protected $options = null;

        /**
         * Create a new client request message to be enqueued and sent by http\Client.
         *
         * @var string $meth optional The request method.
         * @var string|Url $url optional The request URL.
         * @var array $headers optional HTTP headers.
         * @var Body $body optional Request body.
         *
         * @throws InvalidArgumentException
         * @throws UnexpectedValueException
         */
        public function __construct($meth = null, $url = null, array $headers = null, Body $body = null) { }

        /**
         * Add querystring data.
         *
         * @param string|array|QueryString $query_data Additional querystring data.
         *
         * @return Request
         *
         * @throws InvalidArgumentException
         * @throws UnexpectedValueException
         */
        public function addQuery($query_data) { }

        /**
         * Add specific SSL options.
         *
         * @see http\Client\Request::setSslOptions()
         * @see http\Client\Request::setOptions()
         * @see http\Client\Curl::$ssl options
         *
         * @param array $ssl_options optional Add this SSL options.
         *
         * @return Request
         *
         * @throws InvalidArgumentException
         */
        public function addSslOptions(array $ssl_options = null) { }

        /**
         * Extract the currently set “Content-Type” header.
         *
         * @see http\Client\Request::setContentType()
         *
         * @return string Returns the currently set content type or NULL, if no “Content-Type” header is set.
         */
        public function getContentType() { }

        /**
         * Get priority set options.
         *
         * @see http\Client\Request::setOptions()
         *
         * @return array
         */
        public function getOptions() { }

        /**
         * Retrieve the currently set querystring.
         *
         * @return string Returns string, the currently set querystring or NULL, if no querystring is set.
         */
        public function getQuery() { }

        /**
         * Retrieve priority set SSL options.
         *
         * @see http\Client\Request::getOptions()
         * @see http\Client\Request::setSslOptions()
         *
         * @return array
         */
        public function getSslOptions() { }

        /**
         * Set the MIME content type of the request message.
         *
         * @param string $content_type The MIME type used as “Content-Type”.
         *
         * @return Request
         *
         * @throws InvalidArgumentException
         * @throws UnexpectedValueException
         */
        public function setContentType($content_type) { }

        /**
         * Set client options.
         *
         * Request specific options override general options which were set in the client.
         * Note: Only options specified prior enqueueing a request are applied to the request.
         *
         * @see http\Client::setOptions()
         * @see http\Client\Curl
         *
         * @param array $options optional The options to set.
         *
         * @return Request
         *
         * @throws InvalidArgumentException
         */
        public function setOptions(array $options = null) { }

        /**
         * (Re)set the querystring.
         *
         * @see http\Client\Request::addQuery()
         * @see http\Message::setRequestUrl()
         *
         * @param string|array|QueryString $query_data optional New querystring data
         *
         * @return Request
         *
         * @throws InvalidArgumentException
         * @throws BadQueryStringException
         */
        public function setQuery($query_data = null) { }

        /**
         * Specifically set SSL options.
         *
         * @see http\Client\Request::setOptions()
         * @see http\Client\Curl::$ssl options
         *
         * @param array $ssl_options optional Set SSL options to this array.
         *
         * @return Request
         *
         * @throws InvalidArgumentException
         */
        public function setSslOptions(array $ssl_options = null) { }
    }

    /**
     * Response
     *
     * Helper autocomplete for pecl_http 2.x extension
     *
     * Represents an HTTP message the client returns as answer from a server to an http\Client\Request.
     *
     * @author N.V.
     */
    class Response extends Message
    {

        /**
         * Extract response cookies.
         * Parses any “Set-Cookie” response headers into an http\Cookie list.
         *
         * @see http\Cookie::__construct()
         *
         *
         * @param int   $flags optional Cookie parser flags.
         * @param array $allowed_extras optional List of keys treated as extras.
         *
         * @return Cookie[] Returns list of http\Cookie instances.
         */
        public function getCookies($flags = 0, array $allowed_extras = null) { }

        /**
         * Retrieve transfer related information after the request has completed.
         *
         * @see http\Client::getTransferInfo()
         *
         * @param string $name optional A key to retrieve out of the transfer info.
         *
         * @return mixed Returns instance with all transfer info if $name was not given or mixed, the specific transfer info for $name.
         *
         * @throws InvalidArgumentException
         * @throws BadMethodCallException
         * @throws UnexpectedValueException
         */
        public function getTransferInfo($name = null) { }
    }

    /**
     * Curl
     *
     * Helper autocomplete for pecl_http 2.x extension
     *
     * @link https://mdref.m6w6.name/http/Client/Curl
     *
     * This is not a real class of pecl_http 2.x extension and contains only options sets templates for different settings.
     * The option names used here are more or less derived from the corresponding CURLOPT_* names.
     *
     * @author N.V.
     */
    class Curl
    {

        /**
         * The HTTP protocol version. See http\Client\Curl\HTTP_VERSION_* constants.
         *
         * @var int
         */
        public static $protocol;

        /*********
         * PROXY *
         *********/

        /**
         * The hostname of the proxy.
         *
         * @var string
         */
        public static $proxyhost;

        /**
         * See http\Client\Curl\PROXY_* constants.
         *
         * @var int
         */
        public static $proxytype;

        /**
         * The port number of the proxy.
         *
         * @var int
         */
        public static $proxyport;

        /**
         * user:password
         *
         * @var string
         */
        public static $proxyauth;

        /**
         * See http\Client\Curl\AUTH_* constants.
         *
         * @var int
         */
        public static $proxyauthtype;

        /**
         * Tunnel all operations through the proxy.
         *
         * @var bool
         */
        public static $proxytunnel;

        /**
         * Comma separated list of hosts where no proxy should be used. Available if libcurl is v7.19.4 or more recent.
         *
         * @var string
         */
        public static $noproxy;

        /**
         * List of key/value pairs of headers which should only be sent to a proxy. Available if libcurl is v7.37.0 or more recent.
         *
         * @var array
         */
        public static $proxyheader;

        /*******
         * DNS *
         *******/

        /**
         * Resolved hosts will be kept fot this number of seconds.
         *
         * @var int
         */
        public static $dns_cache_timeout;

        /**
         * See http\Client\Curl\IPRESOLVE_* constants.
         *
         * @var int
         */
        public static $ipresolve;

        /**
         * A list of HOST:PORT:ADDRESS mappings which pre-populate the DNS cache. Available if libcurl is v7.21.3 or more recent.
         *
         * @var array
         */
        public static $resolve;

        /**
         * Comma separated list of custom DNS servers of the form HOST[:PORT]. Available if libcurl is v7.24.0 or more recent and has built-in c-ares support.
         *
         * @var string
         */
        public static $dns_servers;

        /**
         * The name of the network interface name that the DNS resolver should bind to. Available if libcurl is v7.33.0 or more recent and has built-in c-ares support.
         *
         * @var string
         */
        public static $dns_interface;

        /**
         * The local IPv4 address that the resolver should bind to. Available if libcurl is v7.33.0 or more recent and has built-in c-ares support.
         *
         * @var string
         */
        public static $dns_local_ip4;

        /**
         * The local IPv6 address that the resolver should bind to. Available if libcurl is v7.33.0 or more recent and has built-in c-ares support.
         *
         * @var string
         */
        public static $dns_local_ip6;

        /**********
         * Limits *
         **********/

        /**
         * Minimum speed in bytes per second.
         *
         * @var int
         */
        public static $low_speed_limit;

        /**
         * Maximum time in seconds the transfer can be below $low_speed_limit before cancelling.
         *
         * @var int
         */
        public static $low_speed_time;

        /**
         * Maximum download size.
         *
         * @var int
         */
        public static $maxfilesize;

        /***********************
         * Connection handling *
         ***********************/

        /**
         * Force a new connection.
         *
         * @var bool
         */
        public static $fresh_connect;

        /**
         * Force closing the connection.
         *
         * @var bool
         */
        public static $forbid_reuse;

        /**************
         * Networking *
         **************/

        /**
         * Outgoing interface name.
         *
         * @var string
         */
        public static $interface;

        /**
         * A tuple of min/max ports.
         *
         * @var array
         */
        public static $portrange;

        /**
         * Override the URL’s port.
         *
         * @var int
         */
        public static $port;

        /**
         * RFC4007 zone_id. Available if libcurl is v7.19.0 or more recent.
         *
         * @var int
         */
        public static $address_scope;

        /**
         * Whether to use TCP keepalive. Available if libcurl is v7.25.0 or more recent.
         *
         * @var bool
         */
        public static $tcp_keepalive;

        /**
         * Seconds to wait before sending keepalive probes. Available if libcurl is v7.25.0 or more recent.
         *
         * @var int
         */
        public static $tcp_keepidle;

        /**
         * Interval in seconds to wait between sending keepalive probes. Available if libcurl is v7.25.0 or more recent.
         *
         * @var int
         */
        public static $tcp_keepintvl;

        /**
         * Disable Nagle’s algotrithm.
         *
         * @var bool
         */
        public static $tcp_nodelay;

        /**
         * Connect to the webserver listening at $unix_socket_path instead of opening a TCP connection to it. Available if libcurl is v7.40.0 or more recent.
         *
         * @var string
         */
        public static $unix_socket_path;

        /******************
         * Authentication *
         ******************/

        /**
         * user:password
         *
         * @var string
         */
        public static $httpauth;

        /**
         * See http\Client\Curl\AUTH_* constants.
         *
         * @var int
         */
        public static $httpauthtype;

        /***************
         * Redirection *
         ***************/

        /**
         * How many redirects to follow.
         *
         * @var int
         */
        public static $redirect;

        /**
         * Whether to keep sending authentication credentials on redirects to different hosts.
         *
         * @var bool
         */
        public static $unrestricted_auth;

        /**
         * See http\Client\Curl\POSTREDIR_* constants. Available if libcurl is v7.19.1 or more recent.
         *
         * @var int
         */
        public static $postredir;

        /***********
         * Retries *
         ***********/

        /**
         * Retry this often.
         *
         * @var int
         */
        public static $retrycount;

        /**
         * Pause this number of seconds between retries.
         *
         * @var float
         */
        public static $retrydelay;

        /*******************
         * Special headers *
         *******************/

        /**
         * Custom Referer header.
         *
         * @var string
         */
        public static $referer;

        /**
         * Whether to automatically send referers.
         *
         * @var bool
         */
        public static $autoreferer;

        /**
         * Custom User-Agent header.
         *
         * @var string
         */
        public static $useragent;

        /**
         * Custom ETag.
         *
         * @var string
         */
        public static $etag;

        /**
         * Whether to request compressed content (through Accept-Encoding).
         *
         * @var bool
         */
        public static $compress;

        /**
         * Custom If-(Un)Modified since time. If less than zero, the current time will be added.
         *
         * @var int
         */
        public static $lastmodified;

        /*****************
         * Resume/Ranges *
         *****************/

        /**
         * Resume from this byte offset.
         *
         * @var int
         */
        public static $resume;

        /**
         * Fetch specific ranges (if server supports byte ranges).
         *
         * @var array
         */
        public static $range;

        /***********
         * Cookies *
         ***********/

        /**
         * Whether to URLencode cookies.
         *
         * @var bool
         */
        public static $encodecookies;

        /**
         * List of custom cookies in the form [“name” => “value”].
         *
         * @var array
         */
        public static $cookies;

        /**
         * Ignore previous session cookies to be loaded from $cookiestore.
         *
         * @var bool
         */
        public static $cookiesession;

        /**
         * Path to a Netscape cookie file, from which cookies will be loaded resp. to which cookies will be written.
         *
         * @var string
         */
        public static $cookiestore;

        /************
         * Timeouts *
         ************/

        /**
         * Seconds the complete transfer may take.
         *
         * @var float
         */
        public static $timeout;

        /**
         * Seconds the connect may take.
         *
         * @var float
         */
        public static $connecttimeout;

        /**
         * Senconds to wait for the server to send a response to “Expect: 100-Continue” before just proceeding with the request. Available if libcurl is v7.36.0 or more recent.
         *
         * @var float
         */
        public static $expect_100_timeout;

        /*******
         * SSL *
         *******/

        /**
         * Subarray of SSL related options:
         *
         * @var array
         */
        public static $ssl;

        /***************
         * SSL options *
         ***************/

        /**
         * SSL certificate file.
         *
         * @var string
         */
        public static $cert;

        /**
         * Certificate type (DER, PEM). (Secure Transport additionally supports P12).
         *
         * @var string
         */
        public static $certtype;

        /**
         * Private key file.
         *
         * @var string
         */
        public static $key;

        /**
         * PK type (PEM, DER, ENG).
         *
         * @var string
         */
        public static $keytype;

        /**
         * The password for the private key.
         *
         * @var string
         */
        public static $keypasswd;

        /**
         * Crypto engine to use for the private key.
         *
         * @var string
         */
        public static $engine;

        /**
         * See http\Client\Curl\SSL_VERSION_* constants.
         * @var int
         */
        public static $version;

        /**
         * Whether to apply peer verification.
         * @var bool
         */
        public static $verifypeer;

        /**
         * Whether to apply host verification.
         * @var bool
         */
        public static $verifyhost;

        /**
         * One or more cipher strings separated by colons.
         * @var string
         */
        public static $cipher_list;

        /**
         * CA bundle to verify the peer with.
         * @var string
         */
        public static $cainfo;

        /**
         * Directory with prepared CA certs to verify the peer with.
         * @var string
         */
        public static $capath;

        /**
         * A file used to read from to seed the random engine.
         * @var string
         */
        public static $random_file;

        /**
         * A Entropy Gathering Daemon socket.
         * @var string
         */
        public static $egdsocket;
        /**
         * CA PEM cert for peer verification. Available if libcurl is v7.19.0 or more recent.
         * @var string
         */
        public static $issuercert;

        /**
         * File with the concatenation of CRL in PEM format. Available if libcurl was built with OpenSSL support.
         * @var string
         */
        public static $crlfile;

        /**
         * Enable gathering of SSL certificate chain information. Available if libcurl is v7.19.1 or more recent.
         * @var bool
         */
        public static $certinfo;

        /**
         * File with a public key to pin. Available if libcurl is v7.39.0 or more recent.
         * @var string
         */
        public static $pinned_publickey;

        /**
         * TLS_AUTH_* constant. Available if libcurl is v7.21.4 or more recent.
         * @var int
         */
        public static $tlsauthtype;

        /**
         * TLS-SRP username. Available if libcurl is v7.21.4 or more recent.
         * @var string
         */
        public static $tlsauthuser;

        /**
         * TLS-SRP password. Available if libcurl is v7.21.4 or more recent.
         * @var string
         */
        public static $lsauthpass;

        /**
         * Enable OCSP. Available if libcurl is v7.41.0 or more recent and was build with openssl, gnutls or nss support.
         * @var bool
         */
        public static $verifystatus;

        /*****************
         * Configuration *
         *****************/

        /**
         * Size of the connection cache.
         *
         * @var int
         */
        public static $maxconnects;

        /**
         * Maximum number of connections to a single host. Available if libcurl is v7.30.0 or more recent.
         *
         * @var int
         */
        public static $max_host_connections;

        /**
         * Maximum number of requests in a pipeline. Available if libcurl is v7.30.0 or more recent.
         *
         * @var int
         */
        public static $max_pipeline_length;

        /**
         * Maximum number of simultaneous open connections of this client. Available if libcurl is v7.30.0 or more recent.
         *
         * @var int
         */
        public static $max_total_connections;

        /**
         * Whether to enable HTTP/1.1 pipelining.
         *
         * @var bool
         */
        public static $pipelining;

        /**
         * Chunk length threshold for pipelining; no more requests on this pipeline if exceeded. Available if libcurl is v7.30.0 or more recent.
         *
         * @var int
         */
        public static $chunk_length_penalty_size;

        /**
         * Size threshold for pipelining; no more requests on this pipeline if exceeded. Available if libcurl is v7.30.0 or more recent.
         *
         * @var int
         */
        public static $content_length_penalty_size;

        /**
         * Simple list of server software names to blacklist for pipelining. Available if libcurl is v7.30.0 or more recent.
         *
         * @var array
         */
        public static $pipelining_server_bl;

        /**
         * Simple list of server host names to blacklist for pipelining. Available if libcurl is v7.30.0 or more recent.
         *
         * @var array
         */
        public static $pipelining_site_bl;

        /**
         * Whether to use an event loop. Available if pecl/http was built with libevent support.
         *
         * @var bool
         */
        public static $use_eventloop;
    }
}

namespace http\Exception
{
    use DomainException;
    use http\Exception;

    /**
     * BadConversionException
     *
     * Helper autocomplete for pecl_http 2.x extension
     *
     * A bad conversion (e.g. character conversion) was encountered.
     *
     * @author N.V.
     */
    class BadConversionException extends DomainException implements Exception { }

    /**
     * BadConversionException
     *
     * Helper autocomplete for pecl_http 2.x extension
     *
     * A bad HTTP header was encountered.
     *
     * @author N.V.
     */
    class BadHeaderException extends DomainException implements Exception { }

    /**
     * BadConversionException
     *
     * Helper autocomplete for pecl_http 2.x extension
     *
     * A bad HTTP message was encountered.
     *
     * @author N.V.
     */
    class BadMessageException extends DomainException implements Exception { }

    /**
     * BadConversionException
     *
     * Helper autocomplete for pecl_http 2.x extension
     *
     * A method was called on an object, which was in an invalid or unexpected state.
     *
     * @author N.V.
     */
    class BadMethodCallException extends \BadMethodCallException implements Exception { }

    /**
     * BadConversionException
     *
     * Helper autocomplete for pecl_http 2.x extension
     *
     * A bad querystring was encountered.
     *
     * @author N.V.
     */
    class BadQueryStringException extends DomainException implements Exception { }

    /**
     * BadUrlException
     *
     * Helper autocomplete for pecl_http 2.x extension
     *
     * A bad HTTP URL was encountered.
     *
     * @author N.V.
     */
    class BadUrlException extends DomainException implements Exception { }

    /**
     * InvalidArgumentException
     *
     * Helper autocomplete for pecl_http 2.x extension
     *
     * One or more invalid arguments were passed to a method.
     *
     * @author N.V.
     */
    class InvalidArgumentException extends \InvalidArgumentException implements Exception { }

    /**
     * RuntimeException
     *
     * Helper autocomplete for pecl_http 2.x extension
     *
     * A generic runtime exception.
     *
     * @author N.V.
     */
    class RuntimeException extends \RuntimeException implements Exception { }

    /**
     * UnexpectedValueException
     *
     * Helper autocomplete for pecl_http 2.x extension
     *
     * An unexpected value was encountered.
     *
     * @author N.V.
     */
    class UnexpectedValueException extends \UnexpectedValueException implements Exception { }
}

namespace http\Message
{

    use http\Exception\InvalidArgumentException;
    use http\Exception\RuntimeException;
    use http\Exception\UnexpectedValueException;
    use http\Message;
    use Serializable;

    /**
     * Body
     *
     * Helper autocomplete for pecl_http 2.x extension
     *
     * The message body, represented as a PHP (temporary) stream.
     * Note: Currently, http\Message\Body::addForm() creates multipart/form-data bodies.
     *
     * @author N.V.
     */
    class Body implements Serializable
    {

        /**
         * Create a new message body, optionally referencing $stream.
         *
         * @param resource $stream optional A stream to be used as message body.
         *
         * @throws InvalidArgumentException
         * @throws UnexpectedValueException
         */
        public function __construct($stream = null) { }

        /**
         * String cast handler.
         *
         * @return string Returns the message body.
         */
        public function __toString() { }

        /**
         * Add form fields and files to the message body.
         * Note: Currently, http\Message\Body::addForm() creates “multipart/form-data” bodies.
         *
         * $fields must look like:
         *  [
         *      "field_name" => "value",
         *      "multi_field" => [
         *          "value1",
         *          "value2"
         *      ]
         *  ]
         *
         * $files must look like:

         *  [
         *      [
         *          "name" => "field_name",
         *          "type" => "content/type",
         *          "file" => "/path/to/file.ext"
         *      ],
         *      [
         *          "name" => "field_name2",
         *          "type" => "text/plain",
         *          "file" => "file.ext",
         *          "data" => "string"
         *      ],
         *      [
         *          "name" => "field_name3",
         *          "type" => "image/jpeg",
         *          "file" => "file.ext",
         *          "data" => fopen("/home/mike/Pictures/mike.jpg","r")
         *      ]
         *  ]
         *
         * As you can see, a file structure must contain a “file” entry, which holds a file path, and an optional “data” entry, which may either contain a resource to read from or the actual data as string.
         *
         * @param array $fields optional List of form fields to add.
         * @param array $files optional List of form files to add.
         *
         * @return Body
         *
         * @throws InvalidArgumentException
         * @throws RuntimeException
         */
        public function addForm(array $fields = null, array $files = null) { }

        /**
         * Add a part to a multipart body.
         *
         * @param Message $part The message part.
         *
         * @return Body
         *
         * @throws InvalidArgumentException
         * @throws RuntimeException
         */
        public function addPart(Message $part) { }

        /**
         * Append plain bytes to the message body.
         *
         * @param string $data The data to append to the body.
         *
         * @return Body Returns self.
         *
         * @throws InvalidArgumentException
         * @throws RuntimeException
         */
        public function append($data) { }

        /**
         * Retrieve the ETag of the body.
         *
         * @return string|bool Returns an Apache style ETag of inode, mtime and size in hex concatenated by hyphens if the message body stream is stat-able
         *                     or a content hash (which algorithm is determined by INI http.etag.mode) if the stream is not stat-able
         *                     or false, if http.etag.mode is not a known hash algorithm.
         */
        public function etag() { }

        /**
         * Retrieve any boundary of the message body.
         *
         * @return string Returns the message body boundary or NULL, if this message body has no boundary.
         */
        public function getBoundary() { }

        /**
         * Retrieve the underlying stream resource.
         *
         * @return resource Returns the underlying stream.
         */
        public function getResource() { }

        /**
         * {@inheritdoc}
         * @see Serializable::serialize()
         */
        public function serialize() { }

        /**
         * Stat size, atime, mtime and/or ctime.
         *
         * @param string $field optional A single stat field to retrieve.
         *
         * @return int|object Returns the requested stat field or stdClass instance holding all four stat fields.
         */
        public function stat($field = null) { }

        /**
         * Stream the message body through a callback.
         *
         * @param callable $callback The callback of the form function(http\Message\Body $from, string $data).
         * @param int      $offset optional Start to stream from this offset.
         * @param int      $maxlen optional Stream at most $maxlen bytes, or all if $maxlen is less than 1.
         *
         * @return Body
         */
        public function toCallback(callable $callback, $offset = 0, $maxlen = 0) { }

        /**
         * Stream the message body into antother stream $stream, starting from $offset, streaming $maxlen at most.
         *
         * @param resource $stream The resource to write to.
         * @param int $offset optional The starting offset.
         * @param int $maxlen optional The maximum amount of data to stream. All content if less than 1.
         *
         * @return Body
         */
        public function toStream($stream, $offset = 0, $maxlen = 0) { }

        /**
         * Retrieve the message body serialized to a string.
         *
         * @return string Returns message body.
         */
        public function toString() { }

        /**
         * {@inheritdoc}
         * @see Serializable::unserialize()
         */
        public function unserialize($serialized) { }
    }

    /**
     * Parser
     *
     * Helper autocomplete for pecl_http 2.x extension
     *
     * The parser which is underlying http\Message.
     *
     * @author N.V.
     */
    class Parser
    {

        /**
         * Finish up parser at end of (incomplete) input.
         */
        const CLEANUP = 1;

        /**
         * Soak up the rest of input if no entity length is deducible.
         */
        const DUMB_BODIES = 2;

        /**
         * Redirect messages do not contain any body despite of indication of such.
         */
        const EMPTY_REDIRECTS = 4;

        /**
         * Continue parsing while input is available.
         */
        const GREEDY = 8;

        /**
         * Parse failure.
         */
        const STATE_FAILURE = -1;

        /**
         * Expecting HTTP info (request/response line) or headers.
         */
        const STATE_START = 0;

        /**
         * Parsing headers.
         */
        const STATE_HEADER = 1;

        /**
         * Completed parsing headers.
         */
        const STATE_HEADER_DONE = 2;

        /**
         * Parsing the body.
         */
        const STATE_BODY = 3;

        /**
         * Soaking up all input as body.
         */
        const STATE_BODY_DUMB = 4;

        /**
         * Reading body as indicated by Content-Lenght or Content-Range.
         */
        const STATE_BODY_LENGTH = 5;

        /**
         * Parsing chunked encoded body.
         */
        const STATE_BODY_CHUNKED = 6;

        /**
         * Finished parsing the body.
         */
        const STATE_BODY_DONE = 7;

        const STATE_UPDATE_CL = 8;

        /**
         * Finished parsing the message.
         */
        const STATE_DONE = 9;

        /**
         * Retrieve the current state of the parser.
         *
         * @return int Returns http\Message\Parser::STATE_* constant.
         *
         * @throws InvalidArgumentException
         */
        public function getState() { }

        /**
         * Parse a string.
         *
         * @param string $data The (part of the) message to parse.
         * @param int $flags Any combination of parser flags.
         * @param \http\Message $message The current state of the message parsed.
         *
         * @return int Returns http\Message\Parser::STATE_* constant.
         *
         * @throws InvalidArgumentException
         */
        public function parse($data, $flags, Message &$message) { }

        /**
         * Parse a stream.
         *
         * @param resource $stream The message stream to parse from.
         * @param int $flags Any combination of parser flags.
         * @param \http\Message $message The current state of the message parsed.
         *
         * @return int Returns http\Message\Parser::STATE_* constant.
         *
         * @throws InvalidArgumentException
         * @throws UnexpectedValueException
         */
        public function stream($stream, $flags, Message &$message) { }
    }
}

namespace http\Header
{

    use http\Exception\InvalidArgumentException;
    use http\Exception\UnexpectedValueException;

    /**
     * Parser
     *
     * Helper autocomplete for pecl_http 2.x extension
     *
     * The parser which is underlying http\Header and http\Message.
     *
     * @author N.V.
     */
    class Parser {

        /**
         * Finish up parser at end of (incomplete) input.
         */
        const CLEANUP = 1;

        /**
         * Parse failure.
         */
        const STATE_FAILURE = -1;

        /**
         * Expecting HTTP info (request/response line) or headers.
         */
        const STATE_START = 0;

        /**
         * Expecting a key or already parsing a key.
         */
        const STATE_KEY = 1;

        /**
         * Expecting a value or already parsing the value.
         */
        const STATE_VALUE = 2;

        /**
         * At EOL of an header, checking whether a folded header line follows.
         */
        const STATE_VALUE_EX = 3;

        /**
         * A header was completed.
         */
        const STATE_HEADER_DONE = 4;

        /**
         * Finished parsing the headers.
         */
        const STATE_DONE = 5;

        /**
         * Retrieve the current state of the parser.
         *
         * @see Parser::STATE_* constants.
         *
         * @return  int Returns Parser::STATE_* constant.
         *
         * @throws InvalidArgumentException
         */
        public function getState() { }

        /**
         * Parse a string.
         *
         * @param   string  $data                The (part of the) header to parse.
         * @param   int     $flags               Any combination of parser flags.
         * @param   array   $message    optional Successfully parsed headers.
         *
         * @return  int Returns Parser::STATE_* constant.
         *
         * @throws InvalidArgumentException
         */
        public function parse($data, $flags, array &$message = NULL) { }

        /**
         * Parse a stream.
         *
         * @param   resource    $stream     The header stream to parse from.
         * @param   int         $flags      Any combination of parser flags.
         * @param   array       $headers    The headers parsed.
         *
         * @return  int Returns Parser::STATE_* constant.
         *
         * @throws InvalidArgumentException
         * @throws UnexpectedValueException
         */
        public function stream($stream, $flags, array &$headers) { }
    }
}

namespace http\Env
{

    use http\Exception\InvalidArgumentException;
    use http\Exception\UnexpectedValueException;
    use http\Message;
    use http\QueryString;

    /**
     * Request
     *
     * Helper autocomplete for pecl_http 2.x extension
     *
     * The class' instances represent the server’s current HTTP request.
     *
     * @see http\Message for inherited members.
     *
     * @author N.V.
     */
    class Request extends Message
    {

        /**
         * The request’s query parameters. ($_GET)
         * @var QueryString
         */
        protected $query = null;

        /**
         * The request’s form parameters. ($_POST)
         * @var QueryString
         */
        protected $form = null;

        /**
         * The request’s form uploads. ($_FILES)
         * @var array
         */
        protected $files = null;

        /**
         * The request’s cookies. ($_COOKIE)
         * @var array
         */
        protected $cookie = null;

        /**
         * Create an instance of the server’s current HTTP request.
         * Upon construction, the http\Env\Request acquires http\QueryString instances of query paramters ($_GET) and form parameters ($_POST).
         * It also compiles an array of uploaded files ($_FILES) more comprehensive than the original $_FILES array, see http\Env\Request::getFiles() for that matter.
         *
         * @throws InvalidArgumentException
         * @throws UnexpectedValueException
         */
        public function __construct() { }

        /**
         * Retrieve an URL query value ($_GET).
         *
         * @see http\QueryString::get() and http\QueryString::TYPE_* constants.
         *
         * @param string $name optional The key to retrieve the value for.
         * @param mixed $type optional The type to cast the value to. See http\QueryString::TYPE_* constants.
         * @param mixed $defval optional The default value to return if the key $name does not exist.
         * @param bool $delete optional Whether to delete the entry from the querystring after retrieval.
         *
         * @return QueryString|string|mixed Returns
         *         http\QueryString, if called without arguments,
         *         string, the whole querystring if $name is of zero length.
         *         mixed, $defval if the key $name does not exist.
         *         mixed, the querystring value cast to $type if $type was specified and the key $name exists.
         *         string, the querystring value if the key $name exists and $type is not specified or equals http\QueryString::TYPE_STRING.
         */
        public function getCookie($name = null, $type = null, $defval = null, $delete = false) { }

        /**
         * Retrieve the uploaded files list ($_FILES).
         *
         * @return array Returns the consolidated upload files array.
         */
        public function getFiles() { }

        /**
         * Retrieve a form value ($_POST).
         *
         * @see http\QueryString::get() and http\QueryString::TYPE_* constants.
         *
         * @param string $name optional The key to retrieve the value for.
         * @param mixed $type optional The type to cast the value to. See http\QueryString::TYPE_* constants.
         * @param mixed $defval optional The default value to return if the key $name does not exist.
         * @param bool $delete optional Whether to delete the entry from the querystring after retrieval.
         *
         * @return QueryString|string|mixed Returns
         *         http\QueryString, if called without arguments,
         *         string, the whole querystring if $name is of zero length.
         *         mixed, $defval if the key $name does not exist.
         *         mixed, the querystring value cast to $type if $type was specified and the key $name exists.
         *         string, the querystring value if the key $name exists and $type is not specified or equals http\QueryString::TYPE_STRING.
         */
        public function getForm($name = null, $type = null, $defval = null, $delete = false) { }

        /**
         * Retrieve an URL query value ($_GET).
         *
         * @see http\QueryString::get() and http\QueryString::TYPE_* constants.
         *
         * @param string $name optional The key to retrieve the value for.
         * @param mixed $type optional The type to cast the value to. See http\QueryString::TYPE_* constants.
         * @param mixed $defval optional The default value to return if the key $name does not exist.
         * @param bool $delete optional Whether to delete the entry from the querystring after retrieval.
         *
         * @return QueryString|string|mixed Returns
         *         http\QueryString, if called without arguments,
         *         string, the whole querystring if $name is of zero length.
         *         mixed, $defval if the key $name does not exist.
         *         mixed, the querystring value cast to $type if $type was specified and the key $name exists.
         *         string, the querystring value if the key $name exists and $type is not specified or equals http\QueryString::TYPE_STRING.
         */
        public function getQuery($name = null,  $type = null,  $defval = null,  $delete = false) { }
    }

    /**
     * Response
     *
     * Helper autocomplete for pecl_http 2.x extension
     *
     * The class' instances represent the server’s current HTTP response.
     *
     * @see http\Message for inherited members.
     *
     * @author N.V.
     */
    class Response extends Message
    {

        /**
         * Do not use content encoding.
         */
        const CONTENT_ENCODING_NONE = 0;

        /**
         * Support “Accept-Encoding” requests with gzip and deflate encoding.
         */
        const CONTENT_ENCODING_GZIP = 1;

        /**
         * No caching info available.
         */
        const CACHE_NO = 0;

        /**
         * The cache was hit.
         */
        const CACHE_HIT = 1;

        /**
         * The cache was missed.
         */
        const CACHE_MISS = 2;

        /**
         * A request instance which overrides the environments default request.
         *
         * @var Request
         */
        protected $request = null;

        /**
         * The response’s MIME content type.
         *
         * @var string
         */
        protected $contentType = null;

        /**
         * The response’s MIME content disposition.
         *
         * @var string
         */
        protected $contentDisposition = null;

        /**
         * See http\Env\Response::CONTENT_ENCODING_* constants.
         *
         * @var int
         */
        protected $contentEncoding = null;

        /**
         * How the client should treat this response in regards to caching.
         *
         * @var string
         */
        protected $cacheControl = null;

        /**
         * A custom ETag.
         *
         * @var string
         */
        protected $etag = null;

        /**
         * A “Last-Modified” time stamp.
         *
         * @var int
         */
        protected $lastModified = null;

        /**
         * Any throttling delay.
         *
         * @var int
         */
        protected $throttleDelay = null;

        /**
         * The chunk to send every $throttleDelay seconds.
         *
         * @var int
         */
        protected $throttleChunk = null;

        /**
         * The response’s cookies.
         *
         * @var array
         */
        protected $cookies = null;

        /**
         * Create a new env response message instance.
         *
         * @throws InvalidArgumentException
         * @throws UnexpectedValueException
         */
        public function __construct() { }

        /**
         * Output buffer handler.
         *
         * @param string $data The data output.
         * @param int $ob_flags optional Output buffering flags passed from the output buffering control layer.
         *
         * @return bool Returns success.
         */
        public function __invoke($data, $ob_flags = 0) { }

        /**
         * Manually test the header $header_name of the environment’s request for a cache hit.
         * http\Env\Response::send() checks that itself, though.
         *
         * @param string $header_name optional The request header to test.
         *
         * @return int Returns a http\Env\Response::CACHE_* constant.
         */
        public function isCachedByEtag($header_name = "If-None-Match") { }

        /**
         * Manually test the header $header_name of the environment’s request for a cache hit.
         * http\Env\Response::send() checks that itself, though.
         *
         * @param string $header_name optional The request header to test.
         *
         * @return int Returns a http\Env\Response::CACHE_* constant.
         */
        public function isCachedByLastModified($header_name = "If-Modified-Since") { }

        /**
         * Send the response through the SAPI or $stream.
         *
         * @param resource $stream optional A writable stream to send the response through.
         *
         * @return bool Returns success.
         */
        public function send($stream = null) { }

        /**
         * Make suggestions to the client how it should cache the response.
         *
         * @param string $cache_control A “Cache-Control” header value(s).
         *
         * @return Response Returns self.
         *
         * @throws InvalidArgumentException
         */
        public function setCacheControl($cache_control) { }

        /**
         * Set the reponse’s content disposition parameters.
         *
         * @param array $disposition_params MIME content disposition as http\Params array.
         *
         * @return Response Returns self.
         *
         * @throws InvalidArgumentException
         */
        public function setContentDisposition(array $disposition_params) { }

        /**
         * Enable support for “Accept-Encoding” requests with deflate or gzip.
         * The response will be compressed if the client indicates support and wishes that.
         *
         * @param int $content_encoding See http\Env\Response::CONTENT_ENCODING_* constants.
         *
         * @return Response Returns self.
         *
         * @throws InvalidArgumentException
         */
        public function setContentEncoding($content_encoding) { }

        /**
         * Set the MIME content type of the response.
         *
         * @param string $content_type The response’s content type.
         *
         * @return Response Returns self.
         *
         * @throws InvalidArgumentException
         */
        public function setContentType($content_type) { }

        /**
         * Add cookies to the response to send.
         *
         * @param mixed $cookie The cookie to send.
         *
         * @return Response Returns self.
         *
         * @throws InvalidArgumentException
         * @throws UnexpectedValueException
         */
        public function setCookie($cookie) { }

        /**
         * Override the environment’s request.
         *
         * @param Message $env_request The overriding request message.
         *
         * @return Response Returns self.
         *
         * @throws InvalidArgumentException
         */
        public function setEnvRequest(Message $env_request) { }

        /**
         * Set a custom ETag.
         * Note: This will be used for caching and pre-condition checks.
         *
         * @param string $etag A ETag.
         *
         * @return Response Returns self.
         *
         * @throws InvalidArgumentException
         */
        public function setEtag($etag) { }

        /**
         * Set a custom last modified time stamp.
         * Note: This will be used for caching and pre-condition checks.
         *
         * @param int $last_modified A unix timestamp.
         *
         * @return Response Returns self.
         *
         * @throws InvalidArgumentException
         */
        public function setLastModified($last_modified) { }

        /**
         * Enable throttling.
         * Send $chunk_size bytes every $delay seconds.
         * Note: If you need throttling by regular means, check for other options in your stack, because this method blocks the executing process/thread until the response has completely been sent.
         *
         * @param int $chunk_size Bytes to send.
         * @param float $delay optional Seconds to sleep.
         *
         * @return Response Returns self.
         *
         * @throws InvalidArgumentException
         */
        public function setThrottleRate($chunk_size, $delay = 1.0) { }
    }
}

namespace http\Encoding
{

    class Stream { }
}

namespace http\Encoding\Stream
{

    class Dechunk { }

    class Deflate { }

    class Inflate { }
}