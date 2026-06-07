<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Catches every unhandled exception and converts it to a JSON response via the
 * ExceptionMapperInterface strategy chain.
 *
 * When a request throws an exception that reaches the kernel, this subscriber
 * iterates through all registered mappers (injected as a tagged iterator) and
 * delegates to the first one that supports the exception. The first match wins;
 * remaining mappers are not called.
 *
 * If no mapper handles the exception, the event is left untouched and Symfony's
 * default error handling takes over (typically a 500 response in prod).
 *
 * Logging behaviour:
 *   ≥ 500  → error log (unexpected server fault, needs attention)
 *   < 500  → info log (expected client error, e.g. not found, validation)
 */
final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    /** @param iterable<ExceptionMapperInterface> $mappers */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly iterable $mappers,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onKernelException'];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        foreach ($this->mappers as $mapper) {
            if (!$mapper->supports($exception)) {
                continue;
            }

            $response = $mapper->toResponse($exception);

            if ($response->getStatusCode() >= 500) {
                $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            } else {
                $this->logger->info($exception->getMessage(), ['exception_class' => $exception::class]);
            }

            $event->setResponse($response);

            return;
        }
    }
}
