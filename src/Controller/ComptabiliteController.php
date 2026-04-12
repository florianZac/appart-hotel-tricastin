<?php

namespace App\Controller;

use App\Entity\Frais;
use App\Form\ExportComptabiliteType;
use App\Form\FraisType;
use App\Repository\FraisRepository;
use App\Service\ComptabiliteExporter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/comptabilite')]
#[IsGranted('ROLE_ADMIN')]
class ComptabiliteController extends AbstractController
{
    public function __construct(
        private readonly ComptabiliteExporter  $exporter,
        private readonly FraisRepository       $fraisRepo,
        private readonly EntityManagerInterface $em,
    ) {}

    // ── Page principale : formulaire d'export ───────────────────
    #[Route('', name: 'admin_comptabilite', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $formExport = $this->createForm(ExportComptabiliteType::class);
        $formExport->handleRequest($request);

        $annee = (int) date('Y');
        $fraisList = $this->fraisRepo->findByAnnee($annee);

        return $this->render('admin/comptabilite/index.html.twig', [
            'formExport' => $formExport,
            'fraisList'  => $fraisList,
            'annee'      => $annee,
        ]);
    }

    // ── Téléchargement CSV ──────────────────────────────────────
    #[Route('/export-csv', name: 'admin_comptabilite_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request): Response
    {
        $formExport = $this->createForm(ExportComptabiliteType::class);
        $formExport->handleRequest($request);

        $data        = $formExport->getData();
        $annee       = $data['annee'] ?? (int) date('Y');
        $appartement = $data['appartement'] ?? null;

        return $this->exporter->exportCsv($annee, $appartement);
    }

    // ── CRUD frais : Ajout ──────────────────────────────────────
    #[Route('/frais/nouveau', name: 'admin_frais_new', methods: ['GET', 'POST'])]
    public function newFrais(Request $request): Response
    {
        $frais = new Frais();
        $form  = $this->createForm(FraisType::class, $frais);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($frais);
            $this->em->flush();

            $this->addFlash('success', 'Frais ajouté avec succès.');
            return $this->redirectToRoute('admin_comptabilite');
        }

        return $this->render('admin/comptabilite/frais_form.html.twig', [
            'form'  => $form,
            'titre' => 'Ajouter un frais',
        ]);
    }

    // ── CRUD frais : Modification ───────────────────────────────
    #[Route('/frais/{id}/modifier', name: 'admin_frais_edit', methods: ['GET', 'POST'])]
    public function editFrais(Frais $frais, Request $request): Response
    {
        $form = $this->createForm(FraisType::class, $frais);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', 'Frais modifié avec succès.');
            return $this->redirectToRoute('admin_comptabilite');
        }

        return $this->render('admin/comptabilite/frais_form.html.twig', [
            'form'  => $form,
            'titre' => 'Modifier le frais',
        ]);
    }

    // ── CRUD frais : Suppression ────────────────────────────────
    #[Route('/frais/{id}/supprimer', name: 'admin_frais_delete', methods: ['POST'])]
    public function deleteFrais(Frais $frais, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete_frais_' . $frais->getId(), $request->request->get('_token'))) {
            $this->em->remove($frais);
            $this->em->flush();
            $this->addFlash('success', 'Frais supprimé.');
        }

        return $this->redirectToRoute('admin_comptabilite');
    }
}
