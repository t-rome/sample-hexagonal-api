<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use App\Order\Domain\Exception\OrderNotFoundException;
use App\Product\Domain\Exception\InsufficientStockException;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\User\Domain\Exception\UserAlreadyExistsException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onKernelException'];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $response = $this->buildResponse($exception);

        if (null === $response) {
            return;
        }

        if ($response->getStatusCode() >= 500) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
        } else {
            $this->logger->info($exception->getMessage(), ['exception_class' => $exception::class]);
        }

        $event->setResponse($response);
    }

    private function buildResponse(\Throwable $exception): ?JsonResponse
    {
        if ($exception instanceof ProductNotFoundException || $exception instanceof OrderNotFoundException) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }

        if ($exception instanceof UserAlreadyExistsException) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_CONFLICT);
        }

        if ($exception instanceof InsufficientStockException) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $previous = $exception->getPrevious();
        if ($exception instanceof HttpExceptionInterface && $previous instanceof ValidationFailedException) {
            $violations = [];
            foreach ($previous->getViolations() as $violation) {
                $violations[] = [
                    'field' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }

            return new JsonResponse(
                ['error' => 'Validation failed', 'violations' => $violations],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return null;
    }
}
