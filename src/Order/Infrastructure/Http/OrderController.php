<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Http;

use App\Order\Application\Command\PayOrder\PayOrderCommand;
use App\Order\Application\Command\PlaceOrder\OrderItemData;
use App\Order\Application\Command\PlaceOrder\PlaceOrderCommand;
use App\Order\Application\Query\GetOrder\GetOrderQuery;
use App\Order\Application\Query\ListOrders\ListOrdersQuery;
use App\Order\Application\Query\ReadModel\OrderView;
use App\Order\Domain\Model\Order;
use App\Order\Infrastructure\Http\Dto\PlaceOrderDto;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
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
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json($this->queryBus->ask(new ListOrdersQuery()));
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        return $this->json($this->queryBus->ask(new GetOrderQuery($id)));
    }

    #[Route('/{id}/pay', methods: ['PATCH'])]
    public function pay(int $id): JsonResponse
    {
        $order = $this->commandBus->dispatch(new PayOrderCommand($id));
        \assert($order instanceof Order);

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

        $order = $this->commandBus->dispatch(new PlaceOrderCommand($userId, $items));
        \assert($order instanceof Order);

        return $this->json(OrderView::fromDomain($order), Response::HTTP_CREATED);
    }
}
