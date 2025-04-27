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

namespace Uri\Rfc3986;

use Exception;
use League\Uri\Encoder;
use League\Uri\UriString;
use SensitiveParameter;
use Uri\InvalidUriException;

use function explode;

use const PHP_VERSION_ID;

if (PHP_VERSION_ID < 80500) {
    /**
     * This is a user-land polyfill to the native Uri\Rfc3986\Rfc3986\Uri class proposed
     * in the PHP RFC: Add RFC 3986 and WHATWG compliant URI parsing support.
     *
     * @see https://wiki.php.net/rfc/url_parsing_api
     *
     * @phpstan-type InputComponentMap array{scheme?: ?string, user?: ?string, pass?: ?string, host?: ?string, port?: ?int, path?: string, query?: ?string, fragment?: ?string}
     * @phpstan-type ComponentMap array{scheme: ?string, user: ?string, pass: ?string, host: ?string, port: ?int, path: string, query: ?string, fragment: ?string}
     * @phpstan-type Components array{scheme: ?string, userInfo: ?string, user: ?string, pass: ?string, host: ?string, port: ?int, path: string, query: ?string, fragment: ?string}
     */
    final class Uri
    {
        private const TYPE_RAW = 'raw';
        private const TYPE_NORMALIZED = 'normalized';
        /** @var Components */
        private const DEFAULT_COMPONENTS = ['scheme' => null, 'userInfo' => null, 'user' => null, 'pass' => null, 'host' => null, 'port' => null, 'path' => '', 'query' => null, 'fragment' => null];
        /** @var Components */
        private readonly array $rawComponents;
        private readonly string $rawUri;
        /** @var Components */
        private array $normalizedComponents = self::DEFAULT_COMPONENTS;
        private ?string $normalizedUri = null;
        private bool $isNormalized;

        /**
         * @throws InvalidUriException
         */
        public function __construct(string $uri, ?string $baseUri = null)
        {
            self::assertUriContainsValidRfc3986Characters($uri);
            self::assertUriContainsValidRfc3986Characters($baseUri);

            try {
                $uri = null !== $baseUri ? UriString::resolve($uri, $baseUri) : $uri;
                $components = self::addUserInfo(UriString::parse($uri));
            } catch (Exception $exception) {
                throw new InvalidUriException($exception->getMessage(), previous: $exception);
            }

            Encoder::isUserEncoded($components['user']) || throw new InvalidUriException('The encoded userInfo string component `'.$components['userInfo'].'` contains invalid characters.');
            Encoder::isPasswordEncoded($components['pass']) || throw new InvalidUriException('The encoded userInfo component `'.$components['userInfo'].'` contains invalid characters.');
            Encoder::isPathEncoded($components['path']) || throw new InvalidUriException('The encoded path component `'.$components['path'].'` contains invalid characters.');
            Encoder::isQueryEncoded($components['query']) || throw new InvalidUriException('The encoded query string component `'.$components['query'].'` contains invalid characters.');
            Encoder::isFragmentEncoded($components['fragment']) || throw new InvalidUriException('The encoded fragment string component `'.$components['fragment'].'` contains invalid characters.');

            $this->rawUri = $uri;
            $this->rawComponents = $components;
            $this->isNormalized = false;
        }

        /**
         * @throws InvalidUriException
         */
        private static function assertUriContainsValidRfc3986Characters(?string $uri): void
        {
            null === $uri
            || UriString::containsValidRfc3986Characters($uri)
            || throw new InvalidUriException('The URI `'.$uri.'` contains invalid RFC3986 characters.');
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
        private static function addUserInfo(array $parts): array
        {
            $components = [...self::DEFAULT_COMPONENTS, ...$parts];
            if (null === $components['user']) {
                $components['pass'] = null;
                $components['userInfo'] = null;

                return $components;
            }

            $components['userInfo'] = $components['user'];
            if (null === $components['pass']) {
                return $components;
            }

            $components['userInfo'] .= ':'.$components['pass'];

            return $components;
        }

        public static function parse(string $uri, ?string $baseUri = null): ?Uri
        {
            try {
                return new self($uri, $baseUri);
            } catch (Exception) {
                return null;
            }
        }

        /**
         * @param self::TYPE_RAW|self::TYPE_NORMALIZED $type
         */
        private function getComponent(string $type, string $name): ?string
        {
            if (self::TYPE_NORMALIZED === $type) {
                $this->setNormalizedComponents();
            }

            $value = self::TYPE_NORMALIZED === $type ? $this->normalizedComponents[$name] : $this->rawComponents[$name];
            if (null === $value) {
                return null;
            }

            return (string) $value;
        }

        private function setNormalizedComponents(): void
        {
            if ($this->isNormalized) {
                return;
            }

            $this->normalizedComponents = [
                ...self::addUserInfo(UriString::parseNormalized($this->rawUri)),
                ...['host' => Encoder::normalizeHost($this->rawComponents['host'])],
            ];
            $this->isNormalized = true;
        }

        /**
         * @param InputComponentMap $components
         *
         * @throws InvalidUriException
         */
        private function withComponent(array $components): self
        {
            try {
                $uri = UriString::build([...$this->rawComponents, ...$components]);
            } catch (Exception $exception) {
                throw new InvalidUriException($exception->getMessage(), previous: $exception);
            }

            return new self($uri);
        }

        public function getScheme(): ?string
        {
            return $this->getComponent(self::TYPE_NORMALIZED, 'scheme');
        }

        public function getRawScheme(): ?string
        {
            return $this->getComponent(self::TYPE_RAW, 'scheme');
        }

        /**
         * @throws InvalidUriException
         */
        public function withScheme(?string $scheme): self
        {
            return match (true) {
                $scheme === $this->getRawScheme() => $this,
                UriString::isScheme($scheme) => $this->withComponent(['scheme' => $scheme]),
                default => throw new InvalidUriException('The scheme string component `'.$scheme.'` is an invalid scheme.'),
            };
        }

        public function getUserInfo(): ?string
        {
            return $this->getComponent(self::TYPE_NORMALIZED, 'userInfo');
        }

        public function getRawUserInfo(): ?string
        {
            return $this->getComponent(self::TYPE_RAW, 'userInfo');
        }

        /**
         * @throws InvalidUriException
         */
        public function withUserInfo(#[SensitiveParameter] ?string $userInfo): self
        {
            if ($this->getRawUserInfo() === $userInfo) {
                return $this;
            }

            if (null === $userInfo) {
                return $this->withComponent(['user' => null, 'pass' => null]);
            }

            [$user, $password] = explode(':', $userInfo, 2) + [1 => null];
            Encoder::isUserEncoded($user) || throw new InvalidUriException('The encoded userInfo string component `'.$userInfo.'` contains invalid characters.');
            Encoder::isPasswordEncoded($password) || throw new InvalidUriException('The encoded userInfo string component `'.$userInfo.'` contains invalid characters.');

            return $this->withComponent(['user' => $user, 'pass' => $password]);
        }

        public function getRawUsername(): ?string
        {
            return $this->getComponent(self::TYPE_RAW, 'user');
        }

        public function getUsername(): ?string
        {
            return $this->getComponent(self::TYPE_NORMALIZED, 'user');
        }

        public function getRawPassword(): ?string
        {
            return $this->getComponent(self::TYPE_RAW, 'pass');
        }

        public function getPassword(): ?string
        {
            return $this->getComponent(self::TYPE_NORMALIZED, 'pass');
        }

        public function getRawHost(): ?string
        {
            return $this->getComponent(self::TYPE_RAW, 'host');
        }

        public function getHost(): ?string
        {
            return $this->getComponent(self::TYPE_NORMALIZED, 'host');
        }

        /**
         * @throws InvalidUriException
         */
        public function withHost(?string $host): self
        {
            return match (true) {
                $host === $this->getRawHost() => $this,
                UriString::isHost($host) => $this->withComponent(['host' => $host]),
                default => throw new InvalidUriException('The host component value `'.$host.'` is not a valid host.'),
            };
        }

        public function getPort(): ?int
        {
            return $this->rawComponents['port'];
        }

        /**
         * @throws InvalidUriException
         */
        public function withPort(?int $port): self
        {
            return match (true) {
                $port === $this->getPort() => $this,
                null === $port || ($port >= 0 && $port <= 65535) => $this->withComponent(['port' => $port]),
                default => throw new InvalidUriException('The port component value must be null or an integer between 0 and 65535.'),
            };
        }

        public function getRawPath(): string
        {
            return (string) $this->getComponent(self::TYPE_RAW, 'path');
        }

        public function getPath(): string
        {
            return (string) $this->getComponent(self::TYPE_NORMALIZED, 'path');
        }

        /**
         * @throws InvalidUriException
         */
        public function withPath(string $path): self
        {
            return match (true) {
                $path === $this->getRawPath() => $this,
                Encoder::isPathEncoded($path) => $this->withComponent(['path' => $path]),
                default => throw new InvalidUriException('The encoded path component `'.$path.'` contains invalid characters.'),
            };
        }

        public function getRawQuery(): ?string
        {
            return $this->getComponent(self::TYPE_RAW, 'query');
        }

        public function getQuery(): ?string
        {
            return $this->getComponent(self::TYPE_NORMALIZED, 'query');
        }

        /**
         * @throws InvalidUriException
         */
        public function withQuery(?string $query): self
        {
            return match (true) {
                $query === $this->getRawQuery() => $this,
                Encoder::isQueryEncoded($query) => $this->withComponent(['query' => $query]),
                default => throw new InvalidUriException('The encoded query string component `'.$query.'` contains invalid characters.'),
            };
        }

        public function getRawFragment(): ?string
        {
            return $this->getComponent(self::TYPE_RAW, 'fragment');
        }

        public function getFragment(): ?string
        {
            return $this->getComponent(self::TYPE_NORMALIZED, 'fragment');
        }

        /**
         * @throws InvalidUriException
         */
        public function withFragment(?string $fragment): self
        {
            return match (true) {
                $fragment === $this->getRawFragment() => $this,
                Encoder::isFragmentEncoded($fragment) => $this->withComponent(['fragment' => $fragment]),
                default => throw new InvalidUriException('The encoded fragment string component `'.$fragment.'` contains invalid characters.'),
            };
        }

        /**
         * @throws Exception
         */
        public function equals(self $uri, bool $excludeFragment = true): bool
        {
            return match (true) {
                $this->getFragment() === $uri->getFragment(),
                ! $excludeFragment => $this->normalizedComponents === $uri->normalizedComponents,
                default => [...$this->normalizedComponents, ...['fragment' => null]] === [...$uri->normalizedComponents, ...['fragment' => null]],
            };
        }

        public function toRawString(): string
        {
            return $this->rawUri;
        }

        public function toString(): string
        {
            $this->setNormalizedComponents();
            $this->normalizedUri ??= UriString::build($this->normalizedComponents);

            return $this->normalizedUri;
        }

        /**
         * @throws InvalidUriException
         */
        public function resolve(string $uri): self
        {
            return new self($uri, $this->toRawString());
        }

        /**
         * @return array{0: array{uri: string}, 1: array{}}
         */
        public function __serialize(): array
        {
            return [['uri' => $this->toRawString()], []];
        }

        /**
         * @param array{0: array{uri: string}, 1: array{}} $data
         *
         * @throws Exception|InvalidUriException
         */
        public function __unserialize(array $data): void
        {
            [$properties] = $data;
            $uri = new self($properties['uri'] ?? throw new Exception('The `uri` property is missing from the serialized object.'));

            $this->rawComponents = $uri->rawComponents;
            $this->rawUri = $uri->rawUri;
            $this->isNormalized = false;
        }

        /**
         * @return array{scheme: ?string, username: ?string, password: ?string, host: ?string, port: ?int, path: string, query: ?string, fragment: ?string}
         */
        public function __debugInfo(): array
        {
            return [
                'scheme' => $this->rawComponents['scheme'],
                'username' => $this->rawComponents['user'],
                'password' => $this->rawComponents['pass'],
                'host' => $this->rawComponents['host'],
                'port' => $this->rawComponents['port'],
                'path' => $this->rawComponents['path'],
                'query' => $this->rawComponents['query'],
                'fragment' => $this->rawComponents['fragment'],
            ];
        }
    }
}
