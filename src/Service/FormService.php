<?php

namespace App\Service;

use App\Entity\Form;
use App\Entity\Product;
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

    public function batchDeleteForms(array $formIds): array
    {
        $successCount = 0;
        $failureCount = 0;

        if (empty($formIds)) {
            return [
                'success_count' => 0,
                'failure_count' => 0
            ];
        }

        foreach ($formIds as $id) {
            try {
                $form = $this->formRepository->find($id);
                if ($form) {
                    $this->deleteFormRelations($form);
                    $this->manager->remove($form);
                    $successCount++;
                } else {
                    $failureCount++;
                }
            } catch (\Exception $e) {
                $failureCount++;
                error_log('Failed to delete form with id ' . $id . ': ' . $e->getMessage());
            }
        }

        try {
            $this->manager->flush();
        } catch (\Exception $e) {
            error_log('Failed to flush form deletions: ' . $e->getMessage());
            throw $e;
        }

        return [
            'success_count' => $successCount,
            'failure_count' => $failureCount
        ];
    }

    public function deleteForm(Form $form): void
    {
        $this->deleteFormRelations($form);
        $this->manager->remove($form);
        $this->manager->flush();
    }

    /**
     * Remove all product associations before deleting the form
     */
    private function deleteFormRelations(Form $form): void
    {
        // Find all products with this form and set form to null
        $products = $this->manager->getRepository(Product::class)
            ->createQueryBuilder('p')
            ->where('p.form = :form')
            ->setParameter('form', $form)
            ->getQuery()
            ->getResult();

        foreach ($products as $product) {
            $product->setForm(null);
        }
    }
}
