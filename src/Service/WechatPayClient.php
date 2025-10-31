<?php

declare(strict_types=1);

namespace WechatPayBundle\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class WechatPayClient
{
    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    public function getClient(): HttpClientInterface
    {
        return $this->httpClient;
    }
}
