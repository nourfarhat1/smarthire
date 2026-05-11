<?php

namespace App\Controller\Admin;

use App\Entity\Quiz;
use App\Entity\Question;
use App\Form\QuizType;
use App\Repository\QuizRepository;
use App\Repository\QuestionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/quizzes')]
#[IsGranted('ROLE_ADMIN')]
class QuizManagementController extends AbstractController
{
    public function __construct(
        private QuizRepository $quizRepository,
        private QuestionRepository $questionRepository
    ) {
    }

    #[Route('/', name: 'app_admin_quizzes')]
    public function index(): Response
    {
        $quizzes = $this->quizRepository->findAll();
        
        return $this->render('admin/quizzes/index.html.twig', [
            'quizzes' => $quizzes,
        ]);
    }

    #[Route('/new', name: 'app_admin_quizzes_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $quiz = new Quiz();
        $form = $this->createForm(QuizType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->quizRepository->save($quiz, true);
            $this->addFlash('success', 'Quiz created successfully!');
            return $this->redirectToRoute('app_admin_quizzes');
        }

        return $this->render('admin/quizzes/new.html.twig', [
            'quiz' => $quiz,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_quizzes_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Quiz $quiz): Response
    {
        $form = $this->createForm(QuizType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->quizRepository->save($quiz, true);
            $this->addFlash('success', 'Quiz updated successfully!');
            return $this->redirectToRoute('app_admin_quizzes');
        }

        return $this->render('admin/quizzes/edit.html.twig', [
            'quiz' => $quiz,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/questions', name: 'app_admin_quizzes_questions')]
    public function questions(Quiz $quiz): Response
    {
        $questions = $this->questionRepository->findBy(['quiz' => $quiz], ['id' => 'ASC']);
        $questionsCount = count($questions);
        
        return $this->render('admin/quizzes/questions.html.twig', [
            'quiz' => $quiz,
            'questions' => $questions,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_quizzes_delete', methods: ['POST'])]
    public function delete(Request $request, Quiz $quiz): Response
    {
        if ($this->isCsrfTokenValid('delete' . $quiz->getId(), $request->request->get('_token'))) {
            $this->quizRepository->remove($quiz, true);
            $this->addFlash('success', 'Quiz deleted successfully!');
        }

        return $this->redirectToRoute('app_admin_quizzes');
    }

    protected function isCsrfTokenValid(string $id, ?string $token): bool
    {
        return $token && hash_equals($this->getToken('delete' . $id), $token);
    }
}
