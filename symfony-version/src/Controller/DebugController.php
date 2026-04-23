<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DebugController extends AbstractController
{
    #[Route('/debug/user', name: 'debug_user')]
    public function debugUser(): Response
    {
        if (!$this->getUser()) {
            return new Response('No user logged in');
        }
        
        $user = $this->getUser();
        $debug = [
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'roleId' => $user->getRoleId(),
            'roles' => $user->getRoles(),
            'isBanned' => $user->isBanned(),
            'isVerified' => $user->isVerified(),
        ];
        
        return new Response('<pre>' . print_r($debug, true) . '</pre>');
    }
}
