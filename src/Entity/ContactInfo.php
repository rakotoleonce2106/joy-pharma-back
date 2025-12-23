<?php

namespace App\Entity;

use App\Repository\ContactInfoRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContactInfoRepository::class)]
class ContactInfo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(groups: ['create'])]
    #[ORM\Column(length: 255)]
    #[Groups(['contact:read', 'contact:write'])]
    private ?string $phone = null;

    #[Assert\NotBlank(groups: ['create'])]
    #[Assert\Email]
    #[ORM\Column(length: 255)]
    #[Groups(['contact:read', 'contact:write'])]
    private ?string $email = null;

    #[ORM\OneToOne(mappedBy: 'contact')]
    private ?Store $store = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

  

    public function getStore(): ?Store
    {
        return $this->store;
    }

    public function setStore(?Store $store): static
    {
        // unset the owning side of the relation if necessary
        if ($store === null && $this->store !== null) {
            $this->store->setLocation(null);
        }

        $this->store = $store;

        return $this;
    }
}
