<?php

namespace App\Controller;

use App\Entity\VieNote;
use App\Form\VieNoteForm;
use App\Repository\VieNoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/vienote')]
final class VienoteController extends AbstractController
{
    #[Route(name: 'app_vienote_index', methods: ['GET'])]
    public function index(VieNoteRepository $vieNoteRepository): Response
    {
        return $this->render('vienote/index.html.twig', [
            'vie_notes' => $vieNoteRepository->findAll(),
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/new', name: 'app_vienote_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $vieNote = new VieNote();
        $form = $this->createForm(VieNoteForm::class, $vieNote);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Définir automatiquement le créateur et la date
            $vieNote->setCreateur($this->getUser());
            $vieNote->setUpdatedAt(new \DateTimeImmutable());
            
            $entityManager->persist($vieNote);
            $entityManager->flush();

            return $this->redirectToRoute('app_vienote_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('vienote/new.html.twig', [
            'vie_note' => $vieNote,
            'form' => $form,
        ]);
    }
}