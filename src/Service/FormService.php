<?php

namespace App\Service;

use App\Entity\Form;
use App\Repository\FormRepository;
use Doctrine\ORM\EntityManagerInterface;


readonly class FormService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private FormRepository         $formRepository
    )
    {
    }

    public function createForm(Form $Form): void
    {
        $this->manager->persist($Form);
        $this->manager->flush();
    }


    public function getOrCreateFormByName(string $name): Form
    {
        $form = $this->formRepository->findOneBy(['label' => $name]);
        if (!$form) {
            $form = new Form();
            $form->setLabel($name);
            $this->manager->persist($form);
        }
        return $form;
    }


    public function updateForm(Form $Form): void
    {
        $this->manager->flush();
    }

    public function findByName(string $name): ?Form
    {
        return $this->manager->getRepository(Form::class)
            ->findOneBy(['name' => $name]);
    }

    public function batchDeleteForms(array $formIds): void
    {


        foreach ($formIds as $id) {
            $form = $this->formRepository->find($id);
            if ($form) {
                $this->deleteForm($form);

            }
        }

    }

    public function deleteForm(Form $form): void
    {
        $this->manager->remove($form);
        $this->manager->flush();
    }
}
