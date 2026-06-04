<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Http;

use App\Order\Application\Command\PayOrder\PayOrderCommand;
use App\Order\Application\Command\PayOrder\PayOrderHandler;
use App\Order\Application\Command\PlaceOrder\OrderItemData;
use App\Order\Application\Command\PlaceOrder\PlaceOrderCommand;
use App\Order\Application\Command\PlaceOrder\PlaceOrderHandler;
use App\Order\Application\Query\GetOrder\GetOrderHandler;
use App\Order\Application\Query\GetOrder\GetOrderQuery;
use App\Order\Application\Query\ListOrders\ListOrdersHandler;
use App\Order\Application\Query\ListOrders\ListOrdersQuery;
use App\Order\Application\Query\ReadModel\OrderView;
use App\Order\Infrastructure\Http\Dto\PlaceOrderDto;
use App\User\Infrastructure\Persistence\UserRecord;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/orders')]
class OrderController extends AbstractController
{
    public function __construct(
        private readonly PlaceOrderHandler $placeHandler,
        private readonly PayOrderHandler $payHandler,
        private readonly GetOrderHandler $getHandler,
        private readonly ListOrdersHandler $listHandler,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json($this->listHandler->handle(new ListOrdersQuery()));
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        return $this->json($this->getHandler->handle(new GetOrderQuery($id)));
    }

    #[Route('/{id}/pay', methods: ['PATCH'])]
    public function pay(int $id): JsonResponse
    {
        $order = $this->payHandler->handle(new PayOrderCommand($id));

        return $this->json(OrderView::fromDomain($order));
    }

    #[Route('', methods: ['POST'])]
    public function place(#[MapRequestPayload] PlaceOrderDto $dto): JsonResponse
    {
        $userRecord = $this->getUser();
        \assert($userRecord instanceof UserRecord);
        $userId = $userRecord->id ?? throw new \LogicException('Authenticated user must have an id.');

        $items = array_map(
            static fn ($item) => new OrderItemData($item->productId, $item->quantity),
            $dto->items,
        );
        $order = $this->placeHandler->handle(new PlaceOrderCommand($userId, $items));

        return $this->json(OrderView::fromDomain($order), Response::HTTP_CREATED);
    }
}
