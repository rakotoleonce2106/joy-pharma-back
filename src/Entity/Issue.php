<?php

namespace App\Entity;

use App\Entity\Traits\EntityIdTrait;
use App\Entity\Traits\EntityTimestampTrait;
use App\Repository\IssueRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

enum IssueType: string
{
    case DAMAGED_PRODUCT = 'damaged_product';
    case WRONG_ADDRESS = 'wrong_address';
    case CUSTOMER_UNAVAILABLE = 'customer_unavailable';
    case OTHER = 'other';
}

enum IssueStatus: string
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case RESOLVED = 'resolved';
}

#[ORM\Entity(repositoryClass: IssueRepository::class)]
#[ORM\Table(name: '`issue`')]
class Issue
{
    use EntityIdTrait;
    use EntityTimestampTrait;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['issue:read'])]
    private ?Order $orderRef = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['issue:read'])]
    private ?User $reportedBy = null;

    #[ORM\Column(type: 'string', length: 30, enumType: IssueType::class)]
    #[Groups(['issue:read', 'issue:write'])]
    private IssueType $type;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['issue:read', 'issue:write'])]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 20, enumType: IssueStatus::class)]
    #[Groups(['issue:read'])]
    private IssueStatus $status;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['issue:read'])]
    private ?string $resolution = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['issue:read'])]
    private ?\DateTimeInterface $resolvedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status = IssueStatus::OPEN;
        $this->type = IssueType::OTHER;
    }

    public function getOrderRef(): ?Order
    {
        return $this->orderRef;
    }

    public function setOrderRef(?Order $orderRef): static
    {
        $this->orderRef = $orderRef;
        return $this;
    }

    public function getReportedBy(): ?User
    {
        return $this->reportedBy;
    }

    public function setReportedBy(?User $reportedBy): static
    {
        $this->reportedBy = $reportedBy;
        return $this;
    }

    public function getType(): IssueType
    {
        return $this->type;
    }

    public function setType(IssueType $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getStatus(): IssueStatus
    {
        return $this->status;
    }

    public function setStatus(IssueStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getResolution(): ?string
    {
        return $this->resolution;
    }

    public function setResolution(?string $resolution): static
    {
        $this->resolution = $resolution;
        return $this;
    }

    public function getResolvedAt(): ?\DateTimeInterface
    {
        return $this->resolvedAt;
    }

    public function setResolvedAt(?\DateTimeInterface $resolvedAt): static
    {
        $this->resolvedAt = $resolvedAt;
        return $this;
    }
}


