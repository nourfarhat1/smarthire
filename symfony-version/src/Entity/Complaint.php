<?php

namespace App\Entity;

use App\Repository\ComplaintRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ComplaintRepository::class)]
#[ORM\Table(name: 'complaint')]
class Complaint
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, name: 'user_id')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: ComplaintType::class, inversedBy: 'complaints')]
    #[ORM\JoinColumn(nullable: false, name: 'type_id')]
    private ?ComplaintType $type = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $subject = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 50)]
    private ?string $status = 'OPEN';

    #[ORM\Column(name: 'submission_date', type: 'datetime')]
    private ?\DateTimeInterface $submissionDate = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $priority = 'MEDIUM';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $aiSummary = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $sentiment = 'NEUTRAL';

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $urgencyScore = 5;

    #[ORM\OneToMany(mappedBy: 'complaint', targetEntity: Response::class)]
    private Collection $responses;

    public function __construct()
    {
        $this->responses = new ArrayCollection();
        $this->submissionDate = new \DateTime();
        $this->status = 'OPEN';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->user?->getId();
    }

    public function getFirstName(): ?string
    {
        return $this->user?->getFirstName();
    }

    public function getLastName(): ?string
    {
        return $this->user?->getLastName();
    }

    public function getType(): ?ComplaintType
    {
        return $this->type;
    }

    public function setType(?ComplaintType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getTypeId(): ?int
    {
        return $this->type?->getId();
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getSubmissionDate(): ?\DateTimeInterface
    {
        return $this->submissionDate;
    }

    public function setSubmissionDate(\DateTimeInterface $submissionDate): self
    {
        $this->submissionDate = $submissionDate;

        return $this;
    }

    /**
     * @return Collection<int, Response>
     */
    public function getResponses(): Collection
    {
        return $this->responses;
    }

    public function addResponse(Response $response): self
    {
        if (!$this->responses->contains($response)) {
            $this->responses->add($response);
            $response->setComplaint($this);
        }

        return $this;
    }

    public function removeResponse(Response $response): self
    {
        if ($this->responses->removeElement($response)) {
            // set the owning side to null (unless already changed)
            if ($response->getComplaint() === $this) {
                $response->setComplaint(null);
            }
        }

        return $this;
    }

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(?string $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function getAiSummary(): ?string
    {
        return $this->aiSummary;
    }

    public function setAiSummary(?string $aiSummary): self
    {
        $this->aiSummary = $aiSummary;

        return $this;
    }

    public function getSentiment(): ?string
    {
        return $this->sentiment;
    }

    public function setSentiment(?string $sentiment): self
    {
        $this->sentiment = $sentiment;

        return $this;
    }

    public function getUrgencyScore(): ?int
    {
        return $this->urgencyScore;
    }

    public function setUrgencyScore(?int $urgencyScore): self
    {
        $this->urgencyScore = $urgencyScore;

        return $this;
    }
}
