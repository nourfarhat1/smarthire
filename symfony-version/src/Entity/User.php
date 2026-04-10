<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'app_user')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private ?int $roleId = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'string')]
    private ?string $password = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $lastName = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isBanned = false;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $verificationToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $resetTokenExpiresAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $profilePicture = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $faceLoginEnabled = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $faceFeatures = null;

    #[ORM\OneToMany(mappedBy: 'candidate', targetEntity: JobRequest::class)]
    private Collection $jobApplications;

    #[ORM\OneToMany(mappedBy: 'candidate', targetEntity: QuizResult::class)]
    private Collection $quizResults;

    #[ORM\OneToMany(mappedBy: 'admin', targetEntity: Training::class)]
    private Collection $trainings;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: EventParticipant::class)]
    private Collection $eventParticipations;

    #[ORM\OneToMany(mappedBy: 'admin', targetEntity: Response::class)]
    private Collection $responses;

    #[ORM\OneToMany(mappedBy: 'recruiter', targetEntity: JobOffer::class)]
    private Collection $jobOffers;

    #[ORM\ManyToMany(targetEntity: JobOffer::class, mappedBy: 'savedByUsers')]
    private Collection $savedJobs;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->jobOffers = new ArrayCollection();
        $this->savedJobs = new ArrayCollection();
        $this->jobApplications = new ArrayCollection();
        $this->quizResults = new ArrayCollection();
        $this->trainings = new ArrayCollection();
        $this->eventParticipations = new ArrayCollection();
        $this->responses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoleId(): ?int
    {
        return $this->roleId;
    }

    public function setRoleId(int $roleId): self
    {
        $this->roleId = $roleId;
        $this->updateRoles();
        return $this;
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
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
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

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function isBanned(): bool
    {
        return $this->isBanned;
    }

    public function setBanned(bool $isBanned): self
    {
        $this->isBanned = $isBanned;
        return $this;
    }

    public function getVerificationToken(): ?string
    {
        return $this->verificationToken;
    }

    public function setVerificationToken(?string $verificationToken): self
    {
        $this->verificationToken = $verificationToken;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        
        $this->updateRoles();
        $roles = $this->roles;
        
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getRoleName(): string
    {
        return match($this->roleId) {
            1 => 'CANDIDATE',
            2 => 'HR',
            3 => 'ADMIN',
            default => 'USER'
        };
    }

    /**
     * Update roles based on roleId
     */
    #[ORM\PostLoad]
    private function updateRoles(): void
    {
        $this->roles = [];
        
        switch ($this->roleId) {
            case 1:
                $this->roles[] = 'ROLE_CANDIDATE';
                break;
            case 2:
                $this->roles[] = 'ROLE_HR';
                break;
            case 3:
                $this->roles[] = 'ROLE_ADMIN';
                break;
        }
        
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        $this->plainPassword = null;
        // $this->salt = null;
    }

    /**
     * @return Collection<int, JobOffer>
     */
    public function getJobOffers(): Collection
    {
        return $this->jobOffers;
    }

    public function addJobOffer(JobOffer $jobOffer): self
    {
        if (!$this->jobOffers->contains($jobOffer)) {
            $this->jobOffers->add($jobOffer);
            $jobOffer->setRecruiter($this);
        }

        return $this;
    }

    public function removeJobOffer(JobOffer $jobOffer): self
    {
        if ($this->jobOffers->removeElement($jobOffer)) {
            // set the owning side to null (unless already changed)
            if ($jobOffer->getRecruiter() === $this) {
                $jobOffer->setRecruiter(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, JobOffer>
     */
    public function getSavedJobs(): Collection
    {
        return $this->savedJobs;
    }

    public function addSavedJob(JobOffer $savedJob): self
    {
        if (!$this->savedJobs->contains($savedJob)) {
            $this->savedJobs->add($savedJob);
        }

        return $this;
    }

    public function removeSavedJob(JobOffer $savedJob): self
    {
        $this->savedJobs->removeElement($savedJob);

        return $this;
    }

    /**
     * @return Collection<int, JobRequest>
     */
    public function getJobApplications(): Collection
    {
        return $this->jobApplications;
    }

    public function addJobApplication(JobRequest $jobApplication): self
    {
        if (!$this->jobApplications->contains($jobApplication)) {
            $this->jobApplications->add($jobApplication);
            $jobApplication->setCandidate($this);
        }

        return $this;
    }

    public function removeJobApplication(JobRequest $jobApplication): self
    {
        if ($this->jobApplications->removeElement($jobApplication)) {
            // set the owning side to null (unless already changed)
            if ($jobApplication->getCandidate() === $this) {
                $jobApplication->setCandidate(null);
            }
        }

        return $this;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(?string $profilePicture): self
    {
        $this->profilePicture = $profilePicture;

        return $this;
    }

    public function isFaceLoginEnabled(): ?bool
    {
        return $this->faceLoginEnabled;
    }

    public function setFaceLoginEnabled(?bool $faceLoginEnabled): self
    {
        $this->faceLoginEnabled = $faceLoginEnabled;

        return $this;
    }

    public function getFaceFeatures(): ?string
    {
        return $this->faceFeatures;
    }

    public function setFaceFeatures(?string $faceFeatures): self
    {
        $this->faceFeatures = $faceFeatures;

        return $this;
    }
}
