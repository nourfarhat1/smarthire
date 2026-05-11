<?php

namespace App\Controller\Training;

use App\Entity\Training;
use App\Repository\TrainingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/training')]
class CandidateTrainingController extends AbstractController
{
    public function __construct(
        private TrainingRepository $trainingRepository
    ) {
    }

    #[Route('/{id}', name: 'app_training_general_show')]
    public function show(Training $training): Response
    {
        $user = $this->getUser();
        $hasLiked = false;

        if ($user) {
            $hasLiked = $this->trainingRepository->hasUserLiked($training->getId(), $user->getId());
        }

        return $this->render('training/show.html.twig', [
            'training' => $training,
            'hasLiked' => $hasLiked,
        ]);
    }

    #[Route('/{id}/like', name: 'app_training_like', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function like(Training $training): Response
    {
        $user = $this->getUser();

        if ($this->trainingRepository->hasUserLiked($training->getId(), $user->getId())) {
            // Unlike
            $this->trainingRepository->unlikeTraining($training->getId(), $user->getId());
            $this->addFlash('info', 'Training removed from favorites');
        } else {
            // Like
            $this->trainingRepository->likeTraining($training->getId(), $user->getId());
            $this->addFlash('success', 'Training added to favorites');
        }

        return $this->redirectToRoute('app_training_general_show', ['id' => $training->getId()]);
    }

    #[Route('/my-trainings', name: 'app_training_my_trainings')]
    #[IsGranted('ROLE_USER')]
    public function myTrainings(): Response
    {
        $user = $this->getUser();
        $likedTrainings = $this->trainingRepository->findUserLikedTrainings($user->getId());

        return $this->render('training/my_trainings.html.twig', [
            'trainings' => $likedTrainings,
        ]);
    }

    #[Route('/progress/{id}', name: 'app_training_progress')]
    #[IsGranted('ROLE_USER')]
    public function progress(Training $training): Response
    {
        $user = $this->getUser();
        
        // This would typically track user progress through the training
        // For now, return a simple progress view
        $progress = [
            'completed' => false,
            'percentage' => 0,
            'lastWatched' => null,
            'totalDuration' => '30 minutes', // Would come from training metadata
        ];

        return $this->render('training/progress.html.twig', [
            'training' => $training,
            'progress' => $progress,
        ]);
    }

    #[Route('/{id}/complete', name: 'app_training_complete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function complete(Training $training): Response
    {
        $user = $this->getUser();
        
        // This would mark the training as completed for the user
        // Would typically save to a user_training_progress table
        
        $this->addFlash('success', 'Training marked as completed!');

        return $this->redirectToRoute('app_training_general_show', ['id' => $training->getId()]);
    }
}
