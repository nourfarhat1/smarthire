<?php

namespace App\Controller\Candidate;

use App\Entity\Quiz;
use App\Entity\Question;
use App\Entity\QuizResult;
use App\Repository\QuizRepository;
use App\Repository\QuestionRepository;
use App\Repository\QuizResultRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/candidate/quiz')]
#[IsGranted('ROLE_CANDIDATE')]
class QuizTakingController extends AbstractController
{
    public function __construct(
        private QuizRepository $quizRepository,
        private QuestionRepository $questionRepository,
        private QuizResultRepository $quizResultRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/take/{id}', name: 'app_candidate_quiz_take')]
    public function takeQuiz(Quiz $quiz): Response
    {
        // Check if user has already attempted this quiz
        $user = $this->getUser();
        $existingResult = $this->quizResultRepository->findOneBy([
            'quiz' => $quiz,
            'candidate' => $user
        ]);

        if ($existingResult) {
            $this->addFlash('warning', 'You have already taken this quiz. View your results in the quiz history.');
            return $this->redirectToRoute('app_candidate_quizzes');
        }

        // Get questions for this quiz
        $questions = $this->questionRepository->findBy(['quiz' => $quiz], ['orderBy' => ['id' => 'ASC']]);

        if (empty($questions)) {
            $this->addFlash('error', 'This quiz has no questions yet.');
            return $this->redirectToRoute('app_candidate_quizzes');
        }

        // Create a new quiz result entry
        $quizResult = new QuizResult();
        $quizResult->setQuiz($quiz);
        $quizResult->setCandidate($user);
        $quizResult->setStartTime(new \DateTime());
        $quizResult->setStatus('in_progress');
        
        $this->entityManager->persist($quizResult);
        $this->entityManager->flush();

        return $this->render('candidate/quiz/take.html.twig', [
            'quiz' => $quiz,
            'questions' => $questions,
            'quizResult' => $quizResult
        ]);
    }

    #[Route('/submit/{quizResultId}', name: 'app_candidate_quiz_submit', methods: ['POST'])]
    public function submitQuiz(Request $request, QuizResult $quizResult): Response
    {
        if ($quizResult->getCandidate() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You are not authorized to submit this quiz.');
        }

        if ($quizResult->getStatus() !== 'in_progress') {
            $this->addFlash('error', 'This quiz has already been submitted.');
            return $this->redirectToRoute('app_candidate_quizzes');
        }

        $answers = $request->request->all('answers');
        $questions = $this->questionRepository->findBy(['quiz' => $quizResult->getQuiz()]);

        $correctAnswers = 0;
        $totalQuestions = count($questions);
        $totalPoints = 0;

        // Calculate score
        foreach ($questions as $question) {
            $questionId = $question->getId();
            if (isset($answers[$questionId])) {
                $userAnswer = $answers[$questionId];
                if ($userAnswer === $question->getCorrectAnswer()) {
                    $correctAnswers++;
                    $totalPoints += 1; // Simple scoring - 1 point per correct answer
                }
            }
        }

        // Update quiz result
        $score = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100) : 0;
        $passed = $score >= $quizResult->getQuiz()->getPassingScore();

        $quizResult->setScore($score);
        $quizResult->setPassed($passed);
        $quizResult->setEndTime(new \DateTime());

        $this->entityManager->flush();

        if ($passed) {
            $this->addFlash('success', 'Congratulations! You passed the quiz with ' . $score . '% score.');
        } else {
            $this->addFlash('error', 'You did not pass the quiz. Your score: ' . $score . '% (Required: ' . $quizResult->getQuiz()->getPassingScore() . '%).');
        }

        return $this->redirectToRoute('app_candidate_quiz_result', ['id' => $quizResult->getId()]);
    }

    #[Route('/result/{id}', name: 'app_candidate_quiz_result')]
    public function quizResult(QuizResult $quizResult): Response
    {
        if ($quizResult->getCandidate() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You are not authorized to view this result.');
        }

        return $this->render('candidate/quiz/result.html.twig', [
            'quizResult' => $quizResult,
            'quiz' => $quizResult->getQuiz()
        ]);
    }

    #[Route('/timer/{quizResultId}', name: 'app_candidate_quiz_timer', methods: ['GET'])]
    public function getQuizTimer(QuizResult $quizResult): JsonResponse
    {
        if ($quizResult->getCandidate() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Unauthorized'], 403);
        }

        if ($quizResult->getStatus() !== 'in_progress') {
            return new JsonResponse(['error' => 'Quiz not in progress'], 400);
        }

        $timeLimit = $quizResult->getQuiz()->getDurationMinutes() * 60; // Convert to seconds
        $elapsed = (new \DateTime())->getTimestamp() - $quizResult->getStartTime()->getTimestamp();
        $remaining = max(0, $timeLimit - $elapsed);

        return new JsonResponse([
            'timeLimit' => $timeLimit,
            'elapsed' => $elapsed,
            'remaining' => $remaining,
            'isExpired' => $remaining <= 0
        ]);
    }

    #[Route('/save-progress/{quizResultId}', name: 'app_candidate_quiz_save_progress', methods: ['POST'])]
    public function saveProgress(Request $request, QuizResult $quizResult): JsonResponse
    {
        if ($quizResult->getCandidate() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Unauthorized'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $currentQuestionIndex = $data['currentQuestionIndex'] ?? 0;
        $answers = $data['answers'] ?? [];

        // Store progress in session or database
        // Simplified version - just store current state
        $quizResult->setAnswers($answers);
        
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'currentQuestionIndex' => $currentQuestionIndex,
            'totalQuestions' => count($this->questionRepository->findBy(['quiz' => $quizResult->getQuiz()]))
        ]);
    }
}
