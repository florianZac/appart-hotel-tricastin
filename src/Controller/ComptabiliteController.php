<?php

namespace App\Controller;

use App\Entity\Frais;
use App\Form\ExportComptabiliteType;
use App\Form\FraisType;
use App\Repository\FraisRepository;
use App\Service\ComptabiliteExporter;
use App\Service\ComptabiliteExporterXlsx;
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
        private readonly ComptabiliteExporter     $exporter,
        private readonly ComptabiliteExporterXlsx $exporterXlsx,
        private readonly FraisRepository          $fraisRepo,
        private readonly EntityManagerInterface   $em,
    ) {}

    // ── Page principale ─────────────────────────────────────────

    #[Route('', name: 'admin_comptabilite', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $formExport = $this->createForm(ExportComptabiliteType::class);
        $formExport->handleRequest($request);

        $annee     = (int) date('Y');
        $fraisList = $this->fraisRepo->findByAnnee($annee);

        return $this->render('admin/comptabilite/index.html.twig', [
            'formExport' => $formExport,
            'fraisList'  => $fraisList,
            'annee'      => $annee,
        ]);
    }

    // ── Export CSV (ancien format, conservé) ────────────────────

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

    // ── Export Excel multi-onglets (nouveau) ────────────────────

    #[Route('/export-xlsx', name: 'admin_comptabilite_export_xlsx', methods: ['GET'])]
    public function exportXlsx(Request $request): Response
    {
        $formExport = $this->createForm(ExportComptabiliteType::class);
        $formExport->handleRequest($request);

        $data         = $formExport->getData();
        $annee        = $data['annee'] ?? (int) date('Y');
        $localisation = $data['localisation'] ?? null;

        return $this->exporterXlsx->exportXlsx($annee, $localisation);
    }

    // ── CRUD frais ──────────────────────────────────────────────

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
