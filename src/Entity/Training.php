<?php

namespace App\Entity;

use App\Repository\TrainingRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrainingRepository::class)]
#[ORM\Table(name: 'training_new')]
class Training
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'string', length: 100)]
    private ?string $category = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(name: 'video_url', type: 'string', length: 500, nullable: true)]
    private ?string $videoUrl = null;

    #[ORM\Column(type: 'integer')]
    private ?int $likes = 0;

    #[ORM\Column(type: 'integer')]
    private ?int $dislikes = 0;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'trainings')]
    #[ORM\JoinColumn(name: 'admin_id', nullable: true, onDelete: 'SET NULL')]
    private ?User $admin = null;

    #[ORM\OneToMany(mappedBy: 'training', targetEntity: UserTrainingVote::class)]
    private Collection $userVotes;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->likes = 0;
        $this->dislikes = 0;
        $this->userVotes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;

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

    public function getVideoUrl(): ?string
    {
        return $this->videoUrl;
    }

    public function setVideoUrl(?string $videoUrl): self
    {
        $this->videoUrl = $videoUrl;

        return $this;
    }

    public function getLikes(): ?int
    {
        return $this->likes;
    }

    public function setLikes(int $likes): self
    {
        $this->likes = $likes;

        return $this;
    }

    public function getDislikes(): ?int
    {
        return $this->dislikes;
    }

    public function setDislikes(int $dislikes): self
    {
        $this->dislikes = $dislikes;

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

    public function getAdmin(): ?User
    {
        return $this->admin;
    }

    public function setAdmin(?User $admin): self
    {
        $this->admin = $admin;

        return $this;
    }

    public function getUserVotes(): Collection
    {
        return $this->userVotes;
    }

    public function addUserVote(UserTrainingVote $userVote): self
    {
        if (!$this->userVotes->contains($userVote)) {
            $this->userVotes->add($userVote);
            $userVote->setTraining($this);
        }

        return $this;
    }

    public function removeUserVote(UserTrainingVote $userVote): self
    {
        if ($this->userVotes->removeElement($userVote)) {
            // set the owning side to null (unless already changed)
            if ($userVote->getTraining() === $this) {
                $userVote->setTraining(null);
            }
        }

        return $this;
    }

    public function getAdminName(): ?string
    {
        return $this->admin?->getFullName();
    }

    public function getUrl(): ?string
    {
        return $this->videoUrl;
    }

    public function setUrl(?string $videoUrl): self
    {
        $this->videoUrl = $videoUrl;

        return $this;
    }
}
