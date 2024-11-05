<?php

namespace Kroesen\LaravelAdditions\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as BaseEncrypter;

class EncryptCookies extends BaseEncrypter
{

    public function decryptEncryptedCookie(string $name, array|string $cookie): array|string
    {
        $value = $this->decryptCookie($name, $cookie);
        return $this->validateValue($name, $value);
    }
}
