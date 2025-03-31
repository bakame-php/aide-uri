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
use function preg_match;

use const PHP_VERSION_ID;

if (PHP_VERSION_ID < 80500) {
    /**
     * This is a user-land polyfill to the native Uri\Rfc3986\Rfc3986\Uri class proposed
     * in the PHP RFC: Add RFC 3986 and WHATWG compliant URI parsing support.
     *
     * @see https://wiki.php.net/rfc/url_parsing_api
     *
     * @phpstan-type InputComponentMap array{scheme?: ?string, user?: ?string, pass?: ?string, host?: ?string, port?: ?int, path?: ?string, query?: ?string, fragment?: ?string}
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
        private readonly string $rawUri;
        /** @var Components */
        private array $normalizedComponents;
        private ?string $normalizedUri;

        /**
         * @throws InvalidUriException
         */
        public function __construct(string $uri, ?string $baseUri = null)
        {
            self::assertUriContainsValidRfc3986Characters($uri);
            self::assertUriContainsValidRfc3986Characters($baseUri);

            try {
                $uri = null !== $baseUri ? UriString::resolve($uri, $baseUri) : $uri;
                $components = UriString::parse($uri);
            } catch (Exception $exception) {
                throw new InvalidUriException($exception->getMessage(), previous: $exception);
            }

            $this->rawComponents = self::addUserInfo($components);
            $this->rawUri = $uri;
            $this->normalizedUri = null;
            $this->normalizedComponents = self::DEFAULT_COMPONENTS;
        }

        /**
         * @throws InvalidUriException
         */
        private static function assertUriContainsValidRfc3986Characters(?string $uri): void
        {
            null === $uri
            || 1 === preg_match('/^(?:[A-Za-z0-9\-._~:\/?#[\]@!$&\'()*+,;=%]|%[0-9A-Fa-f]{2})*$/', $uri)
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

        private function setNormalizedComponents(): void
        {
            if (self::DEFAULT_COMPONENTS === $this->normalizedComponents) {
                $this->normalizedComponents = self::addUserInfo(UriString::parseNormalized($this->toRawString()));
                // We convert the host separately because the current RFC does not handle IDNA
                $this->normalizedComponents['host'] = Encoder::normalizeHost($this->rawComponents['host']);
            }
        }

        /**
         * @param self::TYPE_RAW|self::TYPE_NORMALIZED $type
         */
        private function getComponent(string $name, string $type): ?string
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
            return $this->getComponent('scheme', self::TYPE_NORMALIZED);
        }

        public function getRawScheme(): ?string
        {
            return $this->getComponent('scheme', self::TYPE_RAW);
        }

        /**
         * @throws InvalidUriException
         */
        public function withScheme(?string $encodedScheme): self
        {
            return match (true) {
                $encodedScheme === $this->getRawScheme() => $this,
                UriString::isScheme($encodedScheme) => $this->withComponent(['scheme' => $encodedScheme]),
                default => throw new InvalidUriException('The scheme string component `'.$encodedScheme.'` is an invalid scheme.'),
            };
        }

        public function getUserInfo(): ?string
        {
            return $this->getComponent('userInfo', self::TYPE_NORMALIZED);
        }

        public function getRawUserInfo(): ?string
        {
            return $this->getComponent('userInfo', self::TYPE_RAW);
        }

        /**
         * @throws InvalidUriException
         */
        public function withUserInfo(#[SensitiveParameter] ?string $encodedUserInfo): self
        {
            if ($encodedUserInfo === $this->getRawUserInfo()) {
                return $this;
            }

            if (null === $encodedUserInfo) {
                return $this->withComponent(['user' => null, 'pass' => null]);
            }

            [$user, $password] = explode(':', $encodedUserInfo, 2) + [1 => null];
            if (!Encoder::isUserEncoded($user) || !Encoder::isPasswordEncoded($password)) {
                throw new InvalidUriException('The encoded userInfo string component contains invalid characters.');
            }

            return $this->withComponent(['user' => $user, 'pass' => $password]);
        }

        public function getRawUser(): ?string
        {
            return $this->getComponent('user', self::TYPE_RAW);
        }

        public function getUser(): ?string
        {
            return $this->getComponent('user', self::TYPE_NORMALIZED);
        }

        public function getRawPassword(): ?string
        {
            return $this->getComponent('pass', self::TYPE_RAW);
        }

        public function getPassword(): ?string
        {
            return $this->getComponent('pass', self::TYPE_NORMALIZED);
        }

        public function getRawHost(): ?string
        {
            return $this->getComponent('host', self::TYPE_RAW);
        }

        public function getHost(): ?string
        {
            return $this->getComponent('host', self::TYPE_NORMALIZED);
        }

        /**
         * @throws InvalidUriException
         */
        public function withHost(?string $encodedHost): self
        {
            return match (true) {
                $encodedHost === $this->getRawHost() => $this,
                UriString::isHost($encodedHost) => $this->withComponent(['host' => $encodedHost]),
                default => throw new InvalidUriException('The host component value `'.$encodedHost.'` is not a valid host.'),
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

        public function getRawPath(): ?string
        {
            return $this->getComponent('path', self::TYPE_RAW);
        }

        public function getPath(): ?string
        {
            return $this->getComponent('path', self::TYPE_NORMALIZED);
        }

        /**
         * @throws InvalidUriException
         */
        public function withPath(?string $encodedPath): self
        {
            return match (true) {
                $encodedPath === $this->getRawPath() => $this,
                Encoder::isPathEncoded($encodedPath) => $this->withComponent(['path' => $encodedPath]),
                default => throw new InvalidUriException('The encoded path component `'.$encodedPath.'` contains invalid characters.'),
            };
        }

        public function getRawQuery(): ?string
        {
            return $this->getComponent('query', self::TYPE_RAW);
        }

        public function getQuery(): ?string
        {
            return $this->getComponent('query', self::TYPE_NORMALIZED);
        }

        /**
         * @throws InvalidUriException
         */
        public function withQuery(?string $encodedQuery): self
        {
            return match (true) {
                $encodedQuery === $this->getRawQuery() => $this,
                Encoder::isQueryEncoded($encodedQuery) => $this->withComponent(['query' => $encodedQuery]),
                default => throw new InvalidUriException('The encoded query string component `'.$encodedQuery.'` contains invalid characters.'),
            };
        }

        public function getRawFragment(): ?string
        {
            return $this->getComponent('fragment', self::TYPE_RAW);
        }

        public function getFragment(): ?string
        {
            return $this->getComponent('fragment', self::TYPE_NORMALIZED);
        }

        /**
         * @throws InvalidUriException
         */
        public function withFragment(?string $encodedFragment): self
        {
            return match (true) {
                $encodedFragment === $this->getRawFragment() => $this,
                Encoder::isFragmentEncoded($encodedFragment) => $this->withComponent(['fragment' => $encodedFragment]),
                default => throw new InvalidUriException('The encoded fragment string component `'.$encodedFragment.'` contains invalid characters.'),
            };
        }

        /**
         * @throws Exception
         */
        public function equals(self $uri, bool $excludeFragment = true): bool
        {
            if ($excludeFragment && ($this->getFragment() !== $uri->getFragment())) {
                return [...$this->normalizedComponents, ...['fragment' => null]] === [...$uri->normalizedComponents, ...['fragment' => null]];
            }

            return $this->normalizedComponents === $uri->normalizedComponents;
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
         * @return array{__uri: string}
         */
        public function __serialize(): array
        {
            return ['__uri' => $this->toRawString()];
        }

        /**
         * @param array{__uri: string} $data
         *
         * @throws Exception|InvalidUriException
         */
        public function __unserialize(array $data): void
        {
            $uri = new self($data['__uri'] ?? throw new Exception('The `__uri` property is missing from the serialized object.'));

            $this->rawComponents = $uri->rawComponents;
            $this->rawUri = $uri->rawUri;
            $this->normalizedComponents = self::DEFAULT_COMPONENTS;
            $this->normalizedUri = null;
        }

        /**
         * @return array{scheme: ?string, user: ?string, password: ?string, host: ?string, port: ?int, path: ?string, query: ?string, fragment: ?string}
         */
        public function __debugInfo(): array
        {
            return [
                'scheme' => $this->rawComponents['scheme'],
                'user' => $this->rawComponents['user'],
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
