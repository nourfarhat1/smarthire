<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'app_user')]
#[Vich\Uploadable]
#[UniqueEntity(fields: ['email'], message: 'This email is already in use.')]
#[UniqueEntity(fields: ['phoneNumber'], message: 'This phone number is already in use.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'role_id', type: 'integer')]
    private int $roleId;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string')]
    private string $password;

    #[ORM\Column(name: 'first_name', type: 'string', length: 255)]
    private string $firstName;

    #[ORM\Column(name: 'last_name', type: 'string', length: 255)]
    private string $lastName;

    #[ORM\Column(name: 'phone_number', type: 'string', length: 20, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(name: 'is_verified', type: 'boolean')]
    private bool $isVerified = false;

    #[ORM\Column(name: 'is_banned', type: 'boolean')]
    private bool $isBanned = false;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $verificationToken = null;

    
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $profilePicture = null;

    #[ORM\Column(name: 'face_login_enabled', type: 'boolean')]
    private bool $faceLoginEnabled = false;

    #[ORM\Column(name: 'face_features', type: 'string', length: 255, nullable: true)]
    private ?string $faceFeatures = null;

    #[ORM\Column(name: 'face_tokens', type: 'json', nullable: true)]
    private array $faceTokens = [];


    // ========== NOUVEAUX CHAMPS POUR CV ET SKILLS ==========
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $skills = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $cvFilename = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
private ?string $otpCode = null;

#[ORM\Column(type: 'datetime', nullable: true)]
private ?\DateTimeInterface $otpExpiry = null;

    #[Vich\UploadableField(mapping: 'cv_files', fileNameProperty: 'cvFilename')]
    private ?File $cvFile = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;
    // ========== FIN NOUVEAUX CHAMPS ==========

    // ========== GOOGLE OAUTH FIELDS ==========
    #[ORM\Column(name: 'google_id', type: 'string', length: 255, nullable: true, unique: true)]
    private ?string $googleId = null;

    #[ORM\Column(name: 'google_access_token', type: 'text', nullable: true)]
    private ?string $googleAccessToken = null;

    #[ORM\Column(name: 'google_refresh_token', type: 'text', nullable: true)]
    private ?string $googleRefreshToken = null;
    // ========== FIN GOOGLE OAUTH FIELDS ==========

    #[ORM\OneToMany(mappedBy: 'candidate', targetEntity: JobRequest::class)]
    private Collection $jobApplications;

    #[ORM\OneToMany(mappedBy: 'candidate', targetEntity: QuizResult::class)]
    private Collection $quizResults;

    #[ORM\OneToMany(mappedBy: 'admin', targetEntity: Training::class)]
    private Collection $trainings;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: EventParticipant::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $eventParticipations;

    // src/Entity/User.php

#[ORM\OneToMany(
    mappedBy: 'user', 
    targetEntity: UserTrainingVote::class, 
    cascade: ['persist'] // Remove 'remove' from this array
)]
private Collection $trainingVotes;


    #[ORM\OneToMany(mappedBy: 'recruiter', targetEntity: JobOffer::class)]
    private Collection $jobOffers;

    #[ORM\ManyToMany(targetEntity: JobOffer::class, mappedBy: 'savedByUsers')]
    private Collection $savedJobs;

    public function __construct()
    {
        $this->roleId = 1; // Default to candidate role
        $this->email = '';
        $this->password = '';
        $this->firstName = '';
        $this->lastName = '';
        $this->createdAt = new \DateTime();
        $this->jobOffers = new ArrayCollection();
        $this->savedJobs = new ArrayCollection();
        $this->jobApplications = new ArrayCollection();
        $this->quizResults = new ArrayCollection();
        $this->trainings = new ArrayCollection();
        $this->eventParticipations = new ArrayCollection();
        $this->trainingVotes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoleId(): int
    {
        return $this->roleId;
    }

    public function setRoleId(int $roleId): self
    {
        $this->roleId = $roleId;
        $this->updateRoles();
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getOtpCode(): ?string
{
    return $this->otpCode;
}

public function setOtpCode(?string $otpCode): self
{
    $this->otpCode = $otpCode;
    return $this;
}

public function getOtpExpiry(): ?\DateTimeInterface
{
    return $this->otpExpiry;
}

public function setOtpExpiry(?\DateTimeInterface $otpExpiry): self
{
    $this->otpExpiry = $otpExpiry;
    return $this;
}

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): string
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

    public function getRoles(): array
    {
        $roles = $this->roles;
        
        $this->updateRoles();
        $roles = $this->roles;
        
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
    }

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    public function eraseCredentials(): void
    {
        // plain password is not stored as a property, nothing to erase
    }


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
            if ($jobOffer->getRecruiter() === $this) {
                $jobOffer->setRecruiter(null);
            }
        }

        return $this;
    }

    public function getSavedJobs(): Collection
    {
        return $this->savedJobs;
    }

    public function addSavedJob(JobOffer $savedJob): self
    {
        if (!$this->savedJobs->contains($savedJob)) {
            $this->savedJobs->add($savedJob);
            $savedJob->addSavedByUser($this);
        }

        return $this;
    }

    public function removeSavedJob(JobOffer $savedJob): self
    {
        if ($this->savedJobs->removeElement($savedJob)) {
            $savedJob->removeSavedByUser($this);
        }

        return $this;
    }

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

    public function isFaceLoginEnabled(): bool
    {
        return $this->faceLoginEnabled;
    }

    public function setFaceLoginEnabled(bool $faceLoginEnabled): self
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

    public function getFaceTokens(): array
    {
        return $this->faceTokens ?? [];
    }

    public function setFaceTokens(array $faceTokens): self
    {
        $this->faceTokens = $faceTokens;

        return $this;
    }

    public function addFaceToken(string $faceToken): self
    {
        if (!in_array($faceToken, $this->faceTokens)) {
            $this->faceTokens[] = $faceToken;
        }

        return $this;
    }

    public function removeFaceToken(string $faceToken): self
    {
        $this->faceTokens = array_filter($this->faceTokens, function($token) use ($faceToken) {
            return $token !== $faceToken;
        });

        return $this;
    }

    // ========== CV ET SKILLS ==========

    public function getSkills(): ?string
    {
        return $this->skills;
    }

    public function setSkills(?string $skills): self
    {
        $this->skills = $skills;
        return $this;
    }

    public function getSkillsArray(): array
    {
        if (!$this->skills) {
            return [];
        }
        $skills = json_decode($this->skills, true);
        return is_array($skills) ? $skills : [];
    }

    public function getSkillsString(): string
    {
        return implode(', ', $this->getSkillsArray());
    }

    public function getCvFilename(): ?string
    {
        return $this->cvFilename;
    }

    public function setCvFilename(?string $cvFilename): self
    {
        $this->cvFilename = $cvFilename;
        return $this;
    }

    public function getCvFile(): ?File
    {
        return $this->cvFile;
    }

    public function setCvFile(?File $cvFile): self
    {
        $this->cvFile = $cvFile;
        if ($cvFile) {
            $this->updatedAt = new \DateTime();
        }
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getCvUrl(): ?string
    {
        return $this->cvFilename ? '/uploads/cvs/' . $this->cvFilename : null;
    }

    public function hasCv(): bool
    {
        return $this->cvFilename !== null;
    }

    public function hasSkills(): bool
    {
        return !empty($this->getSkillsArray());
    }

    // ========== GOOGLE OAUTH GETTERS/SETTERS ==========
    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): self
    {
        $this->googleId = $googleId;
        return $this;
    }

    public function getGoogleAccessToken(): ?string
    {
        return $this->googleAccessToken;
    }

    public function setGoogleAccessToken(?string $googleAccessToken): self
    {
        $this->googleAccessToken = $googleAccessToken;
        return $this;
    }

    public function getGoogleRefreshToken(): ?string
    {
        return $this->googleRefreshToken;
    }

    public function setGoogleRefreshToken(?string $googleRefreshToken): self
    {
        $this->googleRefreshToken = $googleRefreshToken;
        return $this;
    }

    public function isGoogleUser(): bool
    {
        return $this->googleId !== null;
    }

    // ========== TRAINING VOTES METHODS ==========

    public function getTrainingVotes(): Collection
    {
        return $this->trainingVotes;
    }

    public function addTrainingVote(UserTrainingVote $trainingVote): self
    {
        if (!$this->trainingVotes->contains($trainingVote)) {
            $this->trainingVotes->add($trainingVote);
            $trainingVote->setUser($this);
        }

        return $this;
    }

    public function removeTrainingVote(UserTrainingVote $trainingVote): self
    {
        if ($this->trainingVotes->removeElement($trainingVote)) {
            // set the owning side to null (unless already changed)
            if ($trainingVote->getUser() === $this) {
                $trainingVote->setUser(null);
            }
        }

        return $this;
    }
}