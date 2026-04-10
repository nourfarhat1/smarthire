<?php

namespace App\Entity;

use App\Repository\JobOfferRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobOfferRepository::class)]
#[ORM\Table(name: 'job_offer')]
class JobOffer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'jobOffers')]
    #[ORM\JoinColumn(nullable: false, name: 'recruiter_id')]
    private ?User $recruiter = null;

    #[ORM\ManyToOne(targetEntity: JobCategory::class, inversedBy: 'jobOffers')]
    #[ORM\JoinColumn(name: 'category_id')]
    private ?JobCategory $category = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $location = null;

    #[ORM\Column(name: 'salary_range', type: 'string', length: 255, nullable: true)]
    private ?string $salaryRange = null;

    #[ORM\Column(name: 'job_type', type: 'string', length: 100)]
    private ?string $jobType = null;

    #[ORM\Column(name: 'posted_date', type: 'datetime')]
    private ?\DateTimeInterface $postedDate = null;

    #[ORM\OneToMany(mappedBy: 'jobOffer', targetEntity: JobRequest::class)]
    private Collection $jobRequests;

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'savedJobs')]
    private Collection $savedByUsers;

    public function __construct()
    {
        $this->jobRequests = new ArrayCollection();
        $this->savedByUsers = new ArrayCollection();
        $this->postedDate = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecruiter(): ?User
    {
        return $this->recruiter;
    }

    public function setRecruiter(?User $recruiter): self
    {
        $this->recruiter = $recruiter;

        return $this;
    }

    public function getCategory(): ?JobCategory
    {
        return $this->category;
    }

    public function setCategory(?JobCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

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

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getSalaryRange(): ?string
    {
        return $this->salaryRange;
    }

    public function setSalaryRange(?string $salaryRange): self
    {
        $this->salaryRange = $salaryRange;

        return $this;
    }

    public function getJobType(): ?string
    {
        return $this->jobType;
    }

    public function setJobType(string $jobType): self
    {
        $this->jobType = $jobType;

        return $this;
    }

    public function getPostedDate(): ?\DateTimeInterface
    {
        return $this->postedDate;
    }

    public function setPostedDate(\DateTimeInterface $postedDate): self
    {
        $this->postedDate = $postedDate;

        return $this;
    }

    /**
     * @return Collection<int, JobRequest>
     */
    public function getJobRequests(): Collection
    {
        return $this->jobRequests;
    }

    public function addJobRequest(JobRequest $jobRequest): self
    {
        if (!$this->jobRequests->contains($jobRequest)) {
            $this->jobRequests->add($jobRequest);
            $jobRequest->setJobOffer($this);
        }

        return $this;
    }

    public function removeJobRequest(JobRequest $jobRequest): self
    {
        if ($this->jobRequests->removeElement($jobRequest)) {
            // set the owning side to null (unless already changed)
            if ($jobRequest->getJobOffer() === $this) {
                $jobRequest->setJobOffer(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getSavedByUsers(): Collection
    {
        return $this->savedByUsers;
    }

    public function addSavedByUser(User $savedByUser): self
    {
        if (!$this->savedByUsers->contains($savedByUser)) {
            $this->savedByUsers->add($savedByUser);
            $savedByUser->addSavedJob($this);
        }

        return $this;
    }

    public function removeSavedByUser(User $savedByUser): self
    {
        if ($this->savedByUsers->removeElement($savedByUser)) {
            $savedByUser->removeSavedJob($this);
        }

        return $this;
    }

    public function getCategoryName(): ?string
    {
        return $this->category?->getName();
    }

    public function __toString(): string
    {
        return $this->title . ' (' . $this->location . ')';
    }
}
