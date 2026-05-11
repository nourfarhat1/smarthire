<?php

namespace App\Controller\Candidate;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\UserAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/candidate/auth')]
class CandidateAuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/register', name: 'app_candidate_register')]
    public function register(Request $request, UserAuthenticatorInterface $userAuthenticator, UserAuthenticator $authenticator): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_candidate_dashboard');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Debug: Log form submission
                error_log('REGISTRATION DEBUG: Form submitted and valid');
                error_log('REGISTRATION DEBUG: User data - ' . print_r([
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'email' => $user->getEmail(),
                    'phoneNumber' => $user->getPhoneNumber(),
                    'password' => '***hidden***',
                    'roleId' => $user->getRoleId()
                ], true));

                // Set candidate role
                $user->setRoleId(1); // Candidate role
                $user->setRoles(['ROLE_CANDIDATE']);
                $user->setVerified(false);

                // Store password as plain text
                $user->setPassword(
                    $form->get('plainPassword')->getData()
                );

                error_log('REGISTRATION DEBUG: About to persist user');
                $this->entityManager->persist($user);
                
                error_log('REGISTRATION DEBUG: About to flush entity manager');
                $this->entityManager->flush();
                
                error_log('REGISTRATION DEBUG: User flushed with ID: ' . $user->getId());
                
                // Verify user was actually saved
                $savedUser = $this->entityManager->getRepository(User::class)->find($user->getId());
                if ($savedUser) {
                    error_log('REGISTRATION DEBUG: User verified in database');
                    $this->addFlash('success', 'Account created successfully! User ID: ' . $user->getId());
                } else {
                    error_log('REGISTRATION ERROR: User not found after save');
                    $this->addFlash('error', 'Registration failed: User not saved properly');
                    return $this->redirectToRoute('app_candidate_register');
                }

                // Auto-login after registration
                error_log('REGISTRATION DEBUG: About to authenticate user');
                return $userAuthenticator->authenticateUser(
                    $user,
                    $authenticator,
                    $request
                );
                
            } catch (\Exception $e) {
                error_log('REGISTRATION ERROR: ' . $e->getMessage());
                error_log('REGISTRATION ERROR TRACE: ' . $e->getTraceAsString());
                $this->addFlash('error', 'Registration failed: ' . $e->getMessage());
                return $this->redirectToRoute('app_candidate_register');
            }
        } else {
            // Debug: Log why form is invalid
            if ($form->isSubmitted()) {
                error_log('REGISTRATION DEBUG: Form submitted but invalid');
                foreach ($form->getErrors(true) as $error) {
                    error_log('FORM ERROR: ' . $error->getMessage());
                }
            } else {
                error_log('REGISTRATION DEBUG: Form not submitted');
            }
        }

        return $this->render('candidate/auth/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/profile', name: 'app_candidate_profile')]
    #[IsGranted('ROLE_CANDIDATE')]
    public function profile(Request $request, EntityManagerInterface $entityManager): Response
    {
        $candidate = $this->getUser();
        
        $form = $this->createForm(RegistrationFormType::class, $candidate, [
            'is_profile_edit' => true
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Only update password if provided
            if ($form->get('plainPassword')->getData()) {
                $candidate->setPassword(
                    $form->get('plainPassword')->getData()
                );
            }

            $entityManager->flush();
            $this->addFlash('success', 'Profile updated successfully!');

            return $this->redirectToRoute('app_candidate_profile');
        }

        return $this->render('candidate/auth/profile.html.twig', [
            'profileForm' => $form->createView(),
            'candidate' => $candidate,
        ]);
    }

    #[Route('/settings', name: 'app_candidate_settings')]
    #[IsGranted('ROLE_CANDIDATE')]
    public function settings(Request $request, EntityManagerInterface $entityManager): Response
    {
        $candidate = $this->getUser();

        // Handle face login toggle
        if ($request->isMethod('POST')) {
            $faceLoginEnabled = $request->request->get('face_login_enabled') === 'on';
            $candidate->setFaceLoginEnabled($faceLoginEnabled);
            
            $entityManager->flush();
            $this->addFlash('success', 'Settings updated successfully!');
            
            return $this->redirectToRoute('app_candidate_settings');
        }

        return $this->render('candidate/auth/settings.html.twig', [
            'candidate' => $candidate,
        ]);
    }

    #[Route('/delete-account', name: 'app_candidate_delete_account', methods: ['POST'])]
    #[IsGranted('ROLE_CANDIDATE')]
    public function deleteAccount(Request $request, EntityManagerInterface $entityManager): Response
    {
        $candidate = $this->getUser();
        
        if ($this->isCsrfTokenValid('delete_account', $request->request->get('_token'))) {
            // Soft delete by banning the account
            $candidate->setBanned(true);
            $candidate->setEmail('deleted_' . time() . '_' . $candidate->getEmail());
            
            $entityManager->flush();
            
            // Logout and redirect
            $request->getSession()->invalidate();
            $this->addFlash('success', 'Your account has been deleted successfully.');
            
            return $this->redirectToRoute('app_home');
        }

        throw $this->createAccessDeniedException('Invalid CSRF token');
    }
}
