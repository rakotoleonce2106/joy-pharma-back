<?php

namespace App\Service;

use App\Entity\Unit;
use App\Entity\Product;
use App\Repository\UnitRepository;
use Doctrine\ORM\EntityManagerInterface;



readonly class UnitService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private UnitRepository $unitRepository
    ) {
    }

    public function getOrCreateUnit(String $label): ?Unit
    {
        $unit = $this->unitRepository->findOneBy(['label' => $label]);
        if ($unit) {
            return null;
        }
        $unit = new Unit();
        $unit->setLabel($label);
        $this->manager->persist($unit);
        return $unit;
    }


    public function getOrCreateUnitByName(String $name): Unit
    {
        $unit = $this->unitRepository->findOneBy(['label' => $name]);
        if (!$unit) {
            $unit = new Unit();
            $unit->setLabel($name);
            $this->manager->persist($unit);
        }
        return $unit;
    }


    public function updateUnit(Unit $unit): void
    {
        $this->manager->flush();
    }

    public function findByName(String $name): ?Unit
    {
        return $this->manager->getRepository(Unit::class)
            ->findOneBy(['label' => $name]);
    }

    public function deleteUnit(Unit $unit): void
    {
        $this->deleteUnitRelations($unit);
        $this->manager->remove($unit);
        $this->manager->flush();
    }

    public function batchDeleteUnits(array $unitIds): array
    {
        $successCount = 0;
        $failureCount = 0;

        if (empty($unitIds)) {
            return [
                'success_count' => 0,
                'failure_count' => 0
            ];
        }

        foreach ($unitIds as $id) {
            try {
                $unit = $this->unitRepository->find($id);
                if ($unit) {
                    $this->deleteUnitRelations($unit);
                    $this->manager->remove($unit);
                    $successCount++;
                } else {
                    $failureCount++;
                }
            } catch (\Exception $e) {
                $failureCount++;
                error_log('Failed to delete unit with id ' . $id . ': ' . $e->getMessage());
            }
        }

        try {
            $this->manager->flush();
        } catch (\Exception $e) {
            error_log('Failed to flush unit deletions: ' . $e->getMessage());
            throw $e;
        }

        return [
            'success_count' => $successCount,
            'failure_count' => $failureCount
        ];
    }

    /**
     * Remove all product associations before deleting the unit
     */
    private function deleteUnitRelations(Unit $unit): void
    {
        // Find all products with this unit and set unit to null
        $products = $this->manager->getRepository(Product::class)
            ->createQueryBuilder('p')
            ->where('p.unit = :unit')
            ->setParameter('unit', $unit)
            ->getQuery()
            ->getResult();

        foreach ($products as $product) {
            $product->setUnit(null);
        }
    }
}
