<?php

namespace App\Controller\Training;

use App\Entity\Training;
use App\Repository\TrainingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/trainings')]
#[IsGranted('ROLE_ADMIN')]
class AdminTrainingController extends AbstractController
{
    public function __construct(
        private TrainingRepository $trainingRepository
    ) {
    }

    #[Route('/', name: 'app_admin_trainings')]
    public function index(): Response
    {
        $trainings = $this->trainingRepository->findAll();

        return $this->render('admin/trainings/index.html.twig', [
            'trainings' => $trainings,
        ]);
    }

    #[Route('/new', name: 'app_admin_trainings_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $training = new Training();

        if ($request->isMethod('POST')) {
            $training->setTitle($request->request->get('title'));
            $training->setCategory($request->request->get('category'));
            $training->setDescription($request->request->get('description'));
            $training->setVideoUrl($request->request->get('video_url'));
            $training->setAdmin($this->getUser());

            $this->trainingRepository->save($training, true);

            $this->addFlash('success', 'Training created successfully!');

            return $this->redirectToRoute('app_admin_trainings');
        }

        return $this->render('admin/trainings/new.html.twig', [
            'training' => $training,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_trainings_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Training $training): Response
    {
        if ($request->isMethod('POST')) {
            $training->setTitle($request->request->get('title'));
            $training->setCategory($request->request->get('category'));
            $training->setDescription($request->request->get('description'));
            $training->setVideoUrl($request->request->get('video_url'));

            $this->trainingRepository->save($training, true);

            $this->addFlash('success', 'Training updated successfully!');

            return $this->redirectToRoute('app_admin_trainings');
        }

        return $this->render('admin/trainings/edit.html.twig', [
            'training' => $training,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_trainings_delete', methods: ['POST'])]
    public function delete(Training $training): Response
    {
        $this->trainingRepository->remove($training, true);

        $this->addFlash('success', 'Training deleted successfully!');

        return $this->redirectToRoute('app_admin_trainings');
    }

    #[Route('/analytics', name: 'app_admin_trainings_analytics')]
    public function analytics(): Response
    {
        $trainings = $this->trainingRepository->findAll();
        $totalLikes = array_sum(array_map(fn($t) => $t->getLikes(), $trainings));
        $totalDislikes = array_sum(array_map(fn($t) => $t->getDislikes(), $trainings));
        $popularTrainings = $this->trainingRepository->findPopularTrainings();

        return $this->render('admin/trainings/analytics.html.twig', [
            'trainings' => $trainings,
            'totalLikes' => $totalLikes,
            'totalDislikes' => $totalDislikes,
            'popularTrainings' => $popularTrainings,
        ]);
    }
}
