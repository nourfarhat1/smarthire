<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CalendarController extends AbstractController
{
    #[Route('/candidate/calendar', name: 'app_candidate_calendar')]
    #[IsGranted('ROLE_CANDIDATE')]
    public function calendar(): Response
    {
        return $this->render('candidate/calendar/index.html.twig');
    }
}
