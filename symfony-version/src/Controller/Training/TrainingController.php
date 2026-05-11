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
class TrainingController extends AbstractController
{
    public function __construct(
        private TrainingRepository $trainingRepository
    ) {
    }

    #[Route('/', name: 'app_training')]
    public function index(Request $request): Response
    {
        $search = $request->query->get('search', '');
        $category = $request->query->get('category', '');

        $trainings = $this->trainingRepository->searchTrainings($search, $category);

        return $this->render('training/index.html.twig', [
            'trainings' => $trainings,
            'search' => $search,
            'selectedCategory' => $category,
        ]);
    }

    #[Route('/{id}', name: 'app_training_main_show')]
    public function show(Training $training): Response
    {
        // Check if user has liked this training
        $hasLiked = false;
        if ($this->getUser()) {
            $hasLiked = $this->trainingRepository->hasUserLiked($training->getId(), $this->getUser()->getId());
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

        return $this->redirectToRoute('app_training_main_show', ['id' => $training->getId()]);
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
}
