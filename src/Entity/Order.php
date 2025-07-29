<?php

namespace App\Entity;

use App\Entity\Traits\EntityIdTrait;
use App\Entity\Traits\EntityTimestampTrait;
use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

enum OrderStatus :string
{
    case STATUS_PENDING = 'pending';
    case STATUS_CONFIRMED = 'confirmed';
    case STATUS_PROCESSING = 'processing';
    case STATUS_SHIPPED = 'shipped';
    case STATUS_DELIVERED = 'delivered';
    case STATUS_CANCELLED = 'cancelled';
}

enum PriorityType : string
{
     case PRIORITY_URGENT = 'urgent';
     case PRIORITY_STANDARD = 'standard';
     case PRIORITY_PLANIFIED = 'planified';

}

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{

    use EntityIdTrait;
    use EntityTimestampTrait;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[Groups(['order:create','order:read'])]
    private ?User $owner = null;

    #[ORM\ManyToOne(inversedBy: 'orders', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['order:create','order:read'])]
    private ?Location $location = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['order:create','order:read'])]
    private ?\DateTimeInterface $scheduledDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['order:create','order:read'])]
    private ?string $notes = null;

    #[ORM\Column(type: 'string', length: 20, enumType: OrderStatus::class)]
    #[Groups(['order:create','order:read'])]
    private OrderStatus $status;

    #[ORM\Column(type: 'string', length: 20, enumType: PriorityType::class)]
    #[Groups(['order:create','order:read'])]
    private PriorityType $priority;

    /**
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'orderParent')]
    #[Groups(['order:create','order:read'])]
    private Collection $items;



    #[ORM\Column]
    #[Groups(['order:read'])]
    private ?float $totalAmount = null;

    #[ORM\Column(length: 255)]
    #[Groups(['order:read'])]
    private ?string $reference = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['order:read'])]
    private ?string $phone = null;

    #[ORM\ManyToOne(inversedBy: 'orders', cascade: ['persist', 'remove'])]
    #[Groups(['order:read'])]
    private ?Payment $payment = null;





     public function __construct()
    {
        $this->status = OrderStatus::STATUS_PENDING;
        $this->priority = PriorityType::PRIORITY_STANDARD;
        $this->createdAt = new \DateTime();
        $this->reference = $this->generateReference();
        $this->items = new ArrayCollection();
    }




    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }


    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getScheduledDate(): ?\DateTimeInterface
    {
        return $this->scheduledDate;
    }

    public function setScheduledDate(\DateTime $scheduledDate): static
    {
        $this->scheduledDate = $scheduledDate;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getTotalAmount(): ?float
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(float $totalAmount): static
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function setStatus(OrderStatus $status): self
    {
        $this->status = $status;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getPriority(): PriorityType
    {
        return $this->priority;
    }

    public function setPriority(PriorityType $priority): self
    {
        $this->priority = $priority;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    public function setPayment(?Payment $payment): static
    {
        $this->payment = $payment;

        return $this;
    }

    private function generateReference(): string
    {
        return 'ORD-' . date('Y') . '-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOrderParent($this);
        }

        return $this;
    }

    public function removeItem(OrderItem $item): static
    {
        if ($this->items->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getOrderParent() === $this) {
                $item->setOrderParent(null);
            }
        }

        return $this;
    }

}
