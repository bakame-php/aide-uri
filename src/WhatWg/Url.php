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

namespace Uri\WhatWg;

use Exception;
use League\Uri\Idna\Converter;
use League\Uri\UriString;
use ReflectionClass;
use Rowbot\URL\URL as WhatWgURL;
use SensitiveParameter;
use Uri\UriComparisonMode;

use function substr;

use const PHP_VERSION_ID;

if (PHP_VERSION_ID < 80500) {
    /**
     * This is a user-land polyfill to the native Uri\WhatWg\Url class proposed
     * in the PHP RFC: Add RFC 3986 and WHATWG compliant URI parsing support.
     *
     * @see https://wiki.php.net/rfc/url_parsing_api
     */
    final class Url
    {
        private WhatWgURL $url;
        private ?string $urlUnicodeString = null;

        /**
         * @param array<int, UrlValidationError> $errors
         */
        public static function parse(string $uri, ?self $baseUrl = null, array &$errors = []): ?self
        {
            try {
                return new self($uri, $baseUrl, $errors);
            } catch (InvalidUrlException $exception) {
                $errors = $exception->errors;

                return null;
            }
        }

        /**
         * @param array<int, UrlValidationError> $softErrors
         *
         * @throws InvalidUrlException
         */
        public function __construct(string $uri, ?self $baseUrl = null, array &$softErrors = [])
        {
            $collector = new UrlValidationErrorCollector();

            try {
                $this->url = new WhatWgURL($uri, $baseUrl?->url->href, ['logger' => $collector]);
            } catch (Exception $exception) {
                throw new InvalidUrlException(
                    message: $exception->getMessage(),
                    errors: $collector->errors(),
                    previous: $exception
                );
            } finally {
                $softErrors = $collector->recoverableErrors();
            }
        }

        private function copy(): self
        {
            $newInstance = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();
            $newInstance->url = clone $this->url;

            return $newInstance;
        }

        public function getScheme(): string
        {
            return substr($this->url->protocol, 0, -1);
        }

        /**
         * @throws InvalidUrlException
         */
        public function withScheme(string $scheme): self
        {
            $scheme = strtolower($scheme);
            if ($scheme === $this->getScheme() || $scheme === $this->url->protocol) {
                return $this;
            }

            $copy = $this->copy();
            $copy->url->protocol = $scheme;

            return $copy;
        }

        public function getUsername(): ?string
        {
            return '' === $this->url->username ? null : $this->url->username;
        }

        /**
         * @throws InvalidUrlException
         */
        public function withUsername(?string $user): self
        {
            if ($user === $this->getUsername() || $user === $this->url->username) {
                return $this;
            }

            $copy = $this->copy();
            $copy->url->username = (string) $user;

            return $copy;
        }

        public function getPassword(): ?string
        {
            return  '' === $this->url->password ? null : $this->url->password;
        }

        /**
         * @throws InvalidUrlException
         */
        public function withPassword(#[SensitiveParameter] ?string $password): self
        {
            if ($password === $this->getPassword() || $password === $this->url->password) {
                return $this;
            }

            $copy = $this->copy();
            $copy->url->password = (string) $password;

            return $copy;
        }

        public function getAsciiHost(): ?string
        {
            return '' === $this->url->hostname ? null : $this->url->hostname;
        }

        public function getUnicodeHost(): ?string
        {
            $host = $this->getAsciiHost();
            if ('' === $host || null === $host) {
                return $host;
            }

            $idn = Converter::toUnicode($host);
            if ($idn->hasErrors()) {
                return $host;
            }

            return $idn->domain();
        }

        /**
         * @throws InvalidUrlException
         */
        public function withHost(string $host): self
        {
            if ($host === $this->getAsciiHost() || $host === $this->getUnicodeHost()) {
                return $this;
            }

            $copy = $this->copy();
            $copy->url->hostname = $host;

            return $copy;
        }

        public function getPort(): ?int
        {
            return '' === $this->url->port ? null : (int) $this->url->port;
        }

        /**
         * @throws InvalidUrlException
         */
        public function withPort(?int $port): self
        {
            if ($port === $this->getPort()) {
                return $this;
            }

            $copy = $this->copy();
            $copy->url->port = (string) $port;

            return $copy;
        }

        public function getPath(): string
        {
            return $this->url->pathname;
        }

        /**
         * @throws InvalidUrlException
         */
        public function withPath(string $path): self
        {
            if ($path === $this->url->pathname) {
                return $this;
            }

            $copy = $this->copy();
            $copy->url->pathname = $path;

            return $copy;
        }

        public function getQuery(): ?string
        {
            $query = $this->url->search;
            if ('' !== $query) {
                return substr($query, 1);
            }

            return null;
        }

        /**
         * @throws InvalidUrlException
         */
        public function withQuery(?string $query): self
        {
            if ($query === $this->url->search || $query === $this->getQuery()) {
                return $this;
            }

            $copy = $this->copy();
            $copy->url->search = (string) $query;

            return $copy;
        }

        public function getFragment(): ?string
        {
            $fragment = $this->url->hash;
            if ('' !== $fragment) {
                return substr($fragment, 1);
            }

            return null;
        }

        /**
         * @throws InvalidUrlException
         */
        public function withFragment(?string $fragment): self
        {
            if ($fragment === $this->url->hash || $fragment === $this->getFragment()) {
                return $this;
            }

            $copy = $this->copy();
            $copy->url->hash = (string) $fragment;

            return $copy;
        }

        public function equals(self $uri, UriComparisonMode $uriComparisonMode = UriComparisonMode::ExcludeFragment): bool
        {
            if ($this->url->hash === $uri->url->hash || UriComparisonMode::IncludeFragment === $uriComparisonMode) {
                return $this->url->href === $uri->url->href;
            }

            $cloneThis = clone $this->url;
            $cloneThis->hash = '';
            $cloneThat = clone $uri->url;
            $cloneThat->hash = '';

            return $cloneThis->href === $cloneThat->href;
        }

        public function toAsciiString(): string
        {
            return $this->url->href;
        }

        public function toUnicodeString(): string
        {
            if (null !== $this->urlUnicodeString) {
                return $this->urlUnicodeString;
            }

            $unicodeHost = $this->getUnicodeHost();
            $this->urlUnicodeString = $this->getAsciiHost() === $unicodeHost
                ? $this->url->href
                : UriString::build([
                ...UriString::parse($this->url->href),
                ...['host' => $unicodeHost],
            ]);

            return $this->urlUnicodeString;
        }

        /**
         * @param array<int, UrlValidationError> $softErrors
         *
         * @throws InvalidUrlException
         */
        public function resolve(string $uri, array &$softErrors = []): self
        {
            return new self($uri, $this, $softErrors);
        }

        /**
         * @return array{0: array{uri: string}, 1: array{}}
         */
        public function __serialize(): array
        {
            return [['uri' => $this->url->href], []];
        }

        /**
         * @param array{0: array{uri: string}, 1: array{}} $data
         *
         * @throws Exception|InvalidUrlException
         */
        public function __unserialize(array $data): void
        {
            [$properties] = $data;
            $uri = new self($properties['uri'] ?? throw new Exception('The `uri` property is missing from the serialized object.'));
            $this->url = $uri->url;
        }

        /**
         * @return array{scheme: ?string, username: ?string, password: ?string, host: ?string, port: ?int, path: ?string, query: ?string, fragment: ?string}
         */
        public function __debugInfo(): array
        {
            return [
                'scheme' => $this->getScheme(),
                'username' => $this->getUsername(),
                'password' => $this->getPassword(),
                'host' => $this->getAsciiHost(),
                'port' => $this->getPort(),
                'path' => $this->getPath(),
                'query' => $this->getQuery(),
                'fragment' => $this->getFragment(),
            ];
        }
    }
}
