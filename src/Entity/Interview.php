<?php

namespace App\Entity;

use App\Repository\InterviewRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InterviewRepository::class)]
#[ORM\Table(name: 'interview')]
class Interview
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: JobRequest::class, inversedBy: 'interviews')]
    #[ORM\JoinColumn(nullable: false, name: 'job_request_id')]
    private ?JobRequest $jobRequest = null;

    #[ORM\Column(name: 'date_time', type: 'datetime')]
    private ?\DateTimeInterface $dateTime = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $location = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'string', length: 50)]
    private ?string $status = 'SCHEDULED';

    public function __construct()
    {
        $this->status = 'SCHEDULED';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJobRequest(): ?JobRequest
    {
        return $this->jobRequest;
    }

    public function setJobRequest(?JobRequest $jobRequest): self
    {
        $this->jobRequest = $jobRequest;

        return $this;
    }

    public function getDateTime(): ?\DateTimeInterface
    {
        return $this->dateTime;
    }

    public function setDateTime(\DateTimeInterface $dateTime): self
    {
        $this->dateTime = $dateTime;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

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
}
