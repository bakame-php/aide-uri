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
use League\Uri\Idna\Converter as IdnaConverter;
use League\Uri\IPv6\Converter as IPv6Converter;
use League\Uri\UriString;

use function explode;
use function rawurldecode;
use function strtolower;

/**
 * @phpstan-import-type ComponentMap from UriString
 * @phpstan-import-type InputComponentMap from UriString
 */
final class Uri
{
    private readonly ?string $scheme;
    private readonly ?string $user;
    private readonly ?string $password;
    private readonly ?string $host;
    private readonly ?int $port;
    private readonly ?string $path;
    private readonly ?string $query;
    private readonly ?string $fragment;
    private readonly string $uriString;
    private readonly string $uriNormalizedString;

    public static function parse(string $uri, ?string $baseUri = null): ?Uri
    {
        try {
            return new self($uri, $baseUri);
        } catch (Exception) {
            return null;
        }
    }

    public function __construct(string $uri, ?string $baseUri = null)
    {
        $parsed = UriString::parse(null === $baseUri ? $uri : UriString::resolve($uri, $baseUri));
        [
            'scheme' => $this->scheme,
            'user' => $this->user,
            'pass' => $this->password,
            'host' => $this->host,
            'port' => $this->port,
            'path' => $path,
            'query' => $this->query,
            'fragment' => $this->fragment,
        ] = $parsed;

        $this->path = ('' === $path) ? null : $path;
        $this->uriString = UriString::build($parsed);
        $this->uriNormalizedString = UriString::normalize($this->uriString);
    }

    /**
     * @return InputComponentMap
     */
    private function toComponents(): array
    {
        return [
            'scheme' => $this->scheme,
            'user' => $this->user,
            'pass' => $this->password,
            'host' => $this->host,
            'port' => $this->port,
            'path' => $this->path,
            'query' => $this->query,
            'fragment' => $this->fragment,
        ];
    }

    private function decoded(?string $component): ?string
    {
        return null !== $component ? rawurldecode($component) : null;
    }

    public function getScheme(): ?string
    {
        return null !== $this->scheme ? strtolower($this->scheme) : null;
    }

    public function getRawScheme(): ?string
    {
        return $this->scheme;
    }

    public function withScheme(?string $encodedScheme): self
    {
        if ($encodedScheme === $this->scheme) {
            return $this;
        }

        return new self(UriString::build([...$this->toComponents(), ...['scheme' => $encodedScheme]]));
    }

    public function getUserInfo(): ?string
    {
        return $this->decoded($this->getRawUserInfo());
    }

    public function getRawUserInfo(): ?string
    {
        return match (null) {
            $this->user => null,
            $this->password => $this->user,
            default => $this->user.':'.$this->password,
        };
    }

    public function withUserInfo(?string $encodedUserInfo): self
    {
        if ($encodedUserInfo === $this->getRawUserInfo()) {
            return $this;
        }

        [$user, $password] = explode(':', $encodedUserInfo, 2) + [1 => null]; /* @phpstan-ignore-line */

        return new self(UriString::build([...$this->toComponents(), ...['user' => $user, 'password' => $password]]));
    }

    public function getRawUser(): ?string
    {
        return $this->user;
    }

    public function getUser(): ?string
    {
        return $this->decoded($this->user);
    }

    public function getRawPassword(): ?string
    {
        return $this->password;
    }

    public function getPassword(): ?string
    {
        return $this->decoded($this->password);
    }

    public function getRawHost(): ?string
    {
        return $this->host;
    }

    public function getHost(): ?string
    {
        return null !== $this->host ? IdnaConverter::toAscii($this->host)->domain() : null;
    }

    public function getHostForDisplay(): ?string
    {
        return null !== $this->host ? IdnaConverter::toUnicode((string)IPv6Converter::compress($this->host))->domain() : null;
    }

    public function withHost(?string $encodedHost): self
    {
        if ($encodedHost === $this->host) {
            return $this;
        }

        return new self(UriString::build([...$this->toComponents(), ...['host' => $encodedHost]]));
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function withPort(?int $port): self
    {
        if ($port === $this->port) {
            return $this;
        }

        return new self(UriString::build([...$this->toComponents(), ...['port' => $port]]));
    }

    public function getRawPath(): ?string
    {
        return $this->path;
    }

    public function getPath(): ?string
    {
        return $this->decoded($this->path);
    }

    public function withPath(?string $encodedPath): self
    {
        if ($encodedPath === $this->path) {
            return $this;
        }

        return new self(UriString::build([...$this->toComponents(), ...['path' => $encodedPath]]));
    }

    public function getRawQuery(): ?string
    {
        return $this->query;
    }

    public function getQuery(): ?string
    {
        return $this->decoded($this->query);
    }

    public function withQuery(?string $encodedQuery): self
    {
        if ($encodedQuery === $this->query) {
            return $this;
        }

        return new self(UriString::build([...$this->toComponents(), ...['query' => $encodedQuery]]));
    }

    public function getRawFragment(): ?string
    {
        return $this->fragment;
    }

    public function getFragment(): ?string
    {
        return $this->decoded($this->fragment);
    }

    public function withFragment(?string $encodedFragment): self
    {
        if ($encodedFragment === $this->fragment) {
            return $this;
        }

        return new self(UriString::build([...$this->toComponents(), ...['fragment' => $encodedFragment]]));
    }

    public function normalize(): self
    {
        return new self($this->uriNormalizedString);
    }

    public function equals(self $uri, bool $excludeFragment = true): bool
    {
        if ($excludeFragment) {
            return $this->withFragment(null)->uriNormalizedString === $uri->withFragment(null)->uriNormalizedString;
        }

        return $this->uriNormalizedString === $uri->uriNormalizedString;
    }

    public function toNormalizedString(): string
    {
        return $this->uriNormalizedString;
    }

    public function toString(): string
    {
        return $this->uriString;
    }

    public function resolve(self $uri): self
    {
        return new self($uri->toString(), $this->toString());
    }

    /**
     * @return InputComponentMap
     */
    public function __serialize(): array
    {
        return $this->toComponents();
    }

    /**
     * @param InputComponentMap $data
     */
    public function __unserialize(array $data): void
    {
        //we do a 2 pass because we can not trust external data storage
        $parsed = UriString::parse(UriString::build($data));
        [
            'scheme' => $this->scheme,
            'user' => $this->user,
            'pass' => $this->password,
            'host' => $this->host,
            'port' => $this->port,
            'path' => $path,
            'query' => $this->query,
            'fragment' => $this->fragment,
        ] = $parsed;

        $this->path = ('' === $path) ? null : $path;
        $this->uriString = UriString::build($parsed);
        $this->uriNormalizedString = UriString::normalize($this->uriString);
    }

    /**
     * @return InputComponentMap
     */
    public function __debugInfo(): array
    {
        return $this->toComponents();
    }
}
