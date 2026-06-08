<?php

declare(strict_types=1);

namespace Siroko\Shared\Infrastructure\Http;

use Siroko\Cart\Domain\Exception\CartItemNotFound;
use Siroko\Cart\Domain\Exception\CartNotFound;
use Siroko\Cart\Domain\Exception\EmptyCart;
use Siroko\Cart\Domain\Exception\InsufficientStock;
use Siroko\Cart\Domain\Exception\ProductNotInCatalog;
use Siroko\Order\Domain\Exception\IllegalOrderTransition;
use Siroko\Order\Domain\Exception\OrderNotFound;
use Siroko\Shared\Domain\Exception\InvalidCurrency;
use Siroko\Shared\Domain\Exception\InvalidIdentifier;
use Siroko\Shared\Domain\Exception\InvalidQuantity;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Traduce las excepciones de dominio a respuestas HTTP + JSON en un único sitio.
 * Los controllers quedan finos, sin try/catch (ver DECISIONS D-011).
 */
final class DomainExceptionListener implements EventSubscriberInterface
{
    /** @var array<class-string, int> */
    private const STATUS_MAP = [
        // 404 — recurso inexistente
        CartNotFound::class => Response::HTTP_NOT_FOUND,
        OrderNotFound::class => Response::HTTP_NOT_FOUND,
        ProductNotInCatalog::class => Response::HTTP_NOT_FOUND,
        CartItemNotFound::class => Response::HTTP_NOT_FOUND,
        // 400 — entrada inválida
        InvalidQuantity::class => Response::HTTP_BAD_REQUEST,
        InvalidCurrency::class => Response::HTTP_BAD_REQUEST,
        InvalidIdentifier::class => Response::HTTP_BAD_REQUEST,
        // 409 — conflicto con el estado actual
        EmptyCart::class => Response::HTTP_CONFLICT,
        InsufficientStock::class => Response::HTTP_CONFLICT,
        IllegalOrderTransition::class => Response::HTTP_CONFLICT,
    ];

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onKernelException'];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        $status = self::STATUS_MAP[$throwable::class] ?? null;

        if ($status === null) {
            return; // No es de dominio: lo gestiona Symfony.
        }

        $event->setResponse(new JsonResponse([
            'error' => $throwable->getMessage(),
        ], $status));
    }
}
