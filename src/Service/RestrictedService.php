<?php

namespace App\Service;

use App\Entity\Restricted;
use App\Repository\RestrictedRepository;
use Doctrine\ORM\EntityManagerInterface;



readonly class RestrictedService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private RestrictedRepository $restrictedRepository
    ) {
    }

    public function createRestricted(Restricted $restricted): void
    {
        $this->manager->persist($restricted);
        $this->manager->flush();
    }


    public function getOrCreateRestrictedByName(String $name): Restricted
    {
        $restricted = $this->restrictedRepository->findOneBy(['waitingFor' => $name]);
        if (!$restricted) {
            $restricted = new Restricted();
            $restricted->setWaitingFor($name);
            $this->manager->persist($restricted);
        }
        return $restricted;
    }


    public function updateRestricted(Restricted $restricted): void
    {
        $this->manager->flush();
    }

    public function findByName(String $name): ?Restricted
    {
        return $this->manager->getRepository(Restricted::class)
            ->findOneBy(['waitingFor' => $name]);
    }

    public function deleteRestricted(Restricted $restricted): void
    {
        $this->manager->remove($restricted);
        $this->manager->flush();
    }

    public function batchDeleteCategories(array $restrictedIds): void
    {


        foreach ($restrictedIds as $id) {
            $restricted = $this->restrictedRepository->find($id);
            if ($restricted) {
                $this->deleteRestricted($restricted);

            }
        }



    }
}
