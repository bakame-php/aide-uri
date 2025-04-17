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
use Rowbot\URL\URL as WhatWgURL;
use SensitiveParameter;

use function substr;

use const PHP_VERSION_ID;

if (PHP_VERSION_ID < 80500) {
    final class Url
    {
        private WhatWgURL $url;
        private ValidationErrorLogger $logger;

        /**
         * @param array<int, UrlValidationError> $errors
         */
        public static function parse(string $uri, ?string $baseUrl = null, array &$errors = []): ?self
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
        public function __construct(string $uri, ?string $baseUrl = null, array &$softErrors = [])
        {
            $this->logger = new ValidationErrorLogger();
            $options = ['logger' => $this->logger];

            try {
                $this->logger->reset();
                $this->url = new WhatWgURL($uri, $baseUrl, $options);
            } catch (Exception $exception) {
                throw new InvalidUrlException(
                    message: $exception->getMessage(),
                    errors: $this->logger->errors(),
                    previous: $exception
                );
            } finally {
                $softErrors = $this->logger->recoverableErrors();
            }
        }

        private function copy(): self
        {
            $newInstance = new self('a://b');
            $newInstance->url = clone $this->url;
            $newInstance->logger->reset();

            return $newInstance;
        }

        public function getScheme(): string
        {
            $scheme = $this->url->protocol;
            if ('' !== $scheme) {
                return substr($scheme, 0, -1);
            }

            return '';
        }

        /**
         * @throws InvalidUrlException
         */
        public function withScheme(string $scheme): self
        {
            $copy = $this->copy();
            try {
                $copy->url->protocol = $scheme;

                return $copy;
            } catch (Exception $exception) {
                throw new InvalidUrlException($exception->getMessage(), errors: $copy->logger->errors(), previous: $exception);
            }
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
            $copy = $this->copy();
            try {
                $copy->url->username = (string) $user;

                return $copy;
            } catch (Exception $exception) {
                throw new InvalidUrlException($exception->getMessage(), errors: $copy->logger->errors(), previous: $exception);
            }
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
            $copy = $this->copy();
            try {
                $copy->url->password = (string) $password;

                return $copy;
            } catch (Exception $exception) {
                throw new InvalidUrlException($exception->getMessage(), errors: $copy->logger->errors(), previous: $exception);
            }
        }

        public function getAsciiHost(): ?string
        {
            return '' === $this->url->hostname ? null : $this->url->hostname;
        }

        public function getUnicodeHost(): ?string
        {
            $host = $this->getAsciiHost();
            if ('' === $host || null === $host) {
                return null;
            }

            $idn = Converter::toUnicode($host);
            if ($idn->hasErrors()) {
                return $this->url->hostname;
            }

            return $idn->domain();
        }

        /**
         * @throws InvalidUrlException
         */
        public function withHost(string $host): self
        {
            $copy = $this->copy();
            try {
                $copy->url->hostname = $host;

                return $copy;
            } catch (Exception $exception) {
                throw new InvalidUrlException($exception->getMessage(), errors: $copy->logger->errors(), previous: $exception);
            }
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
            $copy = $this->copy();
            try {
                $copy->url->port = (string) $port;

                return $copy;
            } catch (Exception $exception) {
                throw new InvalidUrlException($exception->getMessage(), errors: $copy->logger->errors(), previous: $exception);
            }
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
            $copy = $this->copy();
            try {
                $copy->url->pathname = $path;

                return $copy;
            } catch (Exception $exception) {
                throw new InvalidUrlException($exception->getMessage(), errors: $copy->logger->errors(), previous: $exception);
            }
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
            $copy = $this->copy();
            try {
                $copy->url->search = (string) $query;

                return $copy;
            } catch (Exception $exception) {
                throw new InvalidUrlException($exception->getMessage(), errors: $copy->logger->errors(), previous: $exception);
            }
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
            $copy = $this->copy();
            try {
                $copy->url->hash = (string) $fragment;

                return $copy;
            } catch (Exception $exception) {
                throw new InvalidUrlException($exception->getMessage(), errors: $copy->logger->errors(), previous: $exception);
            }
        }

        public function equals(Url $uri, bool $excludeFragment = true): bool
        {
            if ($this->url->hash === $uri->url->hash || ! $excludeFragment) {
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
            return UriString::build([
                'scheme' => $this->getScheme(),
                'user' => $this->getUsername(),
                'pass' => $this->getPassword(),
                'host' => $this->getUnicodeHost(),
                'port' => $this->getPort(),
                'path' => $this->getPath(),
                'query' => $this->getQuery(),
                'fragment' => $this->getFragment(),
            ]);
        }

        /**
         * @throws InvalidUrlException
         */
        public function resolve(string $uri): self
        {
            return new self($uri, $this->url->href);
        }

        /**
         * @return array{__uri: string}
         */
        public function __serialize(): array
        {
            return ['__uri' => $this->url->href];
        }

        /**
         * @param array{__uri: string} $data
         *
         * @throws Exception|InvalidUrlException
         */
        public function __unserialize(array $data): void
        {
            $uri = new self($data['__uri'] ?? throw new Exception('The `__uri` property is missing from the serialized object.'));
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
