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
use League\Uri\Encoder;
use League\Uri\Exceptions\SyntaxError;
use League\Uri\UriString;
use SensitiveParameter;

use function explode;
use function preg_match;

/**
 * This is a user-land polyfill to the native Uri\Rfc3986\Uri class proposed
 * in the PHP RFC: Add RFC 3986 and WHATWG compliant URI parsing support.
 *
 * @see https://wiki.php.net/rfc/url_parsing_api
 *
 * @phpstan-type ComponentMap array{scheme: ?string, user: ?string, pass: ?string, host: ?string, port: ?int, path: ?string, query: ?string, fragment: ?string}
 * @phpstan-type Components array{scheme: ?string, userInfo: ?string, user: ?string, pass: ?string, host: ?string, port: ?int, path: ?string, query: ?string, fragment: ?string}
 */
final class Uri
{
    private const TYPE_RAW = 'raw';
    private const TYPE_NORMALIZED = 'normalized';
    /** @var Components */
    private const DEFAULT_COMPONENTS = ['scheme' => null, 'userInfo' => null, 'user' => null, 'pass' => null, 'host' => null, 'port' => null, 'path' => null, 'query' => null, 'fragment' => null];
    /** @var Components */
    private readonly array $rawComponents;
    /** @var Components */
    private array $normalizedComponents = self::DEFAULT_COMPONENTS;
    private bool $isInitialized = false;
    private ?string $rawUri = null;
    private ?string $normalizedUri = null;

    public static function parse(string $uri, ?string $baseUri = null): ?Uri
    {
        try {
            return new self($uri, $baseUri);
        } catch (Exception) {
            return null;
        }
    }

    /**
     * @param Components $components
     *
     * @throws InvalidUriException
     *
     */
    private static function fromComponents(array $components): self
    {
        try {
            $uri = UriString::build($components);
        } catch (SyntaxError $exception) {
            throw new InvalidUriException($exception->getMessage(), previous: $exception);
        }

        return new self($uri);
    }

    /**
     * @throws InvalidUriException
     */
    public function __construct(string $uri, ?string $baseUri = null)
    {
        try {
            $uri = null !== $baseUri ? UriString::resolve($uri, $baseUri) : $uri;
            $this->rawComponents = self::uriSplit(UriString::parse($uri));
        } catch (Exception $exception) {
            throw new InvalidUriException($exception->getMessage());
        }

        $this->isInitialized = true;
    }

    /**
     * Split the URI into its own component following RFC3986 rules.
     *
     * @link https://tools.ietf.org/html/rfc3986
     *
     * @param ComponentMap $parts The URI components
     *
     * @return Components
     */
    private static function uriSplit(array $parts): array
    {
        $components = [...self::DEFAULT_COMPONENTS, ...$parts];
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
     * @throws UninitializedUriError
     */
    private function assertIsInitialized(): void
    {
        $this->isInitialized || throw new UninitializedUriError(self::class.' object is not correctly initialized.');
    }

    /**
     * @param self::TYPE_RAW|self::TYPE_NORMALIZED $type
     *
     * @throws UninitializedUriError
     */
    private function getComponent(string $name, string $type): ?string
    {
        self::assertIsInitialized();
        if (self::TYPE_RAW === $type) {
            $value = $this->rawComponents[$name];
            if (null !== $value) {
                $value = (string) $value;
            }

            return $value;
        }

        $this->setNormalizedComponents();
        $value = $this->normalizedComponents[$name];
        if (null !== $value) {
            $value = (string)$value;
        }

        return $value;
    }

    private function setNormalizedComponents(): void
    {
        if (self::DEFAULT_COMPONENTS === $this->normalizedComponents) {
            $this->normalizedComponents = self::uriSplit(UriString::parseNormalized($this->toRawString()));
        }
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
            null !== $encodedScheme && 1 !== preg_match('/^[A-Za-z]([-A-Za-z\d+.]+)?$/', $encodedScheme) => throw new InvalidUriException('The scheme string component `'.$encodedScheme.'` is an invalid scheme.'),
            default => self::fromComponents([...$this->rawComponents, ...['scheme' => $encodedScheme]]),
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

        if (null === $encodedUserInfo) {
            return self::fromComponents([...$this->rawComponents, ...['user' => null, 'password' => null]]);
        }

        [$user, $password] = explode(':', $encodedUserInfo, 2) + [1 => null];

        return match (false) {
            Encoder::isUserEncoded($user),
            Encoder::isPasswordEncoded($password) => throw new InvalidUriException('The encoded userInfo string component contains invalid characters.'),
            default => self::fromComponents([...$this->rawComponents, ...['user' => $user, 'password' => $password]]),
        };
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
        return match (true) {
            $encodedHost === $this->getRawHost() => $this,
            UriString::isHost($encodedHost) => self::fromComponents([...$this->rawComponents, ...['host' => $encodedHost]]),
            default => throw new InvalidUriException('The host component value `'.$encodedHost.'` is not a valid host.'),
        };
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
            default => self::fromComponents([...$this->rawComponents, ...['port' => $port]]),
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
            Encoder::isPathEncoded($encodedPath) => self::fromComponents([...$this->rawComponents, ...['path' => $encodedPath]]),
            default => throw new InvalidUriException('The encoded path component `'.$encodedPath.'` contains invalid characters.'),
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
            Encoder::isQueryEncoded($encodedQuery) => self::fromComponents([...$this->rawComponents, ...['query' => $encodedQuery]]),
            default => throw new InvalidUriException('The encoded query string component `'.$encodedQuery.'` contains invalid characters.'),
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
            Encoder::isFragmentEncoded($encodedFragment) => self::fromComponents([...$this->rawComponents, ...['fragment' => $encodedFragment]]),
            default => throw new InvalidUriException('The encoded fragment string component `'.$encodedFragment.'` contains invalid characters.'),
        };
    }

    /**
     * @throws UninitializedUriError
     */
    public function equals(self $uri, bool $excludeFragment = true): bool
    {
        $this->assertIsInitialized();
        $this->setNormalizedComponents();

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
        $this->setNormalizedComponents();

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
        $this->normalizedComponents = self::DEFAULT_COMPONENTS;
        $this->rawUri = null;
        $this->normalizedUri = null;
        $this->isInitialized = true;
    }

    /**
     * @return ComponentMap
     */
    public function __debugInfo(): array
    {
        $this->assertIsInitialized();

        $components = $this->rawComponents;
        unset($components['userInfo']);

        return $components;
    }
}
