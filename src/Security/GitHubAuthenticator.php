<?php

namespace App\Security;

use League\OAuth2\Client\Provider\Github;

class GitHubAuthenticator
{
    public function __construct(
        private readonly Github $provider,
    ) {
    }

    public function getProvider(): Github
    {
        return $this->provider;
    }
}
