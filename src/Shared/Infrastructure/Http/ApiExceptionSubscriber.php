<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

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
