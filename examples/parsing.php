<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Bakame\Polyfill\Rfc3986\Uri;

$uriList = [
    "https://🐘.com",
    "https://%61pple:p%61ss@ex%61mple.com/foob%61r?%61bc=%61bc#%61bc",
    'https://xn--GO8h.com',
    "https://你好你好.com",
    "https://%e4%bd%a0%e5%a5%bd%e4%bd%a0%e5%a5%bd.com",
    "HTTPS://////EXAMPLE.com",
    'HTTPS://EXAMPLE.com',
];

foreach ($uriList as $uriString) {
    $uri = new Uri($uriString);
    dump([
        'uri' => $uri->toString(),
        'normalized uri' => $uri->toNormalizedString(),
        'scheme' => $uri->getScheme(),
        'raw scheme' => $uri->getRawScheme(),
        'user info' => $uri->getUserInfo(),
        'raw user info' => $uri->getRawUserInfo(),
        'user' => $uri->getUser(),
        'raw userfo' => $uri->getRawUser(),
        'password' => $uri->getPassword(),
        'raw passoword' => $uri->getRawPassword(),
        'host' => $uri->getHost(),
        'raw host' => $uri->getRawHost(),
        'display host' => $uri->getHostForDisplay(),
        'path' => $uri->getPath(),
        'raw path' => $uri->getRawPath(),
        'query' => $uri->getQuery(),
        'raw query' => $uri->getRawQuery(),
        'fragment' => $uri->getFragment(),
        'raw fragment' => $uri->getRawFragment(),
    ]);
}
