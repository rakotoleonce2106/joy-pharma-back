<?php

namespace App\Traits;

use App\Service\ToastService;

trait ToastTrait
{
    private ToastService $toastService;

    public function setToastService(ToastService $toastService): void
    {
        $this->toastService = $toastService;
    }

    public function addToast(string $type, string $message, string $description = null, array $options = []): void
    {
        $this->toastService->addToast($type, $message, $description, $options);
    }

    public function addSuccessToast(string $message, string $description = null, array $options = []): void
    {
        $this->addToast('success', $message, $description, $options);
    }

    public function addErrorToast(string $message, string $description = null, array $options = []): void
    {
        $this->addToast('error', $message, $description, $options);
    }

    public function addInfoToast(string $message, string $description = null, array $options = []): void
    {
        $this->addToast('info', $message, $description, $options);
    }

    public function addWarningToast(string $message, string $description = null, array $options = []): void
    {
        $this->addToast('warning', $message, $description, $options);
    }
}
