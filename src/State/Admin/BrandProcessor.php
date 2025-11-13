<?php

namespace App\State\Admin;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Admin\BrandInput;
use App\Entity\Brand;
use App\Repository\BrandRepository;
use App\Service\BrandService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BrandProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly BrandService $brandService,
        private readonly BrandRepository $brandRepository
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Brand
    {
        if (!$data instanceof BrandInput) {
            throw new BadRequestHttpException('Invalid input data');
        }

        $isUpdate = isset($uriVariables['id']);
        
        if ($isUpdate) {
            $brand = $this->brandRepository->find($uriVariables['id']);
            if (!$brand) {
                throw new NotFoundHttpException('Brand not found');
            }
        } else {
            $brand = new Brand();
        }

        $brand->setName($data->name);

        if ($isUpdate) {
            $this->brandService->updateBrand($brand);
        } else {
            $this->brandService->createBrand($brand);
        }

        return $brand;
    }
}

