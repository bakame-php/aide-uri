<?php

/**
 * Aide.Uri (https://https://github.com/bakame-php/aide-uri)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bakame\Polyfill\Rfc3986;

use Exception;
use League\Uri\Exceptions\SyntaxError;
use League\Uri\UriString;
use SensitiveParameter;
use Throwable;

use function explode;
use function preg_match;
use function str_contains;
use function str_starts_with;
use function strpos;
use function substr;

/**
 * @phpstan-type ComponentMap array{scheme: ?string, user: ?string, pass: ?string, host: ?string, port: ?int, path: ?string, query: ?string, fragment: ?string}
 * @phpstan-type Components array{scheme: ?string, userInfo: ?string, user: ?string, pass: ?string, host: ?string, port: ?int, path: ?string, query: ?string, fragment: ?string}
 */
final class Uri
{
    private const TYPE_RAW = 'raw';
    private const TYPE_NORMALIZED = 'normalized';
    private const REGEXP_VALID_SCHEME     = '/^[A-Za-z]([-A-Za-z\d+.]+)?$/';
    private const REGEXP_INVALID_USERINFO = '/[^A-Za-z0-9\-._~!$&\'()*+,;=:%]|%(?![0-9A-Fa-f]{2})/';
    private const REGEXP_INVALID_PATH     = '/[^A-Za-z0-9\-._~!$&\'()*+,;=:@\/%]|%(?![0-9A-Fa-f]{2})/';
    private const REGEXP_INVALID_QUERY    = '/[^A-Za-z0-9\-._~!$&\'()*+,;=\/?%]|%(?![0-9A-Fa-f]{2})/';
    private const REGEXP_INVALID_FRAGMENT = '/[^A-Za-z0-9\-._~!$&\'()*+,;=:@\/?%]|%(?![0-9A-Fa-f]{2})/';

    /** @var Components */
    private readonly array $rawComponents;
    /** @var Components */
    private readonly array $normalizedComponents;
    private bool $isInitialized = false;
    private ?string $rawUri = null;
    private ?string $normalizedUri = null;

    public static function parse(string $uri, ?string $baseUri = null): ?Uri
    {
        try {
            return new self($uri, $baseUri);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @throws InvalidUriException
     */
    public function __construct(string $uri, ?string $baseUri = null)
    {
        try {
            $uri = null !== $baseUri ? UriString::resolve($uri, $baseUri) : $uri;
            $components = $this->uriSplit($uri);
        } catch (Exception $exception) {
            throw new InvalidUriException($exception->getMessage());
        }

        $this->rawComponents = self::validateComponents($components);
        $this->normalizedComponents = $this->uriSplit(UriString::normalize($uri));
        $this->isInitialized = true;
    }

    /**
     * Split the URI into its own component following RFC3986 rules.
     *
     * @link https://tools.ietf.org/html/rfc3986
     *
     * @param string $uri The URI string to parse
     *
     * @throws SyntaxError
     *
     * @return Components
     */
    private function uriSplit(string $uri): array
    {
        /** @var Components $defaults */
        static $defaults = [
            'scheme' => null, 'userInfo' => null, 'user' => null, 'pass' => null, 'host' => null,
            'port' => null, 'path' => null, 'query' => null, 'fragment' => null,
        ];

        $components = [...$defaults, ...UriString::parse($uri)];
        if ('' === $components['path']) {
            $components['path'] = null;
        }

        if (null === $components['user']) {
            return $components;
        }

        $components['userInfo'] = $components['user'];
        if (null === $components['pass']) {
            return $components;
        }

        $components['userInfo'] .= ':'.$components['pass'];

        return $components;
    }

    /**
     * Assert the URI internal state is valid.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3
     * @link https://tools.ietf.org/html/rfc3986#section-3.3
     *
     * @param Components $components
     *
     * @throws InvalidUriException
     *
     * @return Components
     */
    private static function validateComponents(array $components): array
    {
        $authority = UriString::buildAuthority($components);
        $path = $components['path'];

        if (null !== $authority) {
            if (null !== $path && '' !== $path && '/' !== $path[0]) {
                throw new InvalidUriException('If an authority is present the path must be empty or start with a `/`.');
            }

            return $components;
        }

        if (null === $path || '' === $path) {
            return $components;
        }

        if (str_starts_with($path, '//')) {
            throw new InvalidUriException('If there is no authority the path `'.$path.'` cannot start with a `//`.');
        }

        if (null !== $components['scheme'] || false === ($pos = strpos($path, ':'))) {
            return $components;
        }

        if (!str_contains(substr($path, 0, $pos), '/')) {
            throw new InvalidUriException('In absence of a scheme and an authority the first path segment cannot contain a colon (":") character.');
        }

        return $components;
    }

    /**
     * @throws UninitializedUriError
     */
    private function assertIsInitialized(): void
    {
        $this->isInitialized || throw new UninitializedUriError('Object of type '.self::class.' has not been correctly initialized.');
    }

    /**
     * @param self::TYPE_RAW|self::TYPE_NORMALIZED $type
     *
     * @throws UninitializedUriError
     */
    private function getComponent(string $name, string $type): ?string
    {
        self::assertIsInitialized();
        $value = (self::TYPE_RAW === $type ? $this->rawComponents : $this->normalizedComponents)[$name];
        if (null !== $value) {
            $value = (string)$value;
        }

        return $value;
    }

    /**
     * @throws UninitializedUriError
     */
    public function getScheme(): ?string
    {
        return $this->getComponent('scheme', self::TYPE_NORMALIZED);
    }

    /**
     * @throws UninitializedUriError
     */
    public function getRawScheme(): ?string
    {
        return $this->getComponent('scheme', self::TYPE_RAW);
    }

    /**
     * @throws UninitializedUriError|InvalidUriException
     */
    public function withScheme(?string $encodedScheme): self
    {
        return match (true) {
            $encodedScheme === $this->getRawScheme() => $this,
            null !== $encodedScheme && 1 !== preg_match(self::REGEXP_VALID_SCHEME, $encodedScheme) => throw new InvalidUriException('The scheme string component `'.$encodedScheme.'` is an invalid scheme.'),
            default => new self(UriString::build([...$this->rawComponents, ...['scheme' => $encodedScheme]])),
        };
    }

    /**
     * @throws UninitializedUriError
     */
    public function getUserInfo(): ?string
    {
        return $this->getComponent('userInfo', self::TYPE_NORMALIZED);
    }

    /**
     * @throws UninitializedUriError
     */
    public function getRawUserInfo(): ?string
    {
        return $this->getComponent('userInfo', self::TYPE_RAW);
    }

    /**
     * @throws UninitializedUriError|InvalidUriException
     */
    public function withUserInfo(#[SensitiveParameter] ?string $encodedUserInfo): self
    {
        if ($encodedUserInfo === $this->getRawUserInfo()) {
            return $this;
        }

        if (null !== $encodedUserInfo && 1 === preg_match(self::REGEXP_INVALID_USERINFO, $encodedUserInfo)) {
            throw new InvalidUriException('The encoded userInfo string component contains invalid characters.');
        }

        [$user, $password] = explode(':', $encodedUserInfo, 2) + [1 => null]; /* @phpstan-ignore-line */

        return new self(UriString::build(self::validateComponents([...$this->rawComponents, ...['user' => $user, 'password' => $password]])));
    }

    /**
     * @throws UninitializedUriError
     */
    public function getRawUser(): ?string
    {
        return $this->getComponent('user', self::TYPE_RAW);
    }

    /**
     * @throws UninitializedUriError
     */
    public function getUser(): ?string
    {
        return $this->getComponent('user', self::TYPE_NORMALIZED);
    }

    /**
     * @throws UninitializedUriError
     */
    public function getRawPassword(): ?string
    {
        return $this->getComponent('pass', self::TYPE_RAW);
    }

    /**
     * @throws UninitializedUriError
     */
    public function getPassword(): ?string
    {
        return $this->getComponent('pass', self::TYPE_NORMALIZED);
    }

    /**
     * @throws UninitializedUriError
     */
    public function getRawHost(): ?string
    {
        return $this->getComponent('host', self::TYPE_RAW);
    }

    /**
     * @throws UninitializedUriError
     */
    public function getHost(): ?string
    {
        return $this->getComponent('host', self::TYPE_NORMALIZED);
    }

    /**
     * @throws UninitializedUriError|InvalidUriException
     */
    public function withHost(?string $encodedHost): self
    {
        if ($encodedHost === $this->getRawHost()) {
            return $this;
        }

        return new self(UriString::build(self::validateComponents([...$this->rawComponents, ...['host' => $encodedHost]])));
    }

    /**
     * @throws UninitializedUriError
     */
    public function getPort(): ?int
    {
        self::assertIsInitialized();

        return $this->rawComponents['port'];
    }

    /**
     * @throws UninitializedUriError|InvalidUriException
     */
    public function withPort(?int $port): self
    {
        return match (true) {
            $port === $this->getPort() => $this,
            null !== $port && ($port < 0 || $port > 65535) => throw new InvalidUriException('The port component value must be null or an integer between 0 and 65535.'),
            default => new self(UriString::build([...$this->rawComponents, ...['port' => $port]])),
        };
    }

    /**
     * @throws UninitializedUriError
     */
    public function getRawPath(): ?string
    {
        return $this->getComponent('path', self::TYPE_RAW);
    }

    /**
     * @throws UninitializedUriError
     */
    public function getPath(): ?string
    {
        return $this->getComponent('path', self::TYPE_NORMALIZED);
    }

    /**
     * @throws UninitializedUriError|InvalidUriException
     */
    public function withPath(?string $encodedPath): self
    {
        return match (true) {
            $encodedPath === $this->getRawPath() => $this,
            null !== $encodedPath && 1 === preg_match(self::REGEXP_INVALID_PATH, $encodedPath) => throw new InvalidUriException('The encoded path component `'.$encodedPath.'` contains invalid characters.'),
            default => new self(UriString::build(self::validateComponents([...$this->rawComponents, ...['path' => $encodedPath]]))),
        };
    }

    /**
     * @throws UninitializedUriError
     */
    public function getRawQuery(): ?string
    {
        return $this->getComponent('query', self::TYPE_RAW);
    }

    /**
     * @throws UninitializedUriError
     */
    public function getQuery(): ?string
    {
        return $this->getComponent('query', self::TYPE_NORMALIZED);
    }

    /**
     * @throws UninitializedUriError|InvalidUriException
     */
    public function withQuery(?string $encodedQuery): self
    {
        return match (true) {
            $encodedQuery === $this->getQuery() => $this,
            null !== $encodedQuery && 1 === preg_match(self::REGEXP_INVALID_QUERY, $encodedQuery) => throw new InvalidUriException('The encoded query string component `'.$encodedQuery.'` contains invalid characters.'),
            default => new self(UriString::build([...$this->rawComponents, ...['query' => $encodedQuery]])),
        };
    }

    /**
     * @throws UninitializedUriError
     */
    public function getRawFragment(): ?string
    {
        return $this->getComponent('fragment', self::TYPE_RAW);
    }

    /**
     * @throws UninitializedUriError
     */
    public function getFragment(): ?string
    {
        return $this->getComponent('fragment', self::TYPE_NORMALIZED);
    }

    /**
     * @throws UninitializedUriError|InvalidUriException
     */
    public function withFragment(?string $encodedFragment): self
    {
        return match (true) {
            $encodedFragment === $this->getFragment() => $this,
            null !== $encodedFragment && 1 === preg_match(self::REGEXP_INVALID_FRAGMENT, $encodedFragment) => throw new InvalidUriException('The encoded fragment string component `'.$encodedFragment.'` contains invalid characters.'),
            default => new self(UriString::build([...$this->rawComponents, ...['fragment' => $encodedFragment]])),
        };
    }

    /**
     * @throws UninitializedUriError
     */
    public function equals(self $uri, bool $excludeFragment = true): bool
    {
        $this->assertIsInitialized();

        if ($excludeFragment && ($this->normalizedComponents['fragment'] !== $uri->normalizedComponents['fragment'])) {
            return UriString::build([...$this->normalizedComponents, ...['fragment' => null]]) === UriString::build([...$uri->normalizedComponents, ...['fragment' => null]]);
        }

        return $this->toString() === $uri->toString();
    }

    /**
     * @throws UninitializedUriError
     */
    public function toRawString(): string
    {
        $this->assertIsInitialized();

        $this->rawUri ??= UriString::build($this->rawComponents);

        return $this->rawUri;
    }

    /**
     * @throws UninitializedUriError
     */
    public function toString(): string
    {
        $this->assertIsInitialized();

        $this->normalizedUri ??= UriString::build($this->normalizedComponents);

        return $this->normalizedUri;
    }

    /**
     * @throws UninitializedUriError|InvalidUriException
     */
    public function resolve(string $uri): self
    {
        return new self($uri, $this->toRawString());
    }

    /**
     * @return array{__uri:string}
     */
    public function __serialize(): array
    {
        $this->assertIsInitialized();

        return ['__uri' => $this->toRawString()];
    }

    /**
     * @param array{__uri:string} $data
     *
     * @throws UninitializedUriError|InvalidUriException
     */
    public function __unserialize(array $data): void
    {
        $uri = new self($data['__uri'] ?? throw new UninitializedUriError('The `__uri` property is missing from the serialized object.'));
        $this->rawComponents = $uri->rawComponents;
        $this->normalizedComponents = $uri->normalizedComponents;
        $this->isInitialized = $uri->isInitialized;
    }

    /**
     * @return ComponentMap
     */
    public function __debugInfo(): array
    {
        $this->assertIsInitialized();

        $components = $this->rawComponents;
        unset($components['userInfo']);

        //the pass component is retracted, in debug mode, for security reason
        //whether it is present or not to avoid leaking its real presence
        $components['pass'] = '*****';

        return $components;
    }
}
