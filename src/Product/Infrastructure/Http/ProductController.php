<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Http;

use App\Product\Application\Command\CreateProduct\CreateProductCommand;
use App\Product\Application\Command\DeleteProduct\DeleteProductCommand;
use App\Product\Application\Command\UpdateProduct\UpdateProductCommand;
use App\Product\Application\Query\GetProduct\GetProductQuery;
use App\Product\Application\Query\ListProducts\ListProductsQuery;
use App\Product\Application\Query\ReadModel\ProductView;
use App\Product\Domain\Model\Product;
use App\Product\Infrastructure\Http\Dto\CreateProductDto;
use App\Product\Infrastructure\Http\Dto\UpdateProductDto;
use App\Product\Infrastructure\Security\ProductVoter;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/products')]
class ProductController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json($this->queryBus->ask(new ListProductsQuery()));
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        return $this->json($this->queryBus->ask(new GetProductQuery($id)));
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateProductDto $dto): JsonResponse
    {
        $this->denyAccessUnlessGranted(ProductVoter::CREATE);

        $product = $this->commandBus->dispatch(
            new CreateProductCommand($dto->name, $dto->description, $dto->price, $dto->stock),
        );
        \assert($product instanceof Product);

        return $this->json(ProductView::fromDomain($product), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, #[MapRequestPayload] UpdateProductDto $dto): JsonResponse
    {
        $this->denyAccessUnlessGranted(ProductVoter::UPDATE);

        $product = $this->commandBus->dispatch(
            new UpdateProductCommand($id, $dto->name, $dto->description, $dto->price),
        );
        \assert($product instanceof Product);

        return $this->json(ProductView::fromDomain($product));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted(ProductVoter::DELETE);

        $this->commandBus->dispatch(new DeleteProductCommand($id));

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
