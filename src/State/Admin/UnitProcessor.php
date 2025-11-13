<?php

namespace App\State\Admin;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Admin\UnitInput;
use App\Entity\Unit;
use App\Repository\UnitRepository;
use App\Service\UnitService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UnitProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly UnitService $unitService,
        private readonly UnitRepository $unitRepository
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Unit
    {
        if (!$data instanceof UnitInput) {
            throw new BadRequestHttpException('Invalid input data');
        }

        $isUpdate = isset($uriVariables['id']);
        
        if ($isUpdate) {
            $unit = $this->unitRepository->find($uriVariables['id']);
            if (!$unit) {
                throw new NotFoundHttpException('Unit not found');
            }
        } else {
            $unit = new Unit();
        }

        $unit->setName($data->name);

        if ($isUpdate) {
            $this->unitService->updateUnit($unit);
        } else {
            $this->unitService->createUnit($unit);
        }

        return $unit;
    }
}

