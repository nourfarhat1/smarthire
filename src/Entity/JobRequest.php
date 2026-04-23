<?php

namespace App\Entity;

use App\Repository\JobRequestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobRequestRepository::class)]
#[ORM\Table(name: 'job_request')]
class JobRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'jobApplications')]
    #[ORM\JoinColumn(nullable: false, name: 'candidate_id')]
    private ?User $candidate = null;

    #[ORM\ManyToOne(targetEntity: JobOffer::class, inversedBy: 'jobRequests')]
    #[ORM\JoinColumn(name: 'job_offer_id', nullable: true)]
    private ?JobOffer $jobOffer = null;

    #[ORM\Column(name: 'submission_date', type: 'datetime')]
    private ?\DateTimeInterface $submissionDate = null;

    #[ORM\Column(type: 'string', length: 50)]
    private ?string $status = 'PENDING';

    #[ORM\Column(name: 'cv_url', type: 'string', length: 500, nullable: true)]
    private ?string $cvUrl = null;

    #[ORM\Column(name: 'cover_letter', type: 'text', nullable: true)]
    private ?string $coverLetter = null;

    #[ORM\Column(name: 'job_title', type: 'string', length: 255, nullable: true)]
    private ?string $jobTitle = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(name: 'suggested_salary', type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $suggestedSalary = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $categorie = null;

    #[ORM\OneToMany(mappedBy: 'jobRequest', targetEntity: Interview::class)]
    private Collection $interviews;

    public function __construct()
    {
        $this->interviews = new ArrayCollection();
        $this->submissionDate = new \DateTime();
        $this->status = 'PENDING';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCandidate(): ?User
    {
        return $this->candidate;
    }

    public function setCandidate(?User $candidate): self
    {
        $this->candidate = $candidate;

        return $this;
    }

    public function getJobOffer(): ?JobOffer
    {
        return $this->jobOffer;
    }

    public function setJobOffer(?JobOffer $jobOffer): self
    {
        $this->jobOffer = $jobOffer;

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCvUrl(): ?string
    {
        return $this->cvUrl;
    }

    public function setCvUrl(?string $cvUrl): self
    {
        $this->cvUrl = $cvUrl;

        return $this;
    }

    public function getCoverLetter(): ?string
    {
        return $this->coverLetter;
    }

    public function setCoverLetter(?string $coverLetter): self
    {
        $this->coverLetter = $coverLetter;

        return $this;
    }

    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    public function setJobTitle(?string $jobTitle): self
    {
        $this->jobTitle = $jobTitle;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getSuggestedSalary(): ?float
    {
        return $this->suggestedSalary;
    }

    public function setSuggestedSalary(?float $suggestedSalary): self
    {
        $this->suggestedSalary = $suggestedSalary;

        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(?string $categorie): self
    {
        $this->categorie = $categorie;

        return $this;
    }

    /**
     * @return Collection<int, Interview>
     */
    public function getInterviews(): Collection
    {
        return $this->interviews;
    }

    public function addInterview(Interview $interview): self
    {
        if (!$this->interviews->contains($interview)) {
            $this->interviews->add($interview);
            $interview->setJobRequest($this);
        }

        return $this;
    }

    public function removeInterview(Interview $interview): self
    {
        if ($this->interviews->removeElement($interview)) {
            // set the owning side to null (unless already changed)
            if ($interview->getJobRequest() === $this) {
                $interview->setJobRequest(null);
            }
        }

        return $this;
    }

    public function getSubmissionDateString(): string
    {
        return $this->submissionDate ? $this->submissionDate->format('d/m/Y H:i') : '';
    }

    public function getCandidateName(): ?string
    {
        return $this->candidate?->getFullName();
    }

    public function getCandidateEmail(): ?string
    {
        return $this->candidate?->getEmail();
    }
}
