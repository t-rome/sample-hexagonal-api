<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Http;

use App\Product\Application\Command\CreateProduct\CreateProductCommand;
use App\Product\Application\Command\CreateProduct\CreateProductHandler;
use App\Product\Application\Command\DeleteProduct\DeleteProductCommand;
use App\Product\Application\Command\DeleteProduct\DeleteProductHandler;
use App\Product\Application\Command\UpdateProduct\UpdateProductCommand;
use App\Product\Application\Command\UpdateProduct\UpdateProductHandler;
use App\Product\Application\Query\GetProduct\GetProductHandler;
use App\Product\Application\Query\GetProduct\GetProductQuery;
use App\Product\Application\Query\GetProduct\ProductView;
use App\Product\Application\Query\ListProducts\ListProductsHandler;
use App\Product\Application\Query\ListProducts\ListProductsQuery;
use App\Product\Infrastructure\Http\Dto\CreateProductDto;
use App\Product\Infrastructure\Http\Dto\UpdateProductDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/products')]
class ProductController extends AbstractController
{
    public function __construct(
        private readonly ListProductsHandler $listHandler,
        private readonly GetProductHandler $getHandler,
        private readonly CreateProductHandler $createHandler,
        private readonly UpdateProductHandler $updateHandler,
        private readonly DeleteProductHandler $deleteHandler,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json($this->listHandler->handle(new ListProductsQuery()));
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        return $this->json($this->getHandler->handle(new GetProductQuery($id)));
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateProductDto $dto): JsonResponse
    {
        $product = $this->createHandler->handle(
            new CreateProductCommand($dto->name, $dto->description, $dto->price, $dto->stock),
        );

        return $this->json(ProductView::fromDomain($product), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, #[MapRequestPayload] UpdateProductDto $dto): JsonResponse
    {
        $product = $this->updateHandler->handle(
            new UpdateProductCommand($id, $dto->name, $dto->description, $dto->price),
        );

        return $this->json(ProductView::fromDomain($product));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteProductCommand($id));

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
