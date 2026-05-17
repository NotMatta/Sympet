<?php

namespace App\Security;

use League\OAuth2\Client\Provider\Google;

class GoogleAuthenticator
{
    public function __construct(
        private readonly Google $provider,
    ) {
    }

    public function getProvider(): Google
    {
        return $this->provider;
    }
}
