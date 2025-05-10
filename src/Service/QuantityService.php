<?php

namespace App\Service;

use App\Entity\Quantity;
use App\Repository\QuantityRepository;
use Doctrine\ORM\EntityManagerInterface;


readonly class QuantityService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private QuantityRepository     $QuantityRepository
    )
    {
    }

    public function createQuantity(Quantity $Quantity): void
    {
        $this->manager->persist($Quantity);
    }


    public function updateQuantity(Quantity $Quantity): void
    {
        $this->manager->flush();
    }

    public function batchDeleteCategories(array $QuantityIds): void
    {


        foreach ($QuantityIds as $id) {
            $Quantity = $this->QuantityRepository->find($id);
            if ($Quantity) {
                $this->deleteQuantity($Quantity);

            }
        }


    }

    public function deleteQuantity(Quantity $Quantity): void
    {
        $this->manager->remove($Quantity);
        $this->manager->flush();
    }
}
