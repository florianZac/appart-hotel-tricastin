<?php

namespace App\Form;

use App\Entity\SeoCocon;
use App\Entity\SeoPage;
use App\Service\SeoService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class SeoPageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = $options['is_new'];

        $builder
            // ── Identification ──────────────────────────────────────────
            ->add('label', TextType::class, [
                'label'       => 'Libellé admin',
                'attr'        => ['placeholder' => 'ex : Page d\'accueil'],
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('route', TextType::class, [
                'label'      => 'Nom de route Symfony',
                'disabled'   => !$isNew,
                'attr'       => ['placeholder' => 'ex : app_home'],
                'help'       => 'Lancez <code>bin/console debug:router</code> pour lister les routes.',
                'help_html'  => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Regex(['pattern' => '/^[a-z][a-z0-9_]*$/']),
                ],
            ])

            // ── Balises title & H1 — Andrieu ch.5 ──────────────────────
            ->add('titre', TextType::class, [
                'label'    => 'Balise <title> (55-65 car.)',
                'required' => false,
                'attr'     => [
                    'placeholder'  => 'Mot-clé cible en début — Marque en fin',
                    'maxlength'    => 255,
                    'data-counter' => 'true',
                    'data-min'     => 50,
                    'data-max'     => 65,
                ],
                'help'     => 'Andrieu : commence par le <strong>mot-clé cible</strong>, se termine par « Appart Hôtel Tricastin ».',
                'help_html' => true,
            ])
            ->add('h1', TextType::class, [
                'label'    => 'Balise H1 (≠ title, max 70 car.)',
                'required' => false,
                'attr'     => [
                    'placeholder'  => 'Formulation différente du title, contient le mot-clé',
                    'maxlength'    => 255,
                    'data-counter' => 'true',
                    'data-max'     => 70,
                ],
                'help'     => 'Andrieu <strong>ch.5 règle fondamentale</strong> : le H1 doit être différent du title pour couvrir plus de requêtes et ne pas créer de redondance.',
                'help_html' => true,
            ])

            // ── Sémantique / Cocon — Andrieu ch.7 ──────────────────────
            ->add('focusKeyword', TextType::class, [
                'label'    => 'Mot-clé cible principal',
                'required' => false,
                'attr'     => ['placeholder' => 'ex : appartement meublé Pont-Saint-Esprit'],
                'help'     => 'Andrieu ch.7 : <strong>1 seul</strong> mot-clé par page. Doit figurer dans le title, le H1 et la meta description.',
                'help_html' => true,
            ])
            ->add('secondaryKeywords', TextareaType::class, [
                'label'    => 'Mots-clés secondaires (LSI)',
                'required' => false,
                'attr'     => [
                    'rows'        => 3,
                    'placeholder' => "location meublée Tricastin\nstudio équipé Gard\nséjour courte durée Rhône",
                ],
                'help' => 'Andrieu : enrichissement sémantique (latent semantic indexing). Un mot-clé par ligne, 3 à 5 maxi.',
            ])

            // ── Meta description — Andrieu ch.5 ────────────────────────
            ->add('description', TextareaType::class, [
                'label'    => 'Meta description (120-160 car.)',
                'required' => false,
                'attr'     => [
                    'rows'         => 3,
                    'placeholder'  => 'Contient le mot-clé + appel à l\'action (CTA). Ex : "Réservez en ligne."',
                    'maxlength'    => 300,
                    'data-counter' => 'true',
                    'data-min'     => 120,
                    'data-max'     => 160,
                ],
                'help' => 'Andrieu ch.5 : incluez le mot-clé (mis en gras dans les SERP) + un <strong>CTA</strong> en fin de phrase.',
                'help_html' => true,
            ])

            // ── Fil d'Ariane — Andrieu ch.6 ────────────────────────────
            ->add('breadcrumbLabel', TextType::class, [
                'label'    => 'Label fil d\'Ariane',
                'required' => false,
                'attr'     => ['placeholder' => 'ex : Accueil, Nos appartements, Pont-Saint-Esprit…'],
                'help'     => 'Andrieu ch.6 : texte affiché dans le breadcrumb et le schéma BreadcrumbList.',
            ])

            // ── Paramètres techniques ───────────────────────────────────
            ->add('robots', ChoiceType::class, [
                'label'   => 'Directive robots',
                'choices' => [
                    'Indexer et suivre (défaut)'       => SeoPage::ROBOTS_INDEX_FOLLOW,
                    'Ne pas indexer, suivre les liens' => SeoPage::ROBOTS_NOINDEX_FOLLOW,
                    'Ne pas indexer, ne pas suivre'    => SeoPage::ROBOTS_NOINDEX_NOFOLLOW,
                    'Indexer, ne pas suivre'           => SeoPage::ROBOTS_INDEX_NOFOLLOW,
                ],
                'help' => 'Andrieu ch.3 : appliquer <code>noindex</code> sur les pages transactionnelles, espace client, CGU.',
                'help_html' => true,
            ])
            ->add('canonical', TextType::class, [
                'label'    => 'URL canonique',
                'required' => false,
                'attr'     => ['placeholder' => 'https://… (vide = URL de la page courante)'],
                'help'     => 'Andrieu ch.8 : évite le contenu dupliqué. Laissez vide sauf cas particulier.',
            ])

            // ── Open Graph — Andrieu ch.11 ──────────────────────────────
            ->add('ogImage', TextType::class, [
                'label'    => 'Image Open Graph (URL, 1200×630 px)',
                'required' => false,
                'attr'     => ['placeholder' => 'https://res.cloudinary.com/…'],
                'help'     => 'Andrieu ch.11 : une image optimisée augmente le CTR lors du partage sur les réseaux sociaux. Format recommandé : JPEG 1200×630.',
            ])
            ->add('ogType', ChoiceType::class, [
                'label'   => 'Type OG',
                'choices' => [
                    'Website (défaut)' => SeoPage::OG_TYPE_WEBSITE,
                    'Article'          => SeoPage::OG_TYPE_ARTICLE,
                ],
            ])

            // ── Hreflang — Andrieu ch.12 ────────────────────────────────
            ->add('hreflangFr', TextType::class, [
                'label'    => 'URL hreflang="fr"',
                'required' => false,
                'attr'     => ['placeholder' => 'https://…/fr/ (vide = généré automatiquement)'],
                'help'     => 'Andrieu ch.12 : signaux multilingues Google. Vide = URL générée depuis la route avec locale fr.',
            ])
            ->add('hreflangEn', TextType::class, [
                'label'    => 'URL hreflang="en"',
                'required' => false,
                'attr'     => ['placeholder' => 'https://…/en/ (vide = généré automatiquement)'],
            ])

            // ── Schéma JSON-LD — Andrieu ch.10 ─────────────────────────
            ->add('schemaType', ChoiceType::class, [
                'label'    => 'Type de schéma JSON-LD principal',
                'required' => false,
                'choices'  => [
                    '— Aucun —'                          => null,
                    'Page web (WebPage)'                  => SeoService::SCHEMA_WEBPAGE,
                    'Établissement hôtelier (LodgingBusiness + WebSite)' => SeoService::SCHEMA_LODGING,
                    'Page de contact (ContactPage)'       => SeoService::SCHEMA_CONTACT,
                    'FAQ (FAQPage — rich snippets)'       => SeoService::SCHEMA_FAQ,
                ],
                'placeholder' => false,
                'help' => 'Andrieu ch.10 : <strong>LodgingBusiness</strong> pour l\'accueil (+ SearchAction), <strong>FAQPage</strong> pour viser les featured snippets. Les fiches appartements génèrent Apartment automatiquement.',
                'help_html' => true,
            ])
            ->add('faqItems', TextareaType::class, [
                'label'    => 'Questions/Réponses FAQ (JSON)',
                'required' => false,
                'attr'     => [
                    'rows'        => 5,
                    'placeholder' => '[{"question":"Quelle est la durée minimale ?","answer":"La durée minimale est de 2 nuits."}]',
                    'class'       => 'font-monospace',
                ],
                'help' => 'Andrieu ch.10 : le schéma FAQPage déclenche des <strong>rich snippets</strong> dans les SERP Google (questions dépliables). Seulement si schemaType = FAQPage.',
                'help_html' => true,
            ])
            ->add('schemaExtra', TextareaType::class, [
                'label'    => 'Champs JSON-LD additionnels',
                'required' => false,
                'attr'     => [
                    'rows'        => 3,
                    'placeholder' => '{"telephone":"+33 4 66 …","sameAs":["https://facebook.com/…"]}',
                    'class'       => 'font-monospace',
                ],
                'help' => 'JSON fusionné dans le schéma généré. Utilisé pour ajouter téléphone, réseaux sociaux, horaires (signaux E-E-A-T Andrieu ch.10).',
            ])

            // ── Cocon sémantique — Andrieu ch.7 ────────────────────────
            ->add('cocon', EntityType::class, [
                'label'        => 'Cocon sémantique',
                'class'        => SeoCocon::class,
                'choice_label' => 'nom',
                'required'     => false,
                'placeholder'  => '— Hors cocon —',
                'help'         => 'Andrieu ch.7 : assigner chaque page à un cocon renforce le maillage interne thématique.',
            ])
            ->add('isCoconPivot', CheckboxType::class, [
                'label'    => 'Page pivot du cocon (hub recevant le plus de maillage)',
                'required' => false,
                'help'     => 'Andrieu ch.7 : la page pivot concentre le PageRank du cocon. Une seule par cocon.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SeoPage::class,
            'is_new'     => false,
        ]);
        $resolver->setAllowedTypes('is_new', 'bool');
    }
}
