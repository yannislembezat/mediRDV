<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Api\ApiResponseFactory;
use Gesdinet\JWTRefreshTokenBundle\Event\RefreshAuthenticationFailureEvent;
use Gesdinet\JWTRefreshTokenBundle\Event\RefreshTokenNotFoundEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTExpiredEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class ApiAuthenticationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ApiResponseFactory $responseFactory,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::AUTHENTICATION_FAILURE => 'onAuthenticationFailure',
            Events::JWT_NOT_FOUND => 'onJwtNotFound',
            Events::JWT_INVALID => 'onJwtInvalid',
            Events::JWT_EXPIRED => 'onJwtExpired',
            'gesdinet.refresh_token_failure' => 'onRefreshFailure',
            'gesdinet.refresh_token_not_found' => 'onRefreshTokenNotFound',
        ];
    }

    public function onAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        $event->setResponse($this->responseFactory->error('Email ou mot de passe invalide.', 401));
    }

    public function onJwtNotFound(JWTNotFoundEvent $event): void
    {
        $event->setResponse($this->responseFactory->error('Authentification requise.', 401));
    }

    public function onJwtInvalid(JWTInvalidEvent $event): void
    {
        $event->setResponse($this->responseFactory->error('Le jeton d\'acces est invalide.', 401));
    }

    public function onJwtExpired(JWTExpiredEvent $event): void
    {
        $event->setResponse($this->responseFactory->error('Le jeton d\'acces a expire.', 401));
    }

    public function onRefreshFailure(RefreshAuthenticationFailureEvent $event): void
    {
        $event->setResponse($this->responseFactory->error('Le refresh token est invalide ou a expire.', 401));
    }

    public function onRefreshTokenNotFound(RefreshTokenNotFoundEvent $event): void
    {
        $event->setResponse($this->responseFactory->error('Le refresh token est manquant.', 401));
    }
}
