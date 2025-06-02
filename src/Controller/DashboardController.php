<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;


use App\Entity\Note;
use App\Entity\Etat;
use App\Entity\Tag;
use Doctrine\Common\Collections\Criteria;
use App\Entity\VieNote;


use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DashboardController extends AbstractController
{
	#[IsGranted('ROLE_USER')]
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'controller_name' => 'DashboardController',
        ]);
    }
	#[IsGranted('ROLE_USER')]
	#[Route('/dashboardExemple', name: 'app_dashboard_exemple')]
    public function dashboardExemple(): Response
    {
        return $this->render('dashboard/exemple.html.twig', [
            'controller_name' => 'DashboardController',
        ]);
    }
	
	//fonction exemple
	#[IsGranted('ROLE_ADMIN')]
	#[Route('/dashboardAdmin', name: 'app_dashboard_admin')]
    public function dashboardAdmin(EntityManagerInterface $em): Response
    {
		$user = $this->getUser();

    // Critère 1 : Notes "En cours"
    $notesEnCours = $em->createQueryBuilder()
        ->select('n')
        ->from(Note::class, 'n')
        ->join('n.etat', 'e')
        ->where('e.non = :etat')
        ->setParameter('etat', 'En cours')
        ->getQuery()
        ->getResult();

    // Critère 2 : Notes taguées "Urgent"
    $notesUrgentes = $em->createQueryBuilder()
        ->select('n')
        ->from(Note::class, 'n')
        ->join('n.tag', 't')
        ->where('t.non = :tag')
        ->setParameter('tag', 'Urgent')
        ->getQuery()
        ->getResult();

    // Critère 3 : Notes de l'utilisateur connecté
        $notes = $user->getNotes();
		
	// Critère 4 : état = "En cours"
        $etatEnCours = $em->getRepository(Etat::class)->findOneBy(['non' => 'En cours']);
        $criteriaEtat = Criteria::create()->where(Criteria::expr()->eq('etat', $etatEnCours));
        $notesEnCours2 = $notes->matching($criteriaEtat);


    return $this->render('dashboard/admin.html.twig', [
        'notes_en_cours' => $notesEnCours,
		'notes_en_cours2' => $notesEnCours2,
        'notes_urgentes' => $notesUrgentes,
        'notes_utilisateur' => $notes,
    ]);
    }
	//fonction exemple récupération
	#[IsGranted('ROLE_ADMIN')]
	#[Route('/dashboardForm', name: 'formulaire_exemple')]
	public function formulaireExemple(Request $request): Response
    {
        // Récupère la donnée envoyée par le formulaire
        $exempleInput = $request->request->get('exemple_input');

        // Traitement de la donnée (par exemple, enregistrer dans la base de données ou autre action)

        // Pour l'exemple, on va juste afficher un message de confirmation
        return new Response(
            '<html><body>Formulaire soumis avec succès! Vous avez entré : ' . htmlspecialchars($exempleInput) . '</body></html>'
        );
    }
    // Nouvelle méthode avec criteria pour VieNote
    #[IsGranted('ROLE_USER')]
    #[Route('/dashboardVieNoteCriteria', name: 'app_dashboard_vienote_criteria')]
    public function dashboardVieNoteCriteria(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

    // Utilisation des criteria comme dans votre dashboardAdmin
    
    // Critère 1 : VieNote de l'utilisateur connecté avec criteria
        $mesVieNotes = $user->getVieNotes();
    
    // Critère 2 : Filtrer les VieNote récentes (dernières 48h) avec criteria
        $dateLimit = new \DateTimeImmutable('-48 hours');
        $criteriaRecentes = Criteria::create()
            ->where(Criteria::expr()->gte('updatedAt', $dateLimit))
            ->orderBy(['updatedAt' => 'DESC']);
        $vieNotesRecentes = $mesVieNotes->matching($criteriaRecentes);
    
    // Critère 3 : Filtrer par description contenant un mot spécifique
        $criteriaDescription = Criteria::create()
            ->where(Criteria::expr()->contains('description', 'modification'));
        $vieNotesModification = $mesVieNotes->matching($criteriaDescription);
    
    // Critère 4 : VieNote liées aux notes de l'utilisateur avec criteria
        $notesUtilisateur = $user->getNotes();
    
    // Pour chaque note, récupérer ses VieNote avec criteria
        $vieNotesParNote = [];
        foreach ($notesUtilisateur as $note) {
            $vieNotesNote = $note->getVieNotes();
        
        // Criteria pour trier par date décroissante
            $criteriaTri = Criteria::create()
                ->orderBy(['updatedAt' => 'DESC'])
                ->setMaxResults(5); // Limiter à 5 résultats
            
            $vieNotesTriees = $vieNotesNote->matching($criteriaTri);
        
            if ($vieNotesTriees->count() > 0) {
                $vieNotesParNote[$note->getTitre()] = $vieNotesTriees;
            }
        }

        return $this->render('dashboard/vienote_criteria.html.twig', [
            'mes_vienotes' => $mesVieNotes,
            'vienotes_recentes' => $vieNotesRecentes,
            'vienotes_modification' => $vieNotesModification,
            'vienotes_par_note' => $vieNotesParNote,
            'user' => $user,
        ]);
    }

// Méthode pour démontrer différents types de criteria
    #[IsGranted('ROLE_USER')]
    #[Route('/exemplesCriteria', name: 'app_exemples_criteria')]
    public function exemplesCriteria(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
    
    // Exemple 1 : Criteria simple sur les notes
        $notes = $user->getNotes();
    
    // Notes créées dans les 7 derniers jours
        $dateLimite = new \DateTimeImmutable('-7 days');
        $criteriaRecentes = Criteria::create()
            ->where(Criteria::expr()->gte('createdAt', $dateLimite))
            ->orderBy(['createdAt' => 'DESC']);
        $notesRecentes = $notes->matching($criteriaRecentes);
    
    // Exemple 2 : Criteria avec condition sur propriété
        $criteriaPropriete = Criteria::create()
            ->where(Criteria::expr()->eq('propriete', 1))
            ->andWhere(Criteria::expr()->isNotNull('endAt'));
        $notesSpecifiques = $notes->matching($criteriaPropriete);
    
    // Exemple 3 : Criteria sur VieNote avec plusieurs conditions
        $vieNotes = $user->getVieNotes();
    
        $criteriaComplexe = Criteria::create()
            ->where(Criteria::expr()->gte('updatedAt', new \DateTimeImmutable('-30 days')))
            ->andWhere(Criteria::expr()->contains('description', 'test'))
            ->orderBy(['updatedAt' => 'DESC'])
            ->setFirstResult(0)
            ->setMaxResults(10);
        $vieNotesComplexes = $vieNotes->matching($criteriaComplexe);

        return $this->render('dashboard/exemples_criteria.html.twig', [
            'notes_recentes' => $notesRecentes,
            'notes_specifiques' => $notesSpecifiques,
            'vienotes_complexes' => $vieNotesComplexes,
            'total_notes' => $notes->count(),
            'total_vienotes' => $vieNotes->count(),
        ]);
    }
    #[IsGranted('ROLE_USER')]
    #[Route('/dashboardVieNote', name: 'app_dashboard_vienote')]
    public function dashboardVieNote(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

    // Critère 1 : VieNote récentes (dernières 24h)
        $vieNotesRecentes = $em->createQueryBuilder()
            ->select('v')
            ->from(VieNote::class, 'v')
            ->where('v.updatedAt >= :date')
            ->setParameter('date', new \DateTimeImmutable('-24 hours'))
            ->orderBy('v.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();

    // Critère 2 : VieNote créées par l'utilisateur connecté
        $mesVieNotes = $em->createQueryBuilder()
            ->select('v')
            ->from(VieNote::class, 'v')
            ->where('v.createur = :user')
            ->setParameter('user', $user)
            ->orderBy('v.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();

    // Critère 3 : VieNote liées aux notes "En cours"
        $vieNotesEnCours = $em->createQueryBuilder()
            ->select('v')
            ->from(VieNote::class, 'v')
            ->join('v.note', 'n')
            ->join('n.etat', 'e')
            ->where('e.nom = :etat')
            ->setParameter('etat', 'En cours')
            ->getQuery()
            ->getResult();

        return $this->render('dashboard/vienote.html.twig', [
            'vienotes_recentes' => $vieNotesRecentes,
            'mes_vienotes' => $mesVieNotes,
            'vienotes_en_cours' => $vieNotesEnCours,
        ]);
    }
}