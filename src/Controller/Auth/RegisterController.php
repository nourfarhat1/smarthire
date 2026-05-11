<?php

namespace App\Controller\Auth;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Service\AIService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Smalot\PdfParser\Parser;

#[Route('/register')]
class RegisterController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private AIService $aiService
    ) {
    }

    #[Route('/', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(RegistrationFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();

            // Check if email already exists
            if ($this->userRepository->findOneByEmail($user->getEmail())) {
                $this->addFlash('error', 'This email is already registered.');
                return $this->redirectToRoute('app_register');
            }

            // ========== CV HANDLING FOR CANDIDATES ==========
            if ($user->getRoleId() == 1) {
                $cvFile = $form->get('cvFile')->getData();

                if ($cvFile) {
                    $originalFilename = pathinfo($cvFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $cvFile->guessExtension();

                    try {
                        $cvFile->move(
                            $this->getParameter('kernel.project_dir') . '/public/uploads/cvs',
                            $newFilename
                        );
                        $user->setCvFilename($newFilename);

                        $cvPath = $this->getParameter('kernel.project_dir') . '/public/uploads/cvs/' . $newFilename;

                        if (file_exists($cvPath)) {
                            try {
                                $parser = new Parser();
                                $pdf = $parser->parseFile($cvPath);
                                $text = $pdf->getText();

                                $analysis = $this->aiService->analyzeResume($text);

                                if (isset($analysis['skills']) && !empty($analysis['skills'])) {
                                    $user->setSkills(json_encode($analysis['skills']));
                                    $this->addFlash('success', 'Skills detected: ' . implode(', ', $analysis['skills']));
                                }
                            } catch (\Exception $e) {
                                // CV parsing failed silently — not critical
                            }
                        }
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Failed to upload CV. Please try again.');
                        return $this->redirectToRoute('app_register');
                    }
                } else {
                    $this->addFlash('warning', 'CV is required for candidate registration.');
                    return $this->redirectToRoute('app_register');
                }
            }
            // ========== END CV HANDLING ==========

            // ========== FACEIO: Save facial ID if user enrolled their face ==========
            $faceioId = trim($request->request->get('faceio_id', ''));
            if (!empty($faceioId)) {
                $user->setFaceioId($faceioId);
                $user->setFaceLoginEnabled(true);
            }
            // ========== END FACEIO ==========

            // Store password as plain text
            $user->setPassword($user->getPassword());

            // Set default values
            $user->setCreatedAt(new \DateTime());
            $user->setVerified(false);
            $user->setBanned(false);

            if (!$user->getRoleId()) {
                $user->setRoleId(1);
            }

            // Save user
            $this->userRepository->save($user, true);

            // Generate verification token
            $verificationToken = bin2hex(random_bytes(32));
            $user->setVerificationToken($verificationToken);
            $this->userRepository->save($user, true);

            $this->addFlash('success', 'Registration successful! You can now log in.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/register.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/verify/{token}', name: 'app_register_verify')]
    public function verifyEmail(string $token): Response
    {
        $user = $this->userRepository->findOneBy(['verificationToken' => $token]);

        if (!$user) {
            $this->addFlash('error', 'Invalid verification token.');
            return $this->redirectToRoute('app_login');
        }

        if ($user->isVerified()) {
            $this->addFlash('info', 'Your account is already verified.');
            return $this->redirectToRoute('app_login');
        }

        $user->setVerified(true);
        $user->setVerificationToken(null);
        $this->userRepository->save($user, true);

        $this->addFlash('success', 'Your account has been verified! You can now log in.');

        return $this->redirectToRoute('app_login');
    }

    #[Route('/resend-verification', name: 'app_register_resend_verification', methods: ['POST'])]
    public function resendVerification(Request $request): Response
    {
        $email = $request->request->get('email');

        if (!$email) {
            $this->addFlash('error', 'Please provide your email address.');
            return $this->redirectToRoute('app_register');
        }

        $user = $this->userRepository->findOneByEmail($email);

        if (!$user) {
            $this->addFlash('error', 'No account found with this email address.');
            return $this->redirectToRoute('app_register');
        }

        if ($user->isVerified()) {
            $this->addFlash('info', 'Your account is already verified.');
            return $this->redirectToRoute('app_register');
        }

        $verificationToken = bin2hex(random_bytes(32));
        $user->setVerificationToken($verificationToken);
        $this->userRepository->save($user, true);

        $this->addFlash('success', 'Verification email has been sent.');

        return $this->redirectToRoute('app_register');
    }
}
