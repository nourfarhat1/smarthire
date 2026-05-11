<?php

namespace App\Controller\Candidate;

use App\Entity\Training;
use App\Repository\JobRequestRepository;
use App\Repository\TrainingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/candidate/training')]
#[IsGranted('ROLE_CANDIDATE')]
class CandidateTrainingController extends AbstractController
{
    public function __construct(
        private TrainingRepository $trainingRepository,
        private JobRequestRepository $jobRequestRepository
    ) {
    }

    #[Route('/', name: 'app_candidate_training')]
    public function index(Request $request): Response
    {
        $search = $request->query->get('search', '');
        $category = $request->query->get('category', '');
        $level = $request->query->get('level', '');
        $rating = $request->query->get('rating', '');

        // Get all trainings with filtering
        $qb = $this->trainingRepository->createQueryBuilder('t')
            ->orderBy('t.likes', 'DESC');

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

        // Apply level filter
        if (!empty($level)) {
            $qb->andWhere('t.level = :level')
               ->setParameter('level', $level);
        }

        // Apply rating filter (using likes as a proxy for popularity)
        if (!empty($rating)) {
            $qb->andWhere('t.likes >= :minLikes')
               ->setParameter('minLikes', (int)$rating * 10); // Scale rating to likes
        }

        $trainings = $qb->getQuery()->getResult();

        // For now, we'll use a simple approach - no user-specific rating tracking
        // In a real implementation, you might want to add a separate table for user ratings
        $ratedTrainingIds = []; // Empty since we don't have rating tracking

        return $this->render('candidate/training/index.html.twig', [
            'trainings' => $trainings,
            'ratedTrainingIds' => $ratedTrainingIds,
            'search' => $search,
            'selectedCategory' => $category,
            'selectedLevel' => $level,
            'selectedRating' => $rating,
        ]);
    }

    #[Route('/{id}', name: 'app_candidate_training_show')]
    public function show(int $id, TrainingRepository $trainingRepository): Response
    {
        $user = $this->getUser();
        
        // Find the training
        $training = $trainingRepository->find($id);
        if (!$training) {
            throw $this->createNotFoundException('Training not found.');
        }

        // Get user's rating for this training (not implemented yet)
        $userRating = null; // No rating system available
        
        // Get AI recommendations based on user's job applications
        $recommendations = $this->generateTrainingRecommendations($user, $training);

        return $this->render('candidate/training/show.html.twig', [
            'training' => $training,
            'userRating' => $userRating,
            'recommendations' => $recommendations,
        ]);
    }

    #[Route('/{id}/like', name: 'app_candidate_training_like', methods: ['POST'])]
    public function likeTraining(Request $request, int $id, TrainingRepository $trainingRepository): Response
    {
        $training = $trainingRepository->find($id);
        if (!$training) {
            throw $this->createNotFoundException('Training not found.');
        }

        // Increment likes
        $currentLikes = $training->getLikes() ?? 0;
        $training->setLikes($currentLikes + 1);
        
        $this->trainingRepository->save($training, true);

        $this->addFlash('success', 'You have liked this training!');
        return $this->redirectToRoute('app_candidate_training_show', ['id' => $id]);
    }

    #[Route('/{id}/dislike', name: 'app_candidate_training_dislike', methods: ['POST'])]
    public function dislikeTraining(Request $request, int $id, TrainingRepository $trainingRepository): Response
    {
        $training = $trainingRepository->find($id);
        if (!$training) {
            throw $this->createNotFoundException('Training not found.');
        }

        // Increment dislikes
        $currentDislikes = $training->getDislikes() ?? 0;
        $training->setDislikes($currentDislikes + 1);
        
        $this->trainingRepository->save($training, true);

        $this->addFlash('success', 'Your feedback has been recorded!');
        return $this->redirectToRoute('app_candidate_training_show', ['id' => $id]);
    }

    #[Route('/ai-recommendations', name: 'app_candidate_training_ai_recommendations')]
    public function aiRecommendations(): Response
    {
        $user = $this->getUser();
        
        // Get user's job applications to analyze skills and interests
        $jobRequests = $this->jobRequestRepository->findBy(['candidate' => $user]);
        
        // Generate AI recommendations based on job applications
        $recommendations = $this->generateTrainingRecommendations($user, null);

        return $this->render('candidate/training/ai-recommendations.html.twig', [
            'recommendations' => $recommendations,
            'jobApplications' => $jobRequests,
        ]);
    }

    private function generateTrainingRecommendations($user, $currentTraining = null): array
    {
        $recommendations = [];
        
        // Get user's job applications
        $jobRequests = $this->jobRequestRepository->findBy(['candidate' => $user]);
        
        // Analyze job categories and skills
        $categories = [];
        $skills = [];
        
        foreach ($jobRequests as $jobRequest) {
            $jobOffer = $jobRequest->getJobOffer();
            if ($jobOffer) {
                $category = $jobOffer->getCategory();
                if (!in_array($category, $categories)) {
                    $categories[] = $category;
                }
                
                // Extract skills from job description (simple approach)
                $description = strtolower($jobOffer->getDescription());
                if (strpos($description, 'javascript') !== false) $skills[] = 'JavaScript';
                if (strpos($description, 'python') !== false) $skills[] = 'Python';
                if (strpos($description, 'react') !== false) $skills[] = 'React';
                if (strpos($description, 'node') !== false) $skills[] = 'Node.js';
                if (strpos($description, 'sql') !== false) $skills[] = 'SQL';
                if (strpos($description, 'aws') !== false) $skills[] = 'AWS';
            }
        }

        // Get trainings based on categories and skills
        foreach ($categories as $category) {
            $trainings = $this->trainingRepository->findBy(['category' => $category], ['likes' => 'DESC'], 2);
            foreach ($trainings as $training) {
                if ($currentTraining && $training->getId() === $currentTraining->getId()) {
                    continue; // Skip current training
                }
                $recommendations[] = [
                    'training' => $training,
                    'reason' => 'Based on your interest in ' . $category . ' positions',
                    'match_score' => rand(75, 95)
                ];
            }
        }

        // Get trainings based on skills
        foreach ($skills as $skill) {
            $trainings = $this->trainingRepository->createQueryBuilder('t')
                ->andWhere('t.title LIKE :skill OR t.description LIKE :skill')
                ->setParameter('skill', '%' . $skill . '%')
                ->orderBy('t.likes', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getResult();

            foreach ($trainings as $training) {
                if ($currentTraining && $training->getId() === $currentTraining->getId()) {
                    continue; // Skip current training
                }
                $recommendations[] = [
                    'training' => $training,
                    'reason' => 'To improve your ' . $skill . ' skills',
                    'match_score' => rand(80, 98)
                ];
            }
        }

        // Remove duplicates and sort by match score
        $recommendations = array_unique($recommendations, SORT_REGULAR);
        usort($recommendations, function($a, $b) {
            return $b['match_score'] - $a['match_score'];
        });

        return array_slice($recommendations, 0, 6);
    }
}
