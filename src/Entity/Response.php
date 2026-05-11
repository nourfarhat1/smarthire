<?php

namespace App\Entity;

use App\Repository\ResponseRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResponseRepository::class)]
#[ORM\Table(name: 'response')]
class Response
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Complaint::class, inversedBy: 'responses')]
    #[ORM\JoinColumn(nullable: false, name: 'complaint_id')]
    private ?Complaint $complaint = null;

    #[ORM\Column(type: 'text')]
    private ?string $message = null;

    #[ORM\Column(name: 'response_date', type: 'datetime')]
    private ?\DateTimeInterface $responseDate = null;

    public function __construct()
    {
        $this->responseDate = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getComplaint(): ?Complaint
    {
        return $this->complaint;
    }

    public function setComplaint(?Complaint $complaint): self
    {
        $this->complaint = $complaint;

        return $this;
    }

    public function getComplaintId(): ?int
    {
        return $this->complaint?->getId();
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getResponseDate(): ?\DateTimeInterface
    {
        return $this->responseDate;
    }

    public function setResponseDate(\DateTimeInterface $responseDate): self
    {
        $this->responseDate = $responseDate;

        return $this;
    }
}
