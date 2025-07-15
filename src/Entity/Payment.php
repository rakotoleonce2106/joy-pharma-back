<?php

namespace App\Entity;

use App\Repository\PaymentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

// Payment Status Enum
enum PaymentStatus: string
{
    case STATUS_PENDING = 'pending';
    case STATUS_PROCESSING = 'processing';
    case STATUS_COMPLETED = 'completed';
    case STATUS_FAILED = 'failed';
    case STATUS_REFUNDED = 'refunded';
}

// Payment Method Enum
enum PaymentMethod: string
{
    case METHODE_MVOLA = 'mvola';
    case METHODE_AIRTEL_MONEY = 'airtel_money';
    case METHODE_ORANGE_MONEY = 'orange_money'; 
    case METHOD_PAYPAL = 'paypal';
    case METHOD_STRIPE = 'stripe';
}

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
class Payment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $transactionId = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(length: 20, enumType: PaymentMethod::class)]
    private ?PaymentMethod $method = null;

    #[ORM\Column(length: 20, enumType: PaymentStatus::class)]
    private PaymentStatus $status;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $processedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $gatewayResponse = null;

    #[ORM\OneToOne(inversedBy: 'payment')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $order = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phoneNumber = null;

    /**
     * @var Collection<int, Order>
     */
    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'payment')]
    private Collection $orders;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->transactionId = $this->generateTransactionId();
        $this->status = PaymentStatus::STATUS_PENDING;
        $this->orders = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function setTransactionId(string $transactionId): static
    {
        $this->transactionId = $transactionId;
        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    public function getMethod(): ?PaymentMethod
    {
        return $this->method;
    }

    public function setMethod(PaymentMethod $method): static
    {
        $this->method = $method;
        return $this;
    }

    public function getStatus(): PaymentStatus
    {
        return $this->status;
    }

    public function setStatus(PaymentStatus $status): static
    {
        $this->status = $status;
        
        if ($this->status !== PaymentStatus::STATUS_PENDING && $this->processedAt === null) {
            $this->processedAt = new \DateTime();
        }
        
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getProcessedAt(): ?\DateTimeInterface
    {
        return $this->processedAt;
    }

    public function setProcessedAt(?\DateTimeInterface $processedAt): static
    {
        $this->processedAt = $processedAt;
        return $this;
    }

    public function getGatewayResponse(): ?string
    {
        return $this->gatewayResponse;
    }

    public function setGatewayResponse(?string $gatewayResponse): static
    {
        $this->gatewayResponse = $gatewayResponse;
        return $this;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(Order $order): static
    {
        $this->order = $order;
        return $this;
    }

    private function generateTransactionId(): string
    {
        return 'TXN-' . date('Y') . '-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    // Utility methods using enums
    public function isPending(): bool
    {
        return $this->status === PaymentStatus::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === PaymentStatus::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::STATUS_FAILED;
    }

    public function isRefunded(): bool
    {
        return $this->status === PaymentStatus::STATUS_REFUNDED;
    }

    public function isProcessing(): bool
    {
        return $this->status === PaymentStatus::STATUS_PROCESSING;
    }

    public function isSuccessful(): bool
    {
        return $this->status === PaymentStatus::STATUS_COMPLETED;
    }

    public function canBeRefunded(): bool
    {
        return $this->status === PaymentStatus::STATUS_COMPLETED;
    }

    public function requiresManualProcessing(): bool
    {
        return $this->method === PaymentMethod::METHODE_MVOLA || 
               $this->method === PaymentMethod::METHODE_AIRTEL_MONEY || 
               $this->method === PaymentMethod::METHODE_ORANGE_MONEY;
    }

    public function isOnlinePayment(): bool
    {
        return in_array($this->method, [
            PaymentMethod::METHOD_STRIPE,
            PaymentMethod::METHOD_PAYPAL
        ]);
    }

    // Static methods for form choices
    public static function getMethodChoices(): array
    {
        return [
            'Stripe' => PaymentMethod::METHOD_STRIPE,
            'PayPal' => PaymentMethod::METHOD_PAYPAL,
            'Mvola' => PaymentMethod::METHODE_MVOLA,
            'Airtel Money' => PaymentMethod::METHODE_AIRTEL_MONEY,
            'Orange Money' => PaymentMethod::METHODE_ORANGE_MONEY,
        ];
    }

    public static function getStatusChoices(): array
    {
        return [
            'Pending' => PaymentStatus::STATUS_PENDING,
            'Processing' => PaymentStatus::STATUS_PROCESSING,
            'Completed' => PaymentStatus::STATUS_COMPLETED,
            'Failed' => PaymentStatus::STATUS_FAILED,
            'Refunded' => PaymentStatus::STATUS_REFUNDED,
        ];
    }

    // Get all enum values
    public static function getAllStatuses(): array
    {
        return array_map(fn($case) => $case->value, PaymentStatus::cases());
    }

    public static function getAllMethods(): array
    {
        return array_map(fn($case) => $case->value, PaymentMethod::cases());
    }

    // Get human-readable labels
    public function getStatusLabel(): string
    {
        return match($this->status) {
            PaymentStatus::STATUS_PENDING => 'Pending',
            PaymentStatus::STATUS_PROCESSING => 'Processing',
            PaymentStatus::STATUS_COMPLETED => 'Completed',
            PaymentStatus::STATUS_FAILED => 'Failed',
            PaymentStatus::STATUS_REFUNDED => 'Refunded',
        };
    }

    public function getMethodLabel(): string
    {
        return match($this->method) {
            PaymentMethod::METHOD_STRIPE => 'Stripe',
            PaymentMethod::METHOD_PAYPAL => 'PayPal',
            PaymentMethod::METHODE_MVOLA => 'Mvola',
            PaymentMethod::METHODE_AIRTEL_MONEY => 'Airtel Money',
            PaymentMethod::METHODE_ORANGE_MONEY => 'Orange Money',
        };
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setPayment($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getPayment() === $this) {
                $order->setPayment(null);
            }
        }

        return $this;
    }
}
