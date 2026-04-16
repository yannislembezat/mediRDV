<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Api\ApiResponseFactory;
use App\Api\ApiValidationException;
use App\Service\Exception\AvailabilitySlotUnavailableException;
use App\Service\Exception\WorkflowException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ApiResponseFactory $responseFactory,
        #[Autowire('%kernel.debug%')]
        private bool $debug = false,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();

        if ($exception instanceof ApiValidationException) {
            $event->setResponse(
                $this->responseFactory->validationError($exception->getErrors(), $exception->getMessage()),
            );

            return;
        }

        if ($exception instanceof BadRequestHttpException) {
            $event->setResponse(
                $this->responseFactory->error(
                    $exception->getMessage() !== '' ? $exception->getMessage() : 'La requete est invalide.',
                    400,
                ),
            );

            return;
        }

        if ($exception instanceof NotFoundHttpException) {
            $event->setResponse($this->responseFactory->error('La ressource demandee est introuvable.', 404));

            return;
        }

        if ($exception instanceof AccessDeniedHttpException || $exception instanceof AccessDeniedException) {
            $event->setResponse($this->responseFactory->error('Acces refuse.', 403));

            return;
        }

        if ($exception instanceof WorkflowException || $exception instanceof AvailabilitySlotUnavailableException) {
            $event->setResponse($this->responseFactory->error($exception->getMessage(), 422));

            return;
        }

        if ($exception instanceof \InvalidArgumentException) {
            $event->setResponse($this->responseFactory->error($exception->getMessage(), 422));

            return;
        }

        if ($exception instanceof HttpExceptionInterface) {
            $message = $exception->getMessage() !== '' ? $exception->getMessage() : 'Une erreur est survenue.';
            $event->setResponse($this->responseFactory->error($message, $exception->getStatusCode()));

            return;
        }

        $message = $this->debug && $exception->getMessage() !== ''
            ? $exception->getMessage()
            : 'Une erreur interne est survenue.';

        $event->setResponse($this->responseFactory->error($message, 500));
    }
}
