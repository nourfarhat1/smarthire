<?php

namespace App\Controller\Candidate;

use App\Repository\QuizRepository;
use App\Repository\QuizResultRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/candidate/quizzes')]
#[IsGranted('ROLE_CANDIDATE')]
class CandidateQuizController extends AbstractController
{
    public function __construct(
        private QuizRepository $quizRepository,
        private QuizResultRepository $quizResultRepository
    ) {
    }

    #[Route('/', name: 'app_candidate_quizzes')]
    public function index(): Response
    {
        $candidate = $this->getUser();
        $availableQuizzes = $this->quizRepository->findAll();
        $completedQuizzes = $this->quizResultRepository->findBy(['candidate' => $candidate]);

        return $this->render('candidate/quizzes.html.twig', [
            'available_quizzes' => $availableQuizzes,
            'completed_quizzes' => $completedQuizzes,
        ]);
    }
}
