<?php
// api/src/Entity/User.php

namespace App\Entity;


use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use App\Entity\Traits\EntityIdTrait;
use App\Entity\Traits\EntityStatusTrait;
use App\Entity\Traits\EntityTimestampTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\MediaObject;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity('email')]
#[ApiFilter(SearchFilter::class, properties: ['firstName' => 'ipartial', 'lastName' => 'ipartial', 'email' => 'ipartial'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{


    use EntityIdTrait;
    use EntityStatusTrait;
    use EntityTimestampTrait;

    #[Assert\NotBlank(groups: ['create'])]
    #[Assert\Email]
    #[Groups(['user:read', 'user:create', 'user:update', 'user:store:read'])]
    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    #[Groups(['user:create'])]
    private ?string $password = null;

    #[Groups(['user:create', 'user:update', 'user:update:profile'])]
    private ?string $plainPassword = null;

    #[ORM\Column(type: 'json')]
    #[Groups(['user:read', 'user:create', 'user:update', 'user:store:read'])]
    private array $roles = [];

    #[Assert\NotBlank(groups: ['create'])]
    #[ORM\Column(length: 255)]
    #[Groups(['user:read', 'user:create', 'user:update', 'user:update:profile', 'user:store:read'])]
    private ?string $firstName = null;

    #[Assert\NotBlank(groups: ['create'])]
    #[ORM\Column(length: 255)]
    #[Groups(['user:read', 'user:create', 'user:update', 'user:update:profile', 'user:store:read'])]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read', 'user:create', 'user:update', 'user:update:profile', 'user:store:read'])]
    private ?string $phone = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['user:read', 'user:update', 'user:update:profile'])]
    private ?string $fcmToken = null;

    #[ORM\Column(length: 6, nullable: true)]
    #[Groups(['user:read', 'user:create', 'user:update'])]
    private ?string $emailVerificationCode = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $emailVerificationCodeExpiresAt = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['user:read', 'user:create', 'user:update'])]
    private bool $isEmailVerified = false;


    #[ORM\ManyToOne(targetEntity: MediaObject::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['user:read', 'user:create', 'user:update', 'user:update:profile', 'user:store:read', 'media_object:read'])]
    #[ApiProperty(types: ['https://schema.org/image'], iris: [MediaObject::class])]
    private ?MediaObject $image = null;

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
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[Groups(['user:read'])]
    #[MaxDepth(1)]
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


    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Delivery::class, cascade: ['persist', 'remove'])]
    #[Groups(['user:read', 'user:update', 'user:update:profile'])]
    #[MaxDepth(1)]
    private ?Delivery $delivery = null;

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

    /**
     * @var Collection<int, Location>
     */
    #[ORM\ManyToMany(targetEntity: Location::class, cascade: ['persist'])]
    #[ORM\JoinTable(name: 'user_locations')]
    #[Groups(['user:read'])]
    #[MaxDepth(1)]
    private Collection $locations;

    /**
     * @var Collection<int, DeviceToken>
     */
    #[ORM\OneToMany(targetEntity: DeviceToken::class, mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $deviceTokens;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->orders = new ArrayCollection();
        $this->deliverOrders = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->deliverySchedules = new ArrayCollection();
        $this->invoices = new ArrayCollection();
        $this->locations = new ArrayCollection();
        $this->deviceTokens = new ArrayCollection();
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
    public function getPassword(): ?string
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

    public function getFcmToken(): ?string
    {
        return $this->fcmToken;
    }

    public function setFcmToken(?string $fcmToken): static
    {
        $this->fcmToken = $fcmToken;

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

    public function getImage(): ?MediaObject
    {
        return $this->image;
    }

    public function setImage(?MediaObject $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function setImageFile(?\Symfony\Component\HttpFoundation\File\UploadedFile $file): static
    {
        if ($file) {
            // Si une image existe déjà, mettre à jour le fichier de l'existant
            if ($this->image) {
                $this->image->setFile($file);
            } else {
                // Sinon, créer un nouveau MediaObject avec le mapping user_images
                $mediaObject = new MediaObject();
                $mediaObject->setFile($file);
                $mediaObject->setMapping('user_images');
                $this->image = $mediaObject;
            }
        }

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


    public function getDelivery(): ?Delivery
    {
        return $this->delivery;
    }

    public function setDelivery(?Delivery $delivery): static
    {
        $this->delivery = $delivery;
        if ($delivery && $delivery->getUser() !== $this) {
            $delivery->setUser($this);
    }
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

    /**
     * @return Collection<int, Location>
     */
    public function getLocations(): Collection
    {
        return $this->locations;
    }

    public function addLocation(Location $location): static
    {
        if (!$this->locations->contains($location)) {
            $this->locations->add($location);
        }

        return $this;
    }

    public function removeLocation(Location $location): static
    {
        $this->locations->removeElement($location);

        return $this;
    }

    /**
     * @return Collection<int, DeviceToken>
     */
    public function getDeviceTokens(): Collection
    {
        return $this->deviceTokens;
    }

    public function addDeviceToken(DeviceToken $deviceToken): static
    {
        if (!$this->deviceTokens->contains($deviceToken)) {
            $this->deviceTokens->add($deviceToken);
            $deviceToken->setUser($this);
        }

        return $this;
    }

    public function removeDeviceToken(DeviceToken $deviceToken): static
    {
        if ($this->deviceTokens->removeElement($deviceToken)) {
            if ($deviceToken->getUser() === $this) {
                $deviceToken->setUser(null);
            }
        }

        return $this;
    }

    public function getEmailVerificationCode(): ?string
    {
        return $this->emailVerificationCode;
    }

    public function setEmailVerificationCode(?string $emailVerificationCode): static
    {
        $this->emailVerificationCode = $emailVerificationCode;

        return $this;
    }

    public function getEmailVerificationCodeExpiresAt(): ?\DateTimeImmutable
    {
        return $this->emailVerificationCodeExpiresAt;
    }

    public function setEmailVerificationCodeExpiresAt(?\DateTimeImmutable $emailVerificationCodeExpiresAt): static
    {
        $this->emailVerificationCodeExpiresAt = $emailVerificationCodeExpiresAt;

        return $this;
    }

    public function isEmailVerified(): bool
    {
        return $this->isEmailVerified;
    }

    public function setIsEmailVerified(bool $isEmailVerified): static
    {
        $this->isEmailVerified = $isEmailVerified;

        return $this;
    }

    #[Groups(['user:update', 'user:update:profile'])]
    public function setIsOnline(bool $isOnline): static
    {
        if ($this->delivery) {
            $this->delivery->setIsOnline($isOnline);
        }

        return $this;
    }

    #[Groups(['user:read'])]
    public function getIsOnline(): bool
    {
        return $this->delivery ? $this->delivery->getIsOnline() : false;
    }
}
