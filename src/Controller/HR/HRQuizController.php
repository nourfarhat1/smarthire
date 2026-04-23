<?php

namespace App\Controller\HR;

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

#[Route('/hr/quizzes')]
#[IsGranted('ROLE_HR')]
class HRQuizController extends AbstractController
{
    public function __construct(
        private QuizRepository $quizRepository,
        private QuestionRepository $questionRepository,
        private QuizResultRepository $quizResultRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/', name: 'app_hr_quizzes')]
    public function index(): Response
    {
        $quizzes = $this->quizRepository->findAll();
        
        // Get quiz statistics
        $quizStats = [];
        foreach ($quizzes as $quiz) {
            $results = $this->quizResultRepository->findBy(['quiz' => $quiz]);
            $totalAttempts = count($results);
            $passedAttempts = count(array_filter($results, fn($r) => $r->isPassed()));
            $averageScore = $totalAttempts > 0 ? array_sum(array_map(fn($r) => $r->getScore(), $results)) / $totalAttempts : 0;
            
            $quizStats[] = [
                'quiz' => $quiz,
                'totalAttempts' => $totalAttempts,
                'passedAttempts' => $passedAttempts,
                'passRate' => $totalAttempts > 0 ? round(($passedAttempts / $totalAttempts) * 100, 1) : 0,
                'averageScore' => $averageScore
            ];
        }
        
        return $this->render('hr/quizzes.html.twig', [
            'quizzes' => $quizzes,
            'quizStats' => $quizStats
        ]);
    }

    #[Route('/new', name: 'app_hr_quizzes_new')]
    public function new(): Response
    {
        return $this->render('hr/quizzes/new.html.twig');
    }

    #[Route('/create', name: 'app_hr_quizzes_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $data = $request->request->all();
        
        $quiz = new Quiz();
        $quiz->setTitle($data['title'] ?? '');
        $quiz->setDescription($data['description'] ?? '');
        $quiz->setDurationMinutes($data['durationMinutes'] ?? 30);
        $quiz->setPassingScore($data['passingScore'] ?? 70);
        
        $this->entityManager->persist($quiz);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Quiz created successfully!');
        
        return $this->redirectToRoute('app_hr_quizzes');
    }

    #[Route('/edit/{id}', name: 'app_hr_quizzes_edit')]
    public function edit(Quiz $quiz): Response
    {
        return $this->render('hr/quizzes/edit.html.twig', [
            'quiz' => $quiz,
            'questions' => $this->questionRepository->findBy(['quiz' => $quiz], ['orderBy' => ['id' => 'ASC']])
        ]);
    }

    #[Route('/update/{id}', name: 'app_hr_quizzes_update', methods: ['POST'])]
    public function update(Request $request, Quiz $quiz): Response
    {
        $data = $request->request->all();
        
        $quiz->setTitle($data['title'] ?? $quiz->getTitle());
        $quiz->setDescription($data['description'] ?? $quiz->getDescription());
        $quiz->setDurationMinutes($data['durationMinutes'] ?? $quiz->getDurationMinutes());
        $quiz->setPassingScore($data['passingScore'] ?? $quiz->getPassingScore());
        
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Quiz updated successfully!');
        
        return $this->redirectToRoute('app_hr_quizzes');
    }

    #[Route('/delete/{id}', name: 'app_hr_quizzes_delete', methods: ['POST'])]
    public function delete(Request $request, Quiz $quiz): Response
    {
        if ($this->isCsrfTokenValid('delete' . $quiz->getId(), $request->request->get('_token'))) {
            // Check if quiz has results
            $results = $this->quizResultRepository->findBy(['quiz' => $quiz]);
            if (!empty($results)) {
                $this->addFlash('error', 'Cannot delete quiz that has existing results.');
                return $this->redirectToRoute('app_hr_quizzes');
            }
            
            // Remove all questions first
            $questions = $this->questionRepository->findBy(['quiz' => $quiz]);
            foreach ($questions as $question) {
                $this->entityManager->remove($question);
            }
            
            $this->entityManager->remove($quiz);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Quiz deleted successfully!');
        }
        
        return $this->redirectToRoute('app_hr_quizzes');
    }

    #[Route('/questions/{quizId}', name: 'app_hr_quizzes_questions')]
    public function manageQuestions(Quiz $quiz): Response
    {
        $questions = $this->questionRepository->findBy(['quiz' => $quiz], ['orderBy' => ['id' => 'ASC']]);
        
        return $this->render('hr/quizzes/questions.html.twig', [
            'quiz' => $quiz,
            'questions' => $questions
        ]);
    }

    #[Route('/questions/new/{quizId}', name: 'app_hr_quizzes_questions_new')]
    public function newQuestion(Quiz $quiz): Response
    {
        return $this->render('hr/quizzes/question-form.html.twig', [
            'quiz' => $quiz,
            'question' => null
        ]);
    }

    #[Route('/questions/edit/{id}', name: 'app_hr_quizzes_questions_edit')]
    public function editQuestion(Question $question): Response
    {
        return $this->render('hr/quizzes/question-form.html.twig', [
            'quiz' => $question->getQuiz(),
            'question' => $question
        ]);
    }

    #[Route('/questions/create/{quizId}', name: 'app_hr_quizzes_questions_create', methods: ['POST'])]
    public function createQuestion(Request $request, Quiz $quiz): Response
    {
        $data = $request->request->all();
        
        $question = new Question();
        $question->setQuiz($quiz);
        $question->setQuestionText($data['questionText'] ?? '');
        $question->setOptionA($data['optionA'] ?? '');
        $question->setOptionB($data['optionB'] ?? '');
        $question->setOptionC($data['optionC'] ?? '');
        $question->setCorrectAnswer($data['correctAnswer'] ?? 'A');
        
        $this->entityManager->persist($question);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Question created successfully!');
        
        return $this->redirectToRoute('app_hr_quizzes_questions', ['quizId' => $quiz->getId()]);
    }

    #[Route('/questions/update/{id}', name: 'app_hr_quizzes_questions_update', methods: ['POST'])]
    public function updateQuestion(Request $request, Question $question): Response
    {
        $data = $request->request->all();
        
        $question->setQuestionText($data['questionText'] ?? $question->getQuestionText());
        $question->setOptionA($data['optionA'] ?? $question->getOptionA());
        $question->setOptionB($data['optionB'] ?? $question->getOptionB());
        $question->setOptionC($data['optionC'] ?? $question->getOptionC());
        $question->setCorrectAnswer($data['correctAnswer'] ?? $question->getCorrectAnswer());
        
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Question updated successfully!');
        
        return $this->redirectToRoute('app_hr_quizzes_questions', ['quizId' => $question->getQuiz()->getId()]);
    }

    #[Route('/questions/delete/{id}', name: 'app_hr_quizzes_questions_delete', methods: ['POST'])]
    public function deleteQuestion(Request $request, Question $question): Response
    {
        if ($this->isCsrfTokenValid('delete_question' . $question->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($question);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Question deleted successfully!');
        }
        
        return $this->redirectToRoute('app_hr_quizzes_questions', ['quizId' => $question->getQuiz()->getId()]);
    }

    #[Route('/results/{quizId}', name: 'app_hr_quizzes_results')]
    public function quizResults(Quiz $quiz): Response
    {
        $results = $this->quizResultRepository->findBy(['quiz' => $quiz], ['orderBy' => ['attemptDate' => 'DESC']]);
        
        // Calculate statistics
        $totalAttempts = count($results);
        $passedAttempts = count(array_filter($results, fn($r) => $r->isPassed()));
        $averageScore = $totalAttempts > 0 ? array_sum(array_map(fn($r) => $r->getScore(), $results)) / $totalAttempts : 0;
        $passRate = $totalAttempts > 0 ? round(($passedAttempts / $totalAttempts) * 100, 1) : 0;
        
        return $this->render('hr/quizzes/results.html.twig', [
            'quiz' => $quiz,
            'results' => $results,
            'stats' => [
                'totalAttempts' => $totalAttempts,
                'passedAttempts' => $passedAttempts,
                'passRate' => $passRate,
                'averageScore' => $averageScore
            ]
        ]);
    }

    #[Route('/analytics', name: 'app_hr_quizzes_analytics')]
    public function analytics(): JsonResponse
    {
        $quizzes = $this->quizRepository->findAll();
        $totalQuizzes = count($quizzes);
        
        $allResults = $this->quizResultRepository->findAll();
        $totalAttempts = count($allResults);
        $passedAttempts = count(array_filter($allResults, fn($r) => $r->isPassed()));
        
        return new JsonResponse([
            'totalQuizzes' => $totalQuizzes,
            'totalAttempts' => $totalAttempts,
            'passedAttempts' => $passedAttempts,
            'overallPassRate' => $totalAttempts > 0 ? round(($passedAttempts / $totalAttempts) * 100, 1) : 0
        ]);
    }
}
