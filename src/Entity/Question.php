<?php

namespace App\Entity;

use App\Repository\QuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuestionRepository::class)]
#[ORM\Table(name: 'question')]
class Question
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Quiz::class, inversedBy: 'questions')]
    #[ORM\JoinColumn(nullable: false, name: 'quiz_id')]
    private ?Quiz $quiz = null;

    #[ORM\Column(name: 'question_text', type: 'text')]
    private ?string $questionText = null;

    #[ORM\Column(name: 'option_a', type: 'string', length: 500)]
    private ?string $optionA = null;

    #[ORM\Column(name: 'option_b', type: 'string', length: 500)]
    private ?string $optionB = null;

    #[ORM\Column(name: 'option_c', type: 'string', length: 500)]
    private ?string $optionC = null;

    #[ORM\Column(name: 'correct_answer', type: 'string', length: 10)]
    private ?string $correctAnswer = null;

    public function __construct()
    {
        $this->isActive = true;
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

    public function getQuestionText(): ?string
    {
        return $this->questionText;
    }

    public function setQuestionText(string $questionText): self
    {
        $this->questionText = $questionText;

        return $this;
    }

    public function getOptionA(): ?string
    {
        return $this->optionA;
    }

    public function setOptionA(string $optionA): self
    {
        $this->optionA = $optionA;

        return $this;
    }

    public function getOptionB(): ?string
    {
        return $this->optionB;
    }

    public function setOptionB(string $optionB): self
    {
        $this->optionB = $optionB;

        return $this;
    }

    public function getOptionC(): ?string
    {
        return $this->optionC;
    }

    public function setOptionC(string $optionC): self
    {
        $this->optionC = $optionC;
        return $this;
    }

    public function getCorrectAnswer(): ?string
    {
        return $this->correctAnswer;
    }

    public function setCorrectAnswer(string $correctAnswer): self
    {
        $this->correctAnswer = $correctAnswer;
        return $this;
    }

    public function __toString(): string
    {
        return $this->questionText ?? '';
    }
}
