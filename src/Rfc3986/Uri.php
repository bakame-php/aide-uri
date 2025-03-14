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

use League\Uri\UriString;
use Throwable;

use function explode;

/**
 * @phpstan-type ComponentMap array{scheme: ?string, user: ?string, pass: ?string, host: ?string, port: ?int, path: ?string, query: ?string, fragment: ?string}
 * @phpstan-type Components array{scheme: ?string, userInfo: ?string, user: ?string, pass: ?string, host: ?string, port: ?int, path: ?string, query: ?string, fragment: ?string}
 */
final class Uri
{
    private const TYPE_RAW = 'raw';
    private const TYPE_NORMALIZED = 'normalized';

    private readonly string $uri;
    /** @var Components */
    private readonly array $components;
    private readonly string $normalizedUri;
    /** @var Components */
    private readonly array $normalizedComponents;
    private bool $isInitialized = false;

    public static function parse(string $uri, ?string $baseUri = null): ?Uri
    {
        try {
            return new self($uri, $baseUri);
        } catch (Throwable) {
            return null;
        }
    }

    public function __construct(string $uri, ?string $baseUri = null)
    {
        try {
            $this->uri = null !== $baseUri ? UriString::resolve($uri, $baseUri) : $uri;
            $this->components = $this->initUri($this->uri);
            $this->normalizedUri = UriString::normalize($this->uri);
            $this->normalizedComponents = $this->normalizedUri === $this->uri ? $this->components : $this->initUri($this->normalizedUri);
            $this->isInitialized = true;
        } catch (Throwable $exception) {
            throw new InvalidUriException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * @return Components
     */
    private function initUri(string $uri): array
    {
        /** @var Components $defaults */
        static $defaults = [
            'scheme' => null, 'userInfo' => null, 'user' => null, 'pass' => null, 'host' => null,
            'port' => null, 'path' => null, 'query' => null, 'fragment' => null,
        ];

        /** @var Components $components */
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
     * @throws UninitializedUriException
     */
    private function assertIsInitialized(): void
    {
        $this->isInitialized || throw new UninitializedUriException('Object of type '.self::class.' has not been correctly initialized.');
    }

    /**
     * @param self::TYPE_RAW|self::TYPE_NORMALIZED $type
     *
     * @throws UninitializedUriException
     *
     */
    private function get(string $component, string $type): ?string
    {
        self::assertIsInitialized();
        $value = (self::TYPE_RAW === $type ? $this->components : $this->normalizedComponents)[$component];
        if (null !== $value) {
            $value = (string)$value;
        }

        return $value;
    }

    /**
     * @throws UninitializedUriException
     */
    public function getScheme(): ?string
    {
        return $this->get('scheme', self::TYPE_NORMALIZED);
    }

    /**
     * @throws UninitializedUriException
     */
    public function getRawScheme(): ?string
    {
        return $this->get('scheme', self::TYPE_RAW);
    }

    /**
     * @throws UninitializedUriException|InvalidUriException
     */
    public function withScheme(?string $encodedScheme): self
    {
        if ($encodedScheme === $this->getRawScheme()) {
            return $this;
        }

        return new self(UriString::build([...$this->components, ...['scheme' => $encodedScheme]]));
    }

    /**
     * @throws UninitializedUriException
     */
    public function getUserInfo(): ?string
    {
        return $this->get('userInfo', self::TYPE_NORMALIZED);
    }

    /**
     * @throws UninitializedUriException
     */
    public function getRawUserInfo(): ?string
    {
        return $this->get('userInfo', self::TYPE_RAW);
    }

    /**
     * @throws UninitializedUriException|InvalidUriException
     */
    public function withUserInfo(?string $encodedUserInfo): self
    {
        if ($encodedUserInfo === $this->getRawUserInfo()) {
            return $this;
        }

        [$user, $password] = explode(':', $encodedUserInfo, 2) + [1 => null]; /* @phpstan-ignore-line */

        return new self(UriString::build([...$this->components, ...['user' => $user, 'password' => $password]]));
    }

    /**
     * @throws UninitializedUriException
     */
    public function getRawUser(): ?string
    {
        return $this->get('user', self::TYPE_RAW);
    }

    /**
     * @throws UninitializedUriException
     */
    public function getUser(): ?string
    {
        return $this->get('user', self::TYPE_NORMALIZED);
    }

    /**
     * @throws UninitializedUriException
     */
    public function getRawPassword(): ?string
    {
        return $this->get('pass', self::TYPE_RAW);
    }

    /**
     * @throws UninitializedUriException
     */
    public function getPassword(): ?string
    {
        return $this->get('pass', self::TYPE_NORMALIZED);
    }

    /**
     * @throws UninitializedUriException
     */
    public function getRawHost(): ?string
    {
        return $this->get('host', self::TYPE_RAW);
    }

    /**
     * @throws UninitializedUriException
     */
    public function getHost(): ?string
    {
        return $this->get('host', self::TYPE_NORMALIZED);
    }

    /**
     * @throws UninitializedUriException|InvalidUriException
     */
    public function withHost(?string $encodedHost): self
    {
        if ($encodedHost === $this->getRawHost()) {
            return $this;
        }

        return new self(UriString::build([...$this->components, ...['host' => $encodedHost]]));
    }

    /**
     * @throws UninitializedUriException
     */
    public function getPort(): ?int
    {
        self::assertIsInitialized();

        return $this->components['port'];
    }

    /**
     * @throws UninitializedUriException|InvalidUriException
     */
    public function withPort(?int $port): self
    {
        if ($port === $this->getPort()) {
            return $this;
        }

        return new self(UriString::build([...$this->components, ...['port' => $port]]));
    }

    /**
     * @throws UninitializedUriException
     */
    public function getRawPath(): ?string
    {
        return $this->get('path', self::TYPE_RAW);
    }

    /**
     * @throws UninitializedUriException
     */
    public function getPath(): ?string
    {
        return $this->get('path', self::TYPE_NORMALIZED);
    }

    /**
     * @throws UninitializedUriException|InvalidUriException
     */
    public function withPath(?string $encodedPath): self
    {
        if ($encodedPath === $this->getRawPath()) {
            return $this;
        }

        return new self(UriString::build([...$this->components, ...['path' => $encodedPath]]));
    }

    /**
     * @throws UninitializedUriException
     */
    public function getRawQuery(): ?string
    {
        return $this->get('query', self::TYPE_RAW);
    }

    /**
     * @throws UninitializedUriException
     */
    public function getQuery(): ?string
    {
        return $this->get('query', self::TYPE_NORMALIZED);
    }

    /**
     * @throws UninitializedUriException|InvalidUriException
     */
    public function withQuery(?string $encodedQuery): self
    {
        if ($encodedQuery === $this->getRawQuery()) {
            return $this;
        }

        return new self(UriString::build([...$this->components, ...['query' => $encodedQuery]]));
    }

    /**
     * @throws UninitializedUriException
     */
    public function getRawFragment(): ?string
    {
        return $this->get('fragment', self::TYPE_RAW);
    }

    /**
     * @throws UninitializedUriException
     */
    public function getFragment(): ?string
    {
        return $this->get('fragment', self::TYPE_NORMALIZED);
    }

    /**
     * @throws UninitializedUriException|InvalidUriException
     */
    public function withFragment(?string $encodedFragment): self
    {
        if ($encodedFragment === $this->getRawFragment()) {
            return $this;
        }

        return new self(UriString::build([...$this->components, ...['fragment' => $encodedFragment]]));
    }

    /**
     * @throws UninitializedUriException|InvalidUriException
     */
    public function normalize(): self
    {
        $this->assertIsInitialized();
        if ($this->normalizedUri === $this->uri) {
            return $this;
        }

        return new self($this->normalizedUri);
    }

    /**
     * @throws UninitializedUriException
     */
    public function equals(self $uri, bool $excludeFragment = true): bool
    {
        $this->assertIsInitialized();
        if ($excludeFragment) {
            return $this->withFragment(null)->toString() === $uri->withFragment(null)->toString();
        }

        return $this->normalizedUri === $uri->normalizedUri;
    }

    /**
     * @throws UninitializedUriException
     */
    public function toString(): string
    {
        $this->assertIsInitialized();

        return $this->normalizedUri;
    }

    /**
     * @throws UninitializedUriException
     */
    public function toRawString(): string
    {
        $this->assertIsInitialized();

        return $this->uri;
    }

    /**
     * @throws UninitializedUriException
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

        return ['__uri' => $this->uri];
    }

    /**
     * @param array{__uri:string} $data
     *
     * @throws UninitializedUriException|InvalidUriException
     */
    public function __unserialize(array $data): void
    {
        $uri = new self($data['__uri'] ?? throw new UninitializedUriException('The `__uri` property is missing from the serialized object.'));
        $this->uri = $uri->uri;
        $this->normalizedUri = $uri->normalizedUri;
        $this->components = $uri->components;
        $this->normalizedComponents = $uri->normalizedComponents;
        $this->isInitialized = $uri->isInitialized;
    }

    /**
     * @return ComponentMap
     */
    public function __debugInfo(): array
    {
        $this->assertIsInitialized();

        $components = $this->components;
        unset($components['userInfo']);

        return $components;
    }
}
