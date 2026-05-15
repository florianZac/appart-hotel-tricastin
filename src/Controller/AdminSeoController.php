<?php

namespace App\Controller;

use App\Entity\SeoCocon;
use App\Entity\SeoPage;
use App\Form\SeoPageType;
use App\Repository\SeoCoconRepository;
use App\Repository\SeoPageRepository;
use App\Service\SeoAuditService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/seo', name: 'admin_seo_')]
#[IsGranted('ROLE_ADMIN')]
class AdminSeoController extends AbstractController
{
    public function __construct(
        private readonly SeoPageRepository   $seoRepo,
        private readonly SeoCoconRepository  $coconRepo,
        private readonly SeoAuditService     $auditService,
        private readonly EntityManagerInterface $em,
    ) {}

    // ── Dashboard SEO ─────────────────────────────────────────────────────

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        $pages  = $this->seoRepo->findAllOrdered();
        $cocons = $this->coconRepo->findAllWithPages();

        // Calcul des scores d'audit pour toutes les pages
        $audits = [];
        foreach ($pages as $page) {
            $audits[$page->getId()] = $this->auditService->audit($page);
        }

        // Statistiques globales
        $scores = array_column($audits, 'score');
        $stats  = [
            'total'     => count($pages),
            'vert'      => count(array_filter($scores, fn($s) => $s >= 80)),
            'orange'    => count(array_filter($scores, fn($s) => $s >= 50 && $s < 80)),
            'rouge'     => count(array_filter($scores, fn($s) => $s < 50)),
            'moyScore'  => count($scores) > 0 ? (int) round(array_sum($scores) / count($scores)) : 0,
        ];

        return $this->render('admin/seo/index.html.twig', [
            'pages'  => $pages,
            'cocons' => $cocons,
            'audits' => $audits,
            'stats'  => $stats,
        ]);
    }

    // ── Création ─────────────────────────────────────────────────────────

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $seoPage = new SeoPage();

        // Pré-remplissage depuis les suggestions
        if ($route = $request->query->get('route')) $seoPage->setRoute($route);
        if ($label = $request->query->get('label')) $seoPage->setLabel($label);

        $form = $this->createForm(SeoPageType::class, $seoPage, ['is_new' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->validateJsonFields($seoPage)) {
                $this->addFlash('danger', 'Le champ JSON-LD ou FAQ contient du JSON invalide.');
            } else {
                $this->em->persist($seoPage);
                $this->em->flush();
                $this->addFlash('success', "Page SEO « {$seoPage->getLabel()} » créée.");
                return $this->redirectToRoute('admin_seo_index');
            }
        }

        return $this->render('admin/seo/edit.html.twig', [
            'form'    => $form,
            'seoPage' => $seoPage,
            'is_new'  => true,
            'audit'   => null,
        ]);
    }

    // ── Édition ──────────────────────────────────────────────────────────

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(SeoPage $seoPage, Request $request): Response
    {
        $form = $this->createForm(SeoPageType::class, $seoPage, ['is_new' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->validateJsonFields($seoPage)) {
                $this->addFlash('danger', 'Le champ JSON-LD ou FAQ contient du JSON invalide.');
            } else {
                $this->em->flush();
                $this->addFlash('success', "Page SEO « {$seoPage->getLabel()} » mise à jour.");
                return $this->redirectToRoute('admin_seo_index');
            }
        }

        return $this->render('admin/seo/edit.html.twig', [
            'form'    => $form,
            'seoPage' => $seoPage,
            'is_new'  => false,
            'audit'   => $this->auditService->audit($seoPage),
        ]);
    }

    // ── Audit AJAX (rafraîchissement temps réel) ─────────────────────────

    #[Route('/{id}/audit', name: 'audit', methods: ['GET'])]
    public function audit(SeoPage $seoPage): JsonResponse
    {
        return $this->json($this->auditService->audit($seoPage));
    }

    // ── Suppression ──────────────────────────────────────────────────────

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(SeoPage $seoPage, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete_seo_' . $seoPage->getId(), $request->request->get('_token'))) {
            $label = $seoPage->getLabel();
            $this->em->remove($seoPage);
            $this->em->flush();
            $this->addFlash('success', "Page SEO « {$label} » supprimée.");
        } else {
            $this->addFlash('danger', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_seo_index');
    }

    // ── Gestion des cocons ───────────────────────────────────────────────

    #[Route('/cocons', name: 'cocons')]
    public function cocons(): Response
    {
        return $this->render('admin/seo/cocons.html.twig', [
            'cocons' => $this->coconRepo->findAllWithPages(),
        ]);
    }

    #[Route('/cocons/new', name: 'cocon_new', methods: ['GET', 'POST'])]
    public function coconNew(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $cocon = new SeoCocon();
            $cocon->setNom($request->request->getString('nom'));
            $cocon->setMotCleCocon($request->request->getString('motCleCocon') ?: null);
            $cocon->setDescription($request->request->getString('description') ?: null);
            $cocon->setCouleur($request->request->getString('couleur', '#3b82f6'));
            $this->em->persist($cocon);
            $this->em->flush();
            $this->addFlash('success', "Cocon « {$cocon->getNom()} » créé.");
            return $this->redirectToRoute('admin_seo_cocons');
        }

        return $this->render('admin/seo/cocon_form.html.twig', ['cocon' => null]);
    }

    #[Route('/cocons/{id}/delete', name: 'cocon_delete', methods: ['POST'])]
    public function coconDelete(SeoCocon $cocon, Request $request): Response
    {
        if ($this->isCsrfTokenValid('del_cocon_' . $cocon->getId(), $request->request->get('_token'))) {
            $this->em->remove($cocon);
            $this->em->flush();
            $this->addFlash('success', "Cocon « {$cocon->getNom()} » supprimé.");
        }
        return $this->redirectToRoute('admin_seo_cocons');
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function validateJsonFields(SeoPage $page): bool
    {
        foreach ([$page->getSchemaExtra(), $page->getFaqItems()] as $json) {
            if ($json) {
                json_decode($json);
                if (json_last_error() !== JSON_ERROR_NONE) return false;
            }
        }
        return true;
    }
}
