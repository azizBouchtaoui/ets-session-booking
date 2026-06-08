<?php

namespace App\EventListener;

use App\Exception\ApiException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class ExceptionListener implements EventSubscriberInterface
{
    public function __construct(private readonly string $environment)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onKernelException'];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();

        if ($exception instanceof ApiException) {
            $event->setResponse(new JsonResponse(
                ['message' => $exception->getMessage()],
                $exception->getStatusCode()
            ));

            return;
        }

        $prev = $exception->getPrevious();
        if ($prev instanceof ValidationFailedException) {
            $errors = [];
            foreach ($prev->getViolations() as $violation) {
                $field = $violation->getPropertyPath() ?: 'global';
                $errors[$field] = $violation->getMessage();
            }

            $event->setResponse(new JsonResponse(['errors' => $errors], 422));

            return;
        }

        if ($exception instanceof HttpExceptionInterface) {
            $event->setResponse(new JsonResponse(
                ['message' => $exception->getMessage() ?: 'HTTP error.'],
                $exception->getStatusCode()
            ));

            return;
        }

        $message = $this->environment === 'dev'
            ? $exception->getMessage()
            : 'Internal server error.';

        $event->setResponse(new JsonResponse(['message' => $message], 500));
    }
}
