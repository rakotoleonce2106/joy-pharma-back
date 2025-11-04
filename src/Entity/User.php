<?php
// api/src/Entity/User.php

namespace App\Entity;


use App\Entity\Traits\EntityIdTrait;
use App\Entity\Traits\EntityStatusTrait;
use App\Entity\Traits\EntityTimestampTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Entity\File as EmbeddedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity('email')]
#[Vich\Uploadable]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{


    use EntityIdTrait;
    use EntityStatusTrait;
    use EntityTimestampTrait;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['user:read', 'user:create'])]
    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private ?string $password = null;

    #[Assert\NotBlank(groups: ['user:create'])]
    #[Groups(['user:create'])]
    private ?string $plainPassword = null;

    #[ORM\Column(type: 'json')]
    #[Groups(['user:read', 'user:create', 'user:update'])]
    private array $roles = [];

    #[ORM\Column(length: 255)]
    #[Groups(['user:read', 'user:create', 'user:update'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:read', 'user:create', 'user:update'])]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read', 'user:create', 'user:update'])]
    private ?string $phone = null;


    #[Vich\UploadableField(mapping: 'profile', fileNameProperty: 'image.name', size: 'image.size')]
    #[Groups(['user:create', 'user:update'])]
    private ?File $imageFile = null;

    #[ORM\Embedded(class: 'Vich\UploaderBundle\Entity\File')]
    #[Groups(['user:read'])]
    private ?EmbeddedFile $image = null;

    /**
     * @var Collection<int, Order>
     */
    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'owner')]
    private Collection $orders;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $facebookId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $googleId = null;

    #[ORM\ManyToOne(inversedBy: 'owner')]
    #[Groups(['user:read'])]
    private ?Store $store = null;

    /**
     * @var Collection<int, Order>
     */
    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'deliver')]
    private Collection $deliverOrders;

    /**
     * @var Collection<int, Favorite>
     */
    #[ORM\OneToMany(targetEntity: Favorite::class, mappedBy: 'user')]
    private Collection $favorites;

    /**
     * @var Collection<int, Cart>
     */
    #[ORM\OneToMany(targetEntity: Cart::class, mappedBy: 'user')]
    private Collection $carts;

    // Delivery Person Fields
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['user:read', 'user:update'])]
    private bool $isOnline = false;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 8, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $currentLatitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 8, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $currentLongitude = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['user:read'])]
    private ?\DateTimeInterface $lastLocationUpdate = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    #[Groups(['user:read'])]
    private int $totalDeliveries = 0;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Groups(['user:read'])]
    private ?float $averageRating = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => 0])]
    #[Groups(['user:read'])]
    private string $totalEarnings = '0.00';

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['user:read', 'user:update'])]
    private ?string $vehicleType = null; // bike, motorcycle, car

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['user:read', 'user:update'])]
    private ?string $vehiclePlate = null;

    // Delivery verification documents
    #[Vich\UploadableField(mapping: 'delivery_residence', fileNameProperty: 'residenceDocument.name', size: 'residenceDocument.size')]
    private ?File $residenceDocumentFile = null;

    #[ORM\Embedded(class: 'Vich\\UploaderBundle\\Entity\\File')]
    #[Groups(['user:read'])]
    private ?EmbeddedFile $residenceDocument = null;

    #[Vich\UploadableField(mapping: 'delivery_vehicle', fileNameProperty: 'vehicleDocument.name', size: 'vehicleDocument.size')]
    private ?File $vehicleDocumentFile = null;

    #[ORM\Embedded(class: 'Vich\\UploaderBundle\\Entity\\File')]
    #[Groups(['user:read'])]
    private ?EmbeddedFile $vehicleDocument = null;

    /**
     * @var Collection<int, Notification>
     */
    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'user')]
    private Collection $notifications;

    /**
     * @var Collection<int, DeliverySchedule>
     */
    #[ORM\OneToMany(targetEntity: DeliverySchedule::class, mappedBy: 'deliveryPerson')]
    private Collection $deliverySchedules;

    /**
     * @var Collection<int, Invoice>
     */
    #[ORM\OneToMany(targetEntity: Invoice::class, mappedBy: 'deliveryPerson')]
    private Collection $invoices;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->image = new EmbeddedFile();
        $this->orders = new ArrayCollection();
        $this->deliverOrders = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->carts = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->deliverySchedules = new ArrayCollection();
        $this->invoices = new ArrayCollection();
        $this->residenceDocument = new EmbeddedFile();
        $this->vehicleDocument = new EmbeddedFile();
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function getFullName(): ?string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }



    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImage(EmbeddedFile $image): void
    {
        $this->image = $image;
    }

    public function getImage(): ?EmbeddedFile
    {
        return $this->image;
    }

    public function setResidenceDocumentFile(?File $file = null): void
    {
        $this->residenceDocumentFile = $file;
        if ($file !== null) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getResidenceDocumentFile(): ?File
    {
        return $this->residenceDocumentFile;
    }

    public function getResidenceDocument(): ?EmbeddedFile
    {
        return $this->residenceDocument;
    }

    public function setResidenceDocument(EmbeddedFile $file): void
    {
        $this->residenceDocument = $file;
    }

    public function setVehicleDocumentFile(?File $file = null): void
    {
        $this->vehicleDocumentFile = $file;
        if ($file !== null) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getVehicleDocumentFile(): ?File
    {
        return $this->vehicleDocumentFile;
    }

    public function getVehicleDocument(): ?EmbeddedFile
    {
        return $this->vehicleDocument;
    }

    public function setVehicleDocument(EmbeddedFile $file): void
    {
        $this->vehicleDocument = $file;
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
            $order->setOwner($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getOwner() === $this) {
                $order->setOwner(null);
            }
        }

        return $this;
    }

    public function getFacebookId(): ?string
    {
        return $this->facebookId;
    }

    public function setFacebookId(?string $facebookId): static
    {
        $this->facebookId = $facebookId;

        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): static
    {
        $this->googleId = $googleId;

        return $this;
    }

    public function getStore(): ?Store
    {
        return $this->store;
    }

    public function setStore(?Store $store): static
    {
        $this->store = $store;

        return $this;
    }

    /**
     * @return Collection<int, Order>
     */
    public function getDeliverOrders(): Collection
    {
        return $this->deliverOrders;
    }

    public function addDeliverOrder(Order $deliverOrder): static
    {
        if (!$this->deliverOrders->contains($deliverOrder)) {
            $this->deliverOrders->add($deliverOrder);
            $deliverOrder->setDeliver($this);
        }

        return $this;
    }

    public function removeDeliverOrder(Order $deliverOrder): static
    {
        if ($this->deliverOrders->removeElement($deliverOrder)) {
            // set the owning side to null (unless already changed)
            if ($deliverOrder->getDeliver() === $this) {
                $deliverOrder->setDeliver(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Favorite>
     */
    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    public function addFavorite(Favorite $favorite): static
    {
        if (!$this->favorites->contains($favorite)) {
            $this->favorites->add($favorite);
            $favorite->setUser($this);
        }

        return $this;
    }

    public function removeFavorite(Favorite $favorite): static
    {
        if ($this->favorites->removeElement($favorite)) {
            // set the owning side to null (unless already changed)
            if ($favorite->getUser() === $this) {
                $favorite->getUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Cart>
     */
    public function getCarts(): Collection
    {
        return $this->carts;
    }

    public function addCart(Cart $cart): static
    {
        if (!$this->carts->contains($cart)) {
            $this->carts->add($cart);
            $cart->setUser($this);
        }

        return $this;
    }

    public function removeCart(Cart $cart): static
    {
        if ($this->carts->removeElement($cart)) {
            // set the owning side to null (unless already changed)
            if ($cart->getUser() === $this) {
                $cart->setUser(null);
            }
        }

        return $this;
    }

    public function getIsOnline(): bool
    {
        return $this->isOnline;
    }


    public function setIsOnline(bool $isOnline): static
    {
        $this->isOnline = $isOnline;
        return $this;
    }

    public function getCurrentLatitude(): ?string
    {
        return $this->currentLatitude;
    }

    public function setCurrentLatitude(?string $currentLatitude): static
    {
        $this->currentLatitude = $currentLatitude;
        return $this;
    }

    public function getCurrentLongitude(): ?string
    {
        return $this->currentLongitude;
    }

    public function setCurrentLongitude(?string $currentLongitude): static
    {
        $this->currentLongitude = $currentLongitude;
        return $this;
    }

    public function getLastLocationUpdate(): ?\DateTimeInterface
    {
        return $this->lastLocationUpdate;
    }

    public function setLastLocationUpdate(?\DateTimeInterface $lastLocationUpdate): static
    {
        $this->lastLocationUpdate = $lastLocationUpdate;
        return $this;
    }

    public function getTotalDeliveries(): int
    {
        return $this->totalDeliveries;
    }

    public function setTotalDeliveries(int $totalDeliveries): static
    {
        $this->totalDeliveries = $totalDeliveries;
        return $this;
    }

    public function incrementTotalDeliveries(): static
    {
        $this->totalDeliveries++;
        return $this;
    }

    public function getAverageRating(): ?float
    {
        return $this->averageRating;
    }

    public function setAverageRating(?float $averageRating): static
    {
        $this->averageRating = $averageRating;
        return $this;
    }

    public function getTotalEarnings(): string
    {
        return $this->totalEarnings;
    }

    public function setTotalEarnings(string $totalEarnings): static
    {
        $this->totalEarnings = $totalEarnings;
        return $this;
    }

    public function addEarnings(float $amount): static
    {
        $this->totalEarnings = bcadd($this->totalEarnings, (string)$amount, 2);
        return $this;
    }

    public function getVehicleType(): ?string
    {
        return $this->vehicleType;
    }

    public function setVehicleType(?string $vehicleType): static
    {
        $this->vehicleType = $vehicleType;
        return $this;
    }

    public function getVehiclePlate(): ?string
    {
        return $this->vehiclePlate;
    }

    public function setVehiclePlate(?string $vehiclePlate): static
    {
        $this->vehiclePlate = $vehiclePlate;
        return $this;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setUser($this);
        }
        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            if ($notification->getUser() === $this) {
                $notification->setUser(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, DeliverySchedule>
     */
    public function getDeliverySchedules(): Collection
    {
        return $this->deliverySchedules;
    }

    public function addDeliverySchedule(DeliverySchedule $schedule): static
    {
        if (!$this->deliverySchedules->contains($schedule)) {
            $this->deliverySchedules->add($schedule);
            $schedule->setDeliveryPerson($this);
        }
        return $this;
    }

    public function removeDeliverySchedule(DeliverySchedule $schedule): static
    {
        if ($this->deliverySchedules->removeElement($schedule)) {
            if ($schedule->getDeliveryPerson() === $this) {
                $schedule->setDeliveryPerson(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    public function addInvoice(Invoice $invoice): static
    {
        if (!$this->invoices->contains($invoice)) {
            $this->invoices->add($invoice);
            $invoice->setDeliveryPerson($this);
        }
        return $this;
    }

    public function removeInvoice(Invoice $invoice): static
    {
        if ($this->invoices->removeElement($invoice)) {
            if ($invoice->getDeliveryPerson() === $this) {
                $invoice->setDeliveryPerson(null);
            }
        }
        return $this;
    }
}
