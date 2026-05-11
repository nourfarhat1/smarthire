<?php

namespace App\Entity;

use App\Repository\QuizResultRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuizResultRepository::class)]
#[ORM\Table(name: 'quiz_result')]
class QuizResult
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Quiz::class, inversedBy: 'results')]
    #[ORM\JoinColumn(nullable: false, name: 'quiz_id')]
    private ?Quiz $quiz = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'quizResults')]
    #[ORM\JoinColumn(nullable: false, name: 'candidate_id')]
    private ?User $candidate = null;

    #[ORM\Column(type: 'integer')]
    private ?int $score = null;

    #[ORM\Column(name: 'attempt_date', type: 'datetime')]
    private ?\DateTimeInterface $attemptDate = null;

    #[ORM\Column(name: 'is_passed', type: 'boolean')]
    private ?bool $isPassed = false;

    #[ORM\Column(name: 'pdf_url', type: 'string', length: 500, nullable: true)]
    private ?string $pdfUrl = null;

    public function __construct()
    {
        $this->attemptDate = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuiz(): ?Quiz
    {
        return $this->quiz;
    }

    public function setQuiz(?Quiz $quiz): self
    {
        $this->quiz = $quiz;

        return $this;
    }

    public function getQuizId(): ?int
    {
        return $this->quiz?->getId();
    }

    public function getQuizTitle(): ?string
    {
        return $this->quiz?->getTitle();
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

    public function getCandidateId(): ?int
    {
        return $this->candidate?->getId();
    }

    public function getCandidateName(): ?string
    {
        return $this->candidate?->getFullName();
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(int $score): self
    {
        $this->score = $score;
        return $this;
    }

    public function getAttemptDate(): ?\DateTimeInterface
    {
        return $this->attemptDate;
    }

    public function setAttemptDate(\DateTimeInterface $attemptDate): self
    {
        $this->attemptDate = $attemptDate;

        return $this;
    }

    public function isPassed(): ?bool
    {
        return $this->isPassed;
    }

    public function setPassed(bool $passed): self
    {
        $this->isPassed = $passed;

        return $this;
    }

    public function getPdfUrl(): ?string
    {
        return $this->pdfUrl;
    }

    public function setPdfUrl(?string $pdfUrl): self
    {
        $this->pdfUrl = $pdfUrl;

        return $this;
    }

    public function __toString(): string
    {
        return 'Result: ' . $this->score . ' - ' . ($this->isPassed ? 'PASSED' : 'FAILED');
    }
}
