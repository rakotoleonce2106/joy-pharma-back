<?php

namespace App\Service;

use App\Entity\Manufacturer;
use App\Repository\ManufacturerRepository;
use Doctrine\ORM\EntityManagerInterface;


readonly class ManufacturerService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private ManufacturerRepository $manufacturerRepository
    )
    {
    }

    public function createManufacturer(Manufacturer $manufacturer): void
    {
        $this->manager->persist($manufacturer);
        $this->manager->flush();
    }


    public function getOrCreateManufacturerByName(string $name): Manufacturer
    {
        $manufacturer = $this->manufacturerRepository->findOneBy(['name' => $name]);
        if (!$manufacturer) {
            $manufacturer = new Manufacturer();
            $manufacturer->setName($name);
            $this->manager->persist($manufacturer);
        }
        return $manufacturer;
    }


    public function updateManufacturer(Manufacturer $Manufacturer): void
    {
        $this->manager->flush();
    }

    public function findByName(string $name): ?Manufacturer
    {
        return $this->manager->getRepository(Manufacturer::class)
            ->findOneBy(['name' => $name]);
    }

    public function batchDeleteManufacturers(array $manufacturerIds): void
    {


        foreach ($manufacturerIds as $id) {
            $manufacturer = $this->manufacturerRepository->find($id);
            if ($manufacturer) {
                $this->deleteManufacturer($manufacturer);

            }
        }


    }

    public function deleteManufacturer(Manufacturer $manufacturer): void
    {
        $this->manager->remove($manufacturer);
        $this->manager->flush();
    }
}
