<?php

namespace App\Controller\Admin;

use App\Entity\ComplaintType;
use App\Form\ComplaintTypeType;
use App\Repository\ComplaintTypeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/complaint-types')]
#[IsGranted('ROLE_ADMIN')]
class ComplaintTypeController extends AbstractController
{
    public function __construct(
        private ComplaintTypeRepository $complaintTypeRepository
    ) {
    }

    #[Route('/', name: 'app_admin_complaint_types')]
    public function index(): Response
    {
        $complaintTypes = $this->complaintTypeRepository->findAll();

        return $this->render('admin/complaint_types.html.twig', [
            'complaint_types' => $complaintTypes,
        ]);
    }

    #[Route('/new', name: 'app_admin_complaint_types_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $complaintType = new ComplaintType();
        $form = $this->createForm(ComplaintTypeType::class, $complaintType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->complaintTypeRepository->save($complaintType, true);
            $this->addFlash('success', 'Complaint type created successfully!');

            return $this->redirectToRoute('app_admin_complaint_types');
        }

        return $this->render('admin/complaint_types_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_complaint_types_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        error_log('=== COMPLAINT TYPE EDIT METHOD CALLED ===');
        error_log('Request method: ' . $request->getMethod());
        error_log('Complaint type ID from parameter: ' . $id);
        
        $complaintType = $this->complaintTypeRepository->find($id);
        if (!$complaintType) {
            error_log('Complaint type not found with ID: ' . $id);
            $this->addFlash('error', 'Complaint type not found.');
            return $this->redirectToRoute('app_admin_complaint_types');
        }
        
        error_log('Complaint type found: ' . $complaintType->getName());
        
        $form = $this->createForm(ComplaintTypeType::class, $complaintType);
        $form->handleRequest($request);
        
        error_log('Form submitted: ' . ($form->isSubmitted() ? 'YES' : 'NO'));
        
        if ($form->isSubmitted()) {
            error_log('Form valid: ' . ($form->isValid() ? 'YES' : 'NO'));
            
            if ($form->isValid()) {
                error_log('Form is valid, saving complaint type...');
                error_log('Type name: ' . $complaintType->getName());
                error_log('Urgency level: ' . $complaintType->getUrgencyLevel());
                
                $this->complaintTypeRepository->save($complaintType, true);
                $this->addFlash('success', 'Complaint type updated successfully!');
                error_log('Complaint type saved, redirecting...');

                return $this->redirectToRoute('app_admin_complaint_types');
            } else {
                error_log('Form submitted but invalid');
                $errors = $form->getErrors(true);
                foreach ($errors as $error) {
                    error_log('Form error: ' . $error->getMessage());
                }
            }
        }

        return $this->render('admin/complaint_types_edit.html.twig', [
            'form' => $form->createView(),
            'complaint_type' => $complaintType,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_complaint_types_delete', methods: ['POST'])]
    public function delete(Request $request, ComplaintType $complaintType): Response
    {
        if ($this->isCsrfTokenValid('delete'.$complaintType->getId(), $request->request->get('_token'))) {
            $this->complaintTypeRepository->remove($complaintType, true);
            $this->addFlash('success', 'Complaint type deleted successfully!');
        }

        return $this->redirectToRoute('app_admin_complaint_types');
    }
}
