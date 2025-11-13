<?php

namespace App\State\Admin;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Admin\FormInput;
use App\Entity\Form;
use App\Repository\FormRepository;
use App\Service\FormService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FormProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly FormService $formService,
        private readonly FormRepository $formRepository
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Form
    {
        if (!$data instanceof FormInput) {
            throw new BadRequestHttpException('Invalid input data');
        }

        $isUpdate = isset($uriVariables['id']);
        
        if ($isUpdate) {
            $form = $this->formRepository->find($uriVariables['id']);
            if (!$form) {
                throw new NotFoundHttpException('Form not found');
            }
        } else {
            $form = new Form();
        }

        $form->setName($data->name);

        if ($isUpdate) {
            $this->formService->updateForm($form);
        } else {
            $this->formService->createForm($form);
        }

        return $form;
    }
}

