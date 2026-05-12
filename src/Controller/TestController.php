<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    #[Route('/test-search', name: 'test_search')]
    public function testSearch(): Response
    {
        return $this->render('candidate/applications/test_search_api.html.twig');
    }
}
