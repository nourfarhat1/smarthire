<?php

namespace App\Controller\Training;

use App\Entity\Training;
use App\Repository\TrainingRepository;
use App\Service\AIGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/trainings')]
#[IsGranted('ROLE_ADMIN')]
class AdminTrainingController extends AbstractController
{
    public function __construct(
        private TrainingRepository $trainingRepository,
        private AIGeneratorService $aiGeneratorService,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/', name: 'app_admin_trainings')]
    public function index(Request $request): Response
    {
        // Get filtering parameters
        $search = $request->query->get('search', '');
        $category = $request->query->get('category', '');
        $sort = $request->query->get('sort', 'created_desc');

        // Build query
        $qb = $this->trainingRepository->createQueryBuilder('t');
        
        // Apply search filter
        if (!empty($search)) {
            $qb->andWhere('t.title LIKE :search OR t.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        // Apply category filter
        if (!empty($category)) {
            $qb->andWhere('t.category = :category')
               ->setParameter('category', $category);
        }
        
        // Apply sorting
        switch ($sort) {
            case 'created_asc':
                $qb->orderBy('t.createdAt', 'ASC');
                break;
            case 'likes_desc':
                $qb->orderBy('t.likes', 'DESC');
                break;
            case 'title_asc':
                $qb->orderBy('t.title', 'ASC');
                break;
            case 'created_desc':
            default:
                $qb->orderBy('t.createdAt', 'DESC');
                break;
        }
        
        $trainings = $qb->getQuery()->getResult();

        return $this->render('admin/trainings/index.html.twig', [
            'trainings' => $trainings,
        ]);
    }

    #[Route('/test-api', name: 'app_admin_trainings_test_api', methods: ['GET'])]
    public function testApi(): JsonResponse
    {
        try {
            $description = $this->aiGeneratorService->generateTrainingDescription('Test Training', 'Technology');
            
            return new JsonResponse([
                'success' => true,
                'message' => 'API is working correctly',
                'sample_description' => substr($description, 0, 200) . '...'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/generate-description', name: 'app_admin_trainings_generate_description', methods: ['POST'])]
    public function generateDescription(Request $request): JsonResponse
    {
        $title = $request->request->get('title');
        $category = $request->request->get('category');
        $tone = $request->request->get('tone', 'professional');

        if (empty($title) || empty($category)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Title and category are required'
            ], 400);
        }

        try {
            $description = $this->aiGeneratorService->generateTrainingDescription($title, $category, $tone);
            
            return $this->json(['success' => true, 'description' => $description]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to generate description: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route("/generate-editing-tips", name: "app_admin_trainings_generate_editing_tips", methods: ["POST"])]
    public function generateEditingTips(Request $request): JsonResponse
    {
        $title = $request->request->get('title');
        $category = $request->request->get('category');
        $description = $request->request->get('description');
        $tone = $request->request->get('tone', 'professional');
        
        if (!$title || !$category || !$description) {
            return $this->json([
                'success' => false,
                'error' => 'Missing required parameters: title, category, and description'
            ], 400);
        }
        
        try {
            $tipsData = $this->aiGeneratorService->generateEditingTips($title, $category, $description, $tone);
            
            return $this->json([
                'success' => true,
                'tips' => $tipsData['tips'],
                'source' => $tipsData['source'],
                'confidence' => $tipsData['confidence']
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Failed to generate AI tips: ' . $e->getMessage()
            ], 500);
        }
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
            
            // Only set admin if user is available
            if ($this->getUser()) {
                $training->setAdmin($this->getUser());
            }

            // Use EntityManager directly to ensure proper identity generation
            $this->entityManager->persist($training);
            $this->entityManager->flush();

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

    #[Route('/{id}/view', name: 'app_admin_trainings_view', methods: ['GET'])]
    public function view(Training $training): Response
    {
        return $this->render('admin/trainings/view.html.twig', [
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
