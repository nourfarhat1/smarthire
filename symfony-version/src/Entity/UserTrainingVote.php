<?php

namespace App\Entity;

use App\Repository\UserTrainingVoteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserTrainingVoteRepository::class)]
#[ORM\Table(name: 'user_training_votes')]
#[ORM\UniqueConstraint(name: 'unique_user_training_vote', columns: ['user_id', 'training_id'])]
class UserTrainingVote
{
    public const VOTE_LIKE = 'like';
    public const VOTE_DISLIKE = 'dislike';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'trainingVotes')]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Training::class, inversedBy: 'userVotes')]
    #[ORM\JoinColumn(name: 'training_id', nullable: false, onDelete: 'CASCADE')]
    private ?Training $training = null;

    #[ORM\Column(type: 'string', length: 10)]
    private ?string $voteType = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
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

    public function getTraining(): ?Training
    {
        return $this->training;
    }

    public function setTraining(?Training $training): self
    {
        $this->training = $training;

        return $this;
    }

    public function getVoteType(): ?string
    {
        return $this->voteType;
    }

    public function setVoteType(string $voteType): self
    {
        if (!in_array($voteType, [self::VOTE_LIKE, self::VOTE_DISLIKE])) {
            throw new \InvalidArgumentException('Invalid vote type');
        }

        $this->voteType = $voteType;

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

    public function isLike(): bool
    {
        return $this->voteType === self::VOTE_LIKE;
    }

    public function isDislike(): bool
    {
        return $this->voteType === self::VOTE_DISLIKE;
    }
}
