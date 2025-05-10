<?php

namespace App\Service;

use App\Entity\Unit;
use App\Repository\UnitRepository;
use Doctrine\ORM\EntityManagerInterface;



readonly class UnitService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private UnitRepository $unitRepository
    ) {
    }

    public function createUnit(Unit $unit): void
    {
        $this->manager->persist($unit);
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
        $this->manager->remove($unit);
        $this->manager->flush();
    }

    public function batchDeleteCategories(array $unitIds): void
    {


        foreach ($unitIds as $id) {
            $unit = $this->unitRepository->find($id);
            if ($unit) {
                $this->deleteUnit($unit);

            }
        }



    }
}
