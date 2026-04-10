<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class UserManagementController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private CsrfTokenManagerInterface $csrfTokenManager
    ) {
    }

    #[Route('/', name: 'app_admin_users')]
    public function index(Request $request): Response
    {
        $search = $request->query->get('search', '');
        $role = $request->query->get('role', '');
        $status = $request->query->get('status', '');
        
        // Get users with filtering
        $users = $this->userRepository->searchUsers($search, $role, $status);
        
        // Get statistics
        $totalUsers = count($users);
        $activeUsers = count(array_filter($users, fn($user) => !$user->isBanned()));
        $bannedUsers = count(array_filter($users, fn($user) => $user->isBanned()));
        $verifiedUsers = count(array_filter($users, fn($user) => $user->isVerified()));
        
        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
            'search' => $search,
            'role' => $role,
            'status' => $status,
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'bannedUsers' => $bannedUsers,
            'verifiedUsers' => $verifiedUsers,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_users_show')]
    public function show(User $user): Response
    {
        return $this->render('admin/users/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/new', name: 'app_admin_users_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Store password as plain text
            $plainPassword = $form->get('password')->getData();
            if ($plainPassword) {
                $user->setPassword($plainPassword);
            }

            $this->userRepository->save($user, true);
            $this->addFlash('success', 'User created successfully!');
            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/users/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_users_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Store password as plain text if changed
            $plainPassword = $form->get('password')->getData();
            if ($plainPassword) {
                $user->setPassword($plainPassword);
            }

            $this->userRepository->save($user, true);
            $this->addFlash('success', 'User updated successfully!');
            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/users/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/ban', name: 'app_admin_users_ban', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function ban(Request $request, int $id): Response
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('app_admin_users');
        }
        
        if ($this->isCsrfTokenValid('ban' . $user->getId(), $request->request->get('_token'))) {
            $user->setBanned(true);
            $this->userRepository->save($user, true);
            $this->addFlash('success', 'User banned successfully!');
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/{id}/unban', name: 'app_admin_users_unban', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function unban(Request $request, int $id): Response
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('app_admin_users');
        }
        
        if ($this->isCsrfTokenValid('unban' . $user->getId(), $request->request->get('_token'))) {
            $user->setBanned(false);
            $this->userRepository->save($user, true);
            $this->addFlash('success', 'User unbanned successfully!');
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/{id}/verify', name: 'app_admin_users_verify', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function verify(Request $request, int $id): Response
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('app_admin_users');
        }
        
        if ($this->isCsrfTokenValid('verify' . $user->getId(), $request->request->get('_token'))) {
            $user->setVerified(true);
            $this->userRepository->save($user, true);
            $this->addFlash('success', 'User verified successfully!');
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/{id}/unverify', name: 'app_admin_users_unverify', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function unverify(Request $request, int $id): Response
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('app_admin_users');
        }
        
        if ($this->isCsrfTokenValid('unverify' . $user->getId(), $request->request->get('_token'))) {
            $user->setVerified(false);
            $this->userRepository->save($user, true);
            $this->addFlash('success', 'User unverified successfully!');
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/{id}/delete', name: 'app_admin_users_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('app_admin_users');
        }
        
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $this->userRepository->remove($user, true);
            $this->addFlash('success', 'User deleted successfully!');
        }

        return $this->redirectToRoute('app_admin_users');
    }
}
