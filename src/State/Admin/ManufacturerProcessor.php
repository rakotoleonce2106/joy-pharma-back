<?php

namespace App\State\Admin;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Admin\ManufacturerInput;
use App\Entity\Manufacturer;
use App\Repository\ManufacturerRepository;
use App\Service\ManufacturerService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ManufacturerProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ManufacturerService $manufacturerService,
        private readonly ManufacturerRepository $manufacturerRepository
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Manufacturer
    {
        if (!$data instanceof ManufacturerInput) {
            throw new BadRequestHttpException('Invalid input data');
        }

        $isUpdate = isset($uriVariables['id']);
        
        if ($isUpdate) {
            $manufacturer = $this->manufacturerRepository->find($uriVariables['id']);
            if (!$manufacturer) {
                throw new NotFoundHttpException('Manufacturer not found');
            }
        } else {
            $manufacturer = new Manufacturer();
        }

        $manufacturer->setName($data->name);
        $manufacturer->setDescription($data->description);

        if ($isUpdate) {
            $this->manufacturerService->updateManufacturer($manufacturer);
        } else {
            $this->manufacturerService->createManufacturer($manufacturer);
        }

        return $manufacturer;
    }
}

