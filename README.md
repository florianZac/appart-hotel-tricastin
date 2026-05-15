# Appart Hôtel Tricastin — Projet Symfony

> Site vitrine et système de réservation pour un appart-hôtel en Drôme Provençale (Pierrelatte, Saint-Paul-Trois-Châteaux, Montélimar).  
> Projet monolithique **Symfony 7.4 LTS** avec **Bootstrap 5** (front + back dans le même projet).

**Auteur** : Florian Aizac | **Client** : Mickael Aizac  
**Licence** : Projet privé — Tous droits réservés. Utilisable uniquement par AIZAC. Toute reproduction est interdite.

---

## Table des matières

1. [Fonctionnalités](#fonctionnalités)
2. [Stack technique](#stack-technique)
3. [Installation (développement)](#installation-développement)
4. [Démarrage Docker (client)](#démarrage-docker-client)
5. [Structure du projet](#structure-du-projet)
6. [Design](#design)
7. [Configuration](#configuration)
8. [Comptes de test](#comptes-de-test)
9. [Système SEO — Andrieu](#système-seo--andrieu)
10. [Comptabilité & Export](#comptabilité--export)
11. [Factures PDF](#factures-pdf)
12. [Témoignages — Flux complet](#témoignages--flux-complet)
13. [Emails automatiques](#emails-automatiques)
14. [Commandes CRON](#commandes-cron)
15. [Tests unitaires](#tests-unitaires)
16. [Déploiement Heroku](#déploiement-heroku)
17. [Cloudinary — Migration images](#cloudinary--migration-images)
18. [Sécurité](#sécurité)
19. [Sauvegarde base de données](#sauvegarde-base-de-données)

---

## Fonctionnalités

### Front-office (public)
- **Page d'accueil** : hero, présentation des localisations, carrousel d'appartements, témoignages clients avec badge appartement, services
- **Les Appartements** : listing par localisation avec détails, équipements, galerie photos (Cloudinary)
- **Réservation** : formulaire complet avec choix d'appartement, dates, nombre de personnes, calcul automatique du montant
- **Calendrier public** : FullCalendar 6 affichant les disponibilités (vert = disponible, rouge = réservé, gris = ménage, orange = bloqué)
- **Contact** : formulaire d'envoi de message + carte Google Maps + coordonnées
- **i18n** : interface bilingue FR/EN avec switcher drapeau
- **Page 404** personnalisée
- **Design responsive** : mobile-first avec Bootstrap 5

### Espace client (authentifié)
- **Dashboard** : statistiques personnelles (réservations, paiements en attente)
- **Historique des réservations** : liste complète avec détail par réservation
- **Historique des paiements** : suivi des paiements Stripe (acompte, solde)
- **Factures PDF** : téléchargement de factures générées automatiquement (DomPDF) pour chaque réservation confirmée ou terminée
- **Gestion du profil** : modification des informations personnelles, désactivation de compte
- **Dépôt d'avis** : formulaire avec notation en étoiles après un séjour terminé (soumis à validation admin)

### Back-office (admin)
- **Dashboard analytique** : statistiques globales + 4 graphiques Chart.js (revenus mensuels, réservations par statut, taux d'occupation, top appartements par CA)
- **Gestion des réservations** : liste complète avec changement de statut (confirmer / annuler / terminer) + envoi d'emails automatiques + bouton facture PDF
- **Gestion du calendrier** : ajout/suppression de disponibilités par appartement via FullCalendar, 4 statuts (disponible, réservé, nettoyage, bloqué/maintenance), suppression automatique des chevauchements
- **Gestion des utilisateurs** : liste, édition des rôles
- **Gestion des témoignages** : dashboard complet (en attente / approuvés / refusés), approbation, refus, relance email ciblée, suivi des séjours sans avis
- **Gestion des paiements** : liste, suivi des paiements en retard, revenus par type
- **Comptabilité** : gestion des frais (hébergement site, nettoyage, réparations, assurance, taxe de séjour), export CSV mensuel complet + export Excel multi-onglets par localisation
- **Factures PDF** : génération et téléchargement de factures pour chaque réservation
- **Upload images** : image principale + galerie via Cloudinary
- **Référencement SEO** : module complet basé sur Andrieu (cocons, audit, JSON-LD, hreflang)

---

## Stack technique

| Composant       | Technologie                                      |
|-----------------|--------------------------------------------------|
| **Framework**   | Symfony 7.4 LTS                                  |
| **PHP**         | 8.2+                                             |
| **Template**    | Twig                                             |
| **CSS**         | Bootstrap 5.3 + CSS custom                       |
| **Icons**       | Bootstrap Icons                                  |
| **Fonts**       | Cormorant Garamond + Montserrat                  |
| **ORM**         | Doctrine ORM                                     |
| **BDD**         | MySQL (dev) / PostgreSQL (prod)                  |
| **JS**          | Vanilla JS + FullCalendar 6 + Chart.js 4         |
| **Mailer**      | Symfony Mailer (Brevo SMTP)                      |
| **Paiement**    | Stripe (acompte + solde)                         |
| **Images**      | Cloudinary (upload + CDN)                        |
| **PDF**         | DomPDF (factures)                                |
| **Bundler**     | Webpack Encore                                   |
| **i18n**        | Symfony Translation (FR/EN)                      |

---

## Installation (développement)

### Prérequis
- **PHP 8.2+** avec extensions : `pdo_mysql`, `intl`, `mbstring`, `xml`
- **Composer** — pour Windows : https://getcomposer.org/Composer-Setup.exe
- **Symfony CLI** — `scoop update symfony-cli`
- **Node.js** (pour Webpack Encore)
- **Vérification** : `composer -v` et `symfony -v`

Versions testées :
```
Composer version 2.9.5 2026-01-29 11:40:53
PHP version 8.4.15 (D:\wamp64\bin\php\php8.4.15\php.exe)
Symfony CLI version 5.16.1 (c) 2021-2026 Fabien Potencier (2025-11-25T07:30:20Z - stable)
```

### Étapes

```bash
# 1. Cloner le projet
git clone <votre-repo> appart-hotel-tricastin
cd appart-hotel-tricastin

# 2. Installer les dépendances PHP
composer install

# 3. Installer les dépendances JS
npm install

# 4. Configurer l'environnement
cp .env .env.local
# Éditer .env.local avec vos valeurs :
#   DATABASE_URL, MAILER_DSN, APP_URL,
#   STRIPE_PUBLIC_KEY, STRIPE_SECRET_KEY, STRIPE_WEBHOOK_SECRET,
#   CLOUDINARY_URL

# 5. Créer la base de données et les tables
php bin/console doctrine:database:create
php bin/console make:migration
php bin/console doctrine:migrations:migrate

# 6. Charger les données initiales
php bin/console doctrine:fixtures:load --no-interaction

# 7. Créer le compte admin
php bin/console app:create-admin

# 8. Compiler les assets
npm run build

# 9. Lancer le serveur de développement
symfony server:start
# OU
php -S localhost:8000 -t public/
```

### Accès
- **Site public** : http://localhost:8000
- **Espace client** : http://localhost:8000/espace-client
- **Admin** : http://localhost:8000/admin

---

## Démarrage Docker (client)

> Aucune installation PHP ou Composer requise. Un seul logiciel suffit : **Docker Desktop**.

### Prérequis

Télécharger et installer : https://www.docker.com/products/docker-desktop/  
*(choisir la version Intel/AMD si vous n'êtes pas sur une puce ARM)*

Après l'installation, lancez Docker Desktop et attendez que l'icône dans la barre des tâches soit **verte** (Running).

### Étape 1 — Configurer vos clés

Ouvrez le fichier `.env.docker` et remplissez les valeurs marquées `<À_REMPLIR>` :

| Variable                | Où la trouver |
|-------------------------|---------------|
| `APP_SECRET`            | N'importe quelle chaîne de 32 caractères aléatoires |
| `MAILER_DSN`            | Votre interface Brevo → SMTP & API → Clés SMTP |
| `CLOUDINARY_URL`        | Votre tableau de bord Cloudinary → Dashboard |
| `STRIPE_PUBLIC_KEY`     | Votre compte Stripe → Développeurs → Clés API |
| `STRIPE_SECRET_KEY`     | Idem |
| `STRIPE_WEBHOOK_SECRET` | Stripe → Développeurs → Webhooks |

### Étape 2 — Ouvrir un terminal dans le dossier

Faites un **clic droit** sur le dossier du projet → **Ouvrir dans le terminal**  
(ou tapez `cmd` dans la barre d'adresse de l'Explorateur Windows)

### Étape 3 — Lancer l'application

```bash
docker compose up --build
```

Le premier démarrage prend **5 à 10 minutes** (téléchargement et compilation).  
Les démarrages suivants prennent **moins d'une minute**.

Quand vous voyez `Application disponible sur http://localhost`, ouvrez votre navigateur :

→ **http://localhost**

### Commandes Docker utiles

| Action                   | Commande                          |
|--------------------------|-----------------------------------|
| Démarrer                 | `docker compose up -d`            |
| Arrêter                  | `docker compose down`             |
| Voir les logs            | `docker compose logs -f app`      |
| Redémarrer               | `docker compose restart app`      |
| Accéder au terminal PHP  | `docker compose exec app bash`    |

### En cas de problème

**La page ne s'affiche pas**
- Vérifiez que Docker Desktop est bien lancé (icône verte dans la barre des tâches)
- Attendez 30 secondes supplémentaires après le message "Application disponible"

**Erreur "port 80 already in use"**
- Un autre logiciel utilise le port 80 (IIS, WAMP, etc.)
- Arrêtez-le ou modifiez dans `docker-compose.yml` la ligne `"80:80"` en `"8080:80"`
- Accédez ensuite via http://localhost:8080

**Perte de données**
- Les données sont stockées dans un volume Docker nommé `tricastin_db_data`
- Elles persistent entre les redémarrages
- Pour une sauvegarde, contactez votre prestataire

---

## Structure du projet

```
appart-hotel-tricastin/
├── config/
│   ├── packages/                          # Configuration des bundles Symfony
│   │   ├── doctrine.yaml                  # ORM Doctrine + connexion BDD
│   │   ├── security.yaml                  # Authentification, rôles, firewall, access_control
│   │   ├── stripe.yaml                    # Clés API Stripe (paiement)
│   │   ├── translation.yaml               # i18n FR/EN, locale par défaut
│   │   ├── webpack_encore.yaml            # Compilation des assets JS/CSS
│   │   └── ...
│   ├── routes/                            # Définition des routes par bundle
│   ├── services.yaml                      # Injection de dépendances : Mailer, Stripe, Cloudinary
│   └── routes.yaml                        # Routes principales de l'application
├── migrations/                            # Fichiers de migration Doctrine (versioning BDD)
├── public/
│   ├── build/                             # Assets compilés par Webpack Encore (prod)
│   ├── css/style.css                      # Feuille de style globale (palette dorée/bleu nuit)
│   ├── js/main.js                         # JS principal : navbar, scroll-reveal, carrousel
│   ├── images/                            # Images locales des appartements (fallback Cloudinary)
│   ├── favicon.ico                        # Favicon maison dorée sur fond dark
│   └── index.php                          # Point d'entrée unique Symfony (front controller)
├── src/
│   ├── Command/
│   │   ├── CleanDisponibilitesCommand.php # Nettoie les chevauchements entre Disponibilités et Réservations
│   │   ├── CreateAdminCommand.php         # Création d'un compte admin en CLI
│   │   ├── SendRappelsReservationsCommand.php  # CRON : rappel J-3 avant arrivée
│   │   ├── SendDemandeAvisCommand.php     # CRON : email demande d'avis post-séjour
│   │   └── CloudinaryMigrateCommand.php   # Migration des images locales vers Cloudinary
│   ├── Controller/
│   │   ├── HomeController.php             # Page d'accueil + mentions légales
│   │   ├── AppartementController.php      # Listing et détail des appartements + SeoService
│   │   ├── ReservationController.php      # Formulaire de réservation public
│   │   ├── ContactController.php          # Formulaire de contact + envoi email
│   │   ├── SecurityController.php         # Login, logout, mot de passe oublié, reset
│   │   ├── RegistrationController.php     # Inscription + vérification email AJAX
│   │   ├── ClientController.php           # Espace client : dashboard, réservations, paiements
│   │   ├── TemoignageController.php       # Dépôt d'avis par le client après séjour
│   │   ├── PaymentController.php          # Paiement Stripe : acompte, solde, webhook
│   │   ├── CalendrierController.php       # API FullCalendar : disponibilités publiques
│   │   ├── FactureController.php          # Génération et téléchargement de factures PDF
│   │   ├── AdminController.php            # Dashboard admin analytique, réservations, calendrier
│   │   ├── AdminTemoignageController.php  # Admin : approuver/refuser/relancer témoignages
│   │   ├── UserController.php             # Admin : gestion des utilisateurs (liste, édition, rôles)
│   │   ├── ComptabiliteController.php     # Gestion comptabilité + export CSV + export Excel
│   │   ├── AdminSeoController.php         # CRUD admin SEO + audit AJAX
│   │   ├── SitemapController.php          # /sitemap.xml, /plan-du-site, /robots.txt
│   │   └── LocaleController.php           # Switcher de langue FR/EN
│   ├── DataFixtures/
│   │   ├── LocalisationFixtures.php       # Données initiales : 3 villes
│   │   ├── AppartementFixtures.php        # Données initiales : appartements
│   │   ├── TemoignageFixtures.php         # Données initiales : 6 avis approuvés
│   │   ├── UserFixtures.php               # Données initiales : comptes utilisateurs
│   │   └── FraisFixtures.php              # Données initiales : frais comptables
│   ├── Entity/
│   │   ├── Appartement.php                # nom, type, surface, capacité, prix, galerie, localisation
│   │   ├── Disponibilite.php              # Créneaux FullCalendar (disponible/réservé/nettoyage/bloqué)
│   │   ├── Localisation.php               # Ville/adresse regroupant plusieurs appartements
│   │   ├── Payment.php                    # Paiement Stripe : montant, type, statut
│   │   ├── Reservation.php                # dates, client, appartement, statut, paiement, facture
│   │   ├── Temoignage.php                 # Avis client : note, commentaire, statut, appartement
│   │   ├── User.php                       # nom, prénom, email, adresse, rôles, mot de passe
│   │   ├── SeoPage.php                    # Entity SEO principale (1 row = 1 route Symfony)
│   │   ├── SeoCocon.php                   # Cocon sémantique (ch.7 Andrieu)
│   │   └── Frais.php                      # type, montant, périodicité, appartement
│   ├── EventSubscriber/
│   │   ├── LocaleSubscriber.php           # Gestion de la langue en session
│   │   ├── SecurityHeadersSubscriber.php  # Headers HTTP sécurité (X-Frame-Options, etc.)
│   │   └── FormSanitizerSubscriber.php    # Sanitisation automatique de tous les formulaires
│   ├── Form/
│   │   ├── ContactType.php
│   │   ├── ProfileType.php
│   │   ├── RegistrationType.php
│   │   ├── ReservationType.php
│   │   ├── TemoignageType.php
│   │   ├── UserType.php
│   │   ├── SeoPageType.php
│   │   ├── ExportComptabiliteType.php     # Formulaire export comptable (année + localisation + appartement)
│   │   ├── FraisType.php
│   │   └── Extension/
│   │       └── SanitizerFormExtension.php # Extension globale : branche le sanitizer sur tous les formulaires
│   ├── Repository/
│   │   ├── AppartementRepository.php      # findAllActifs()
│   │   ├── DisponibiliteRepository.php    # findByAppartementAndPeriode(), isDisponible()
│   │   ├── LocalisationRepository.php     # findAllWithAppartements()
│   │   ├── PaymentRepository.php          # findRecents(), findEnRetard(), getRevenusParType()
│   │   ├── ReservationRepository.php      # findRecentes(), findByUser(), findConfirmeesParAppartement()
│   │   ├── TemoignageRepository.php       # findActifs(), findByStatut(), findByUserAndReservation()
│   │   ├── UserRepository.php
│   │   ├── SeoCoconRepository.php
│   │   ├── SeoPageRepository.php
│   │   └── FraisRepository.php            # findByAnnee(), getTotauxParMois()
│   ├── Service/
│   │   ├── AnalyticsService.php           # Données Chart.js : revenus, occupation, top appartements
│   │   ├── CloudinaryService.php          # Upload/suppression d'images sur Cloudinary CDN
│   │   ├── ComptabiliteExporter.php       # Génération CSV comptable
│   │   ├── ComptabiliteExporterXlsx.php   # Génération Excel multi-onglets (PhpSpreadsheet)
│   │   ├── DateService.php                # Utilitaires de manipulation de dates
│   │   ├── DistanceService.php            # Calcul de distance entre deux points GPS
│   │   ├── FactureService.php             # Génération de factures PDF via DomPDF
│   │   ├── LogService.php                 # Service de journalisation applicative
│   │   ├── MailerService.php              # 10 méthodes d'envoi d'emails
│   │   ├── NominatimService.php           # Géocodage d'adresses via API Nominatim
│   │   ├── OsrmService.php                # Calcul d'itinéraire via API OSRM
│   │   ├── SanitizerService.php           # Nettoyage/sanitisation des entrées utilisateur (XSS)
│   │   ├── SeoService.php                 # Résolution SEO + tous les schémas JSON-LD
│   │   ├── SeoAuditService.php            # Score on-page (critères Andrieu)
│   │   └── StripeService.php              # Sessions Stripe, gestion webhook paiement
│   ├── Twig/
│   │   ├── SeoExtension.php               # seo_resolve(), seo_breadcrumb_list()
│   │   └── CloudinaryExtension.php        # Filtre Twig pour transformer les URLs Cloudinary
│   └── Kernel.php
├── templates/
│   ├── admin/
│   │   ├── base.html.twig                 # Layout admin : sidebar + flash messages
│   │   ├── dashboard.html.twig            # Dashboard analytique + 4 graphiques Chart.js + widget SEO
│   │   ├── reservations.html.twig
│   │   ├── temoignages.html.twig
│   │   ├── calendrier.html.twig
│   │   ├── users.html.twig
│   │   ├── user_edit.html.twig
│   │   ├── profile_edit.html.twig
│   │   ├── paiements.html.twig
│   │   ├── comptabilite/
│   │   │   ├── index.html.twig            # Dashboard comptabilité + export Excel + export CSV
│   │   │   └── frais_form.html.twig
│   │   └── seo/
│   │       ├── index.html.twig            # Dashboard SEO avec scores Andrieu
│   │       ├── edit.html.twig             # Formulaire avec audit en temps réel
│   │       ├── _audit_panel.html.twig     # Partial score réutilisable
│   │       ├── cocons.html.twig           # Liste des cocons sémantiques
│   │       └── cocon_form.html.twig       # Formulaire création cocon
│   ├── sitemap/
│   │   ├── index.xml.twig                 # Sitemap XML
│   │   └── index.html.twig                # Plan du site HTML (/plan-du-site)
│   ├── client/
│   │   ├── dashboard.html.twig
│   │   ├── reservations.html.twig
│   │   ├── reservation_detail.html.twig
│   │   ├── paiements.html.twig
│   │   └── profile_edit.html.twig
│   ├── pdf/
│   │   └── facture.html.twig              # Template HTML facture PDF (DomPDF)
│   ├── temoignage/
│   │   └── new.html.twig
│   ├── emails/                            # 10 templates emails
│   │   ├── base_email.html.twig
│   │   ├── bienvenue.html.twig
│   │   ├── confirmation_reservation.html.twig
│   │   ├── rappel_reservation.html.twig
│   │   ├── annulation_reservation.html.twig
│   │   ├── confirmation_paiement.html.twig
│   │   ├── echeance_paiement.html.twig
│   │   ├── reset_password.html.twig
│   │   ├── admin_nouvelle_reservation.html.twig
│   │   └── demande_avis.html.twig
│   ├── security/
│   │   ├── login.html.twig
│   │   ├── register.html.twig
│   │   ├── forgot_password.html.twig
│   │   └── reset_password.html.twig
│   ├── appartement/
│   ├── contact/
│   ├── home/
│   ├── reservation/
│   ├── payment/
│   ├── bundles/TwigBundle/Exception/
│   │   └── error404.html.twig
│   └── base.html.twig                     # Layout principal — Head SEO complet
├── translations/
│   ├── messages.fr.yaml
│   └── messages.en.yaml
├── .env
├── composer.json
├── package.json
├── webpack.config.js
└── README.md
```

---

## Design

Le design s'inspire du site [appart-hotel-tricastin.com](https://appart-hotel-tricastin.com/) avec :
- **Palette** : doré (`#c8a962`), bleu nuit (`#1a2744`), crème (`#fdf8f0`)
- **Typographie** : Cormorant Garamond (titres) + Montserrat (corps)
- **Composants** : hero plein écran, cards appartements avec hover, carrousel témoignages, formulaires stylisés, dashboard admin sidebar avec graphiques Chart.js
- **Animations** : scroll-reveal, hover transitions, navbar dynamique

---

## Configuration

### Base de données

```env
### MySQL (développement — WAMP)
DATABASE_URL="mysql://root:@127.0.0.1:3306/appart_hotel_tricastin?serverVersion=8.0"

### PostgreSQL (production)
```env
DATABASE_URL="postgresql://user:password@host:5432/appart_hotel_tricastin"
```

### Configuration Email (Brevo SMTP)
```env
# Gmail
MAILER_DSN=gmail://user:password@default
ou 
# Brevo 
MAILER_DSN=smtp://login:password@smtp-relay.brevo.com:587
```

### Stripe
```env
STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

###  Configuration Cloudinary
```env
CLOUDINARY_URL=cloudinary://API_KEY:API_SECRET@CLOUD_NAME
```

### URL de l'application
```env
APP_URL=http://localhost:8000
```
## Comptes de test

| Rôle    | Email                              | Mot de passe   |
|---------|------------------------------------|----------------|
| Admin   | admin@appart-hotel-tricastin.fr    | Admin@2026!    |
| Client 1 | marie.dupont@email.fr             | Client@2026!   |
| Client 2 | jean.martin@email.fr              | Client@2026!   |

## Utilisateur créer de base pour test : 

1. **Admin**
Email admin : admin@appart-hotel-tricastin.fr
Mot de passe : Admin@2026!
 
2. **Client 1**
Email client : marie.dupont@email.fr
Mot de passe : Client@2026!

3. **Client 2**
Email client : jean.martin@email.fr
Mot de passe : Client@2026!

---


---

## Système SEO — Andrieu

Basé sur **Andrieu, "Réussir son référencement web"** (Eyrolles, 2022-2023).

### Stratégies implémentées

| Chapitre | Stratégie | Implémentation |
|----------|-----------|----------------|
| Ch.3 | robots.txt crawl budget | `SitemapController::robots()` — bloque /admin/, /mon-espace/, bots scrapers |
| Ch.4 | Sitemap XML + HTML | `/sitemap.xml` (priorités différenciées) + `/plan-du-site` (maillage) |
| Ch.5 | **Title ≠ H1** | `SeoPage.titre` ≠ `SeoPage.h1` — principe fondamental |
| Ch.5 | Title 55-65 car. + mot-clé en tête | `SeoAuditService` + compteur admin |
| Ch.5 | Meta description 120-160 car. + CTA | Champ + compteur + audit |
| Ch.6 | Fil d'Ariane + BreadcrumbList | `SeoService::buildBreadcrumbList()` + `base.html.twig` |
| Ch.7 | Cocon sémantique | Entity `SeoCocon`, `SeoPage.cocon`, page pivot/satellites |
| Ch.7 | Mot-clé cible (1 par page) | `SeoPage.focusKeyword` + mots-clés LSI secondaires |
| Ch.8 | Canonical par page | `SeoPage.canonical` ou URL courante auto |
| Ch.9 | Performances | `preconnect`, `dns-prefetch` dans `base.html.twig` |
| Ch.10 | Données structurées | LodgingBusiness + WebSite/SearchAction, Apartment, BreadcrumbList, ContactPage, FAQPage, AggregateRating |
| Ch.10 | E-E-A-T | `schemaExtra` JSON (téléphone, sameAs, horaires) |
| Ch.11 | Open Graph + Twitter Card | `base.html.twig` — og:image 1200×630, og:type, og:locale |
| Ch.12 | Hreflang FR/EN + x-default | Génération auto ou manuelle, `base.html.twig` |

### Configuration initiale recommandée

Accédez à `/admin/seo` et complétez :

1. **Cocons** (`/admin/seo/cocons`) :
   - Vérifiez que les 2 cocons sont bien créés
   - Assignez chaque page à son cocon

2. **Page d'accueil** (`app_home`) :
   - Schéma : `LodgingBusiness`
   - Renseignez `schemaExtra` avec le numéro de téléphone réel et les réseaux sociaux

3. **Appartements** : SEO généré automatiquement depuis la BDD — aucune config requise

4. **Images OG** :
   - Créez `public/images/og-default.jpg` (1200×630 px) pour le fallback
   - Renseignez une image par page dans le champ "Image Open Graph"

### Utilisation dans les templates enfants

**Surcharge depuis le controller (pages dynamiques) :**

```php
$seoOverride = $seoService->buildForAppartement($appartement, $temoignages);
return $this->render('appartement/detail.html.twig', [
    'appartement' => $appartement,
    'seoOverride' => $seoOverride,
]);
```

**Surcharge légère depuis un template Twig :**

```twig
{% extends 'base.html.twig' %}
{% set seoOverride = {
    titre: 'Mon titre custom',
    description: 'Ma description',
    robots: 'noindex, follow'
} %}
```

**Injection d'un schéma JSON-LD supplémentaire :**

```twig
{% block seo_extra %}
<script type="application/ld+json">
{{ seo_breadcrumb_list([
    {label: 'Accueil', url: url('app_home')},
    {label: 'Ma page', url: app.request.uri}
])|raw }}
</script>
{% endblock %}
```

---

## Comptabilité & Export

### Gestion des frais

Les frais sont des dépenses associées à un appartement ou à la structure globale.  
Types supportés : Hébergement site, Nettoyage, Réparation, Assurance, Taxe de séjour, Autre.  
Périodicités : Mensuel (×12 dans le bilan annuel), Annuel, Ponctuel.

### Routes comptabilité

| Route                                | Méthode   | Description                    |
|--------------------------------------|-----------|--------------------------------|
| `admin_comptabilite`                 | GET       | Dashboard comptabilité         |
| `admin_comptabilite_export_csv`      | GET       | Téléchargement CSV             |
| `admin_comptabilite_export_xlsx`     | GET       | Téléchargement Excel multi-onglets |
| `admin_frais_new`                    | GET/POST  | Ajout d'un frais               |
| `admin_frais_edit`                   | GET/POST  | Modification d'un frais        |
| `admin_frais_delete`                 | POST      | Suppression d'un frais         |

### Export Excel multi-onglets (PhpSpreadsheet)

Génère `comptabilite_tricastin_AAAA.xlsx` avec :

| Onglet | Contenu |
|--------|---------|
| Pont-Saint-Esprit | Réservations mois/mois + taux d'occupation + frais + bilan |
| Saint-Paul-Trois-Châteaux | Idem |
| Tulette | Idem |
| Récapitulatif | Tableau comparatif consolidé — résultats en vert/rouge |

> Installation requise : `composer require phpoffice/phpspreadsheet`

### Structure du CSV généré

```
═══════════════════════════════════════════════════
BILAN COMPTABLE 2026 — Tous les appartements
═══════════════════════════════════════════════════

Janvier 2026
Client;N° Facture;Appartement;Date début;Date fin;Nb jours;Prix unitaire (€/nuit);Total hébergement (€)
Jean Dupont;FAC-2026-001;Studio Lavande;05/01/2026;12/01/2026;7;85,00;595,00

SOUS-TOTAL JANVIER;;;;;14 jours;Taux occupation : 22,6 %;1 365,00 €

...

RÉCAPITULATIF TAUX D'OCCUPATION
Mois;Jours occupés;Jours disponibles;Taux (%)
Janvier;14;31;45,2 %
TOTAL ANNUEL;180;365;49,3 %

DÉTAIL DES FRAIS — 2026
Type;Libellé;Appartement;Périodicité;Mois;Montant (€)
Hébergement du site;Hébergement site web + nom de domaine;Global;Annuel;—;120,00
TOTAL FRAIS ANNUELS;;;;;1 350,00 €

BILAN FINANCIER — 2026
Total revenus hébergement;18 500,00 €
Total frais annuels;1 350,00 €
RÉSULTAT NET;17 150,00 €
```

**Notes techniques CSV :**
- Séparateur `;` — compatible Excel FR nativement
- Encodage UTF-8 avec BOM — accents corrects dans Excel
- Frais mensuels comptés ×12 dans le total annuel automatiquement
- Frais globaux : `appartement = null` — apparaissent dans tous les exports
- Taux d'occupation : calculé sur le nombre de jours calendaires du mois

---

## Factures PDF

1. **Réservation confirmée ou terminée** : numéro de facture auto-généré (`FAC-2026-0001`)
2. **Admin** : depuis la liste des réservations → icône PDF → téléchargement
3. **Client** : depuis le détail de sa réservation → "Télécharger la facture"
4. **Contenu** : en-tête Appart Hôtel Tricastin, infos client, détail prestation, totaux (TTC, déjà payé, solde restant), historique des paiements Stripe
5. **Technologie** : DomPDF, template `templates/pdf/facture.html.twig`

---

## Témoignages — Flux complet

1. **Séjour terminé** : la commande CRON `app:send-demande-avis` envoie un email au client avec un lien vers le formulaire d'avis
2. **Client connecté** : remplit le formulaire (note en étoiles + commentaire) lié à l'appartement et à la réservation
3. **Témoignage créé** : statut `en_attente`, invisible sur le site
4. **Admin** : depuis le dashboard témoignages, peut approuver, refuser, ou relancer un client par email
5. **Témoignage approuvé** : s'affiche sur la page d'accueil avec le badge de l'appartement concerné

---

## Dashboard analytique (Chart.js)

| Graphique | Type | Données |
|-----------|------|---------|
| **Revenus mensuels** | Barres | Paiements réussis groupés par mois |
| **Réservations par statut** | Doughnut | En attente / Confirmées / Annulées / Terminées |
| **Taux d'occupation** | Courbe | % jours occupés par mois |
| **Top appartements** | Barres horizontales | Top 5 par chiffre d'affaires |

Les données sont calculées par `AnalyticsService` et transmises au template en JSON pour Chart.js 4.

---

## Emails automatiques

10 templates d'emails (héritent de `base_email.html.twig`) :

| Déclencheur | Destinataire | Template |
|-------------|--------------|----------|
| Inscription | Client | `bienvenue.html.twig` |
| Nouvelle réservation | Admin + Client | `admin_nouvelle_reservation.html.twig` + `confirmation_reservation.html.twig` |
| Rappel J-3 (CRON) | Client | `rappel_reservation.html.twig` |
| Annulation | Client | `annulation_reservation.html.twig` |
| Paiement reçu | Client | `confirmation_paiement.html.twig` |
| Échéance paiement | Client | `echeance_paiement.html.twig` |
| Mot de passe oublié | Client | `reset_password.html.twig` |
| Demande d'avis (CRON) | Client | `demande_avis.html.twig` |
| Message contact | Admin | *(ContactController)* |

---

## Commandes CRON

```bash
# Rappels J-3 avant arrivée (quotidien 09h)
php bin/console app:send-rappels-reservations

# Demande d'avis post-séjour (quotidien 10h)
php bin/console app:send-demande-avis

# Nettoyage des disponibilités
php bin/console app:clean-disponibilites

# Création d'un admin
php bin/console app:create-admin
```

**Planification Linux (production) :**
```cron
0 9  * * * cd /chemin/projet && php bin/console app:send-rappels-reservations
0 10 * * * cd /chemin/projet && php bin/console app:send-demande-avis
```

**Planification Windows :** utiliser le **Planificateur de tâches** (`taskschd.msc`).

**Vérification et débogage des commandes :**
```bash
# Vérifie qu'un fichier de commande existe
dir src\Command\CleanDisponibilitesCommand.php

# Vide le cache
php bin/console cache:clear

# Vérifie la syntaxe PHP
php -l src\Command\CleanDisponibilitesCommand.php

# Liste toutes les commandes app:
php bin/console list app
```

---

## Tests unitaires

### Installation

```bash
composer require --dev phpunit/phpunit
composer require --dev symfony/browser-kit symfony/css-selector
```

### Lancement

```bash
# Tests unitaires uniquement
php vendor/bin/phpunit --testsuite Unit

# Tests fonctionnels (nécessitent une BDD test)
php vendor/bin/phpunit --testsuite Functional

# Tout avec rapport de couverture HTML (nécessite Xdebug)
php vendor/bin/phpunit --testsuite Unit --coverage-html ResultatCouvertureCode
```

### Architecture des tests

```
tests/
├── Entity/
│   ├── AppartementTest.php      (10 tests)
│   ├── DisponibiliteTest.php    (6 tests)
│   ├── FraisTest.php            (5 tests)
│   ├── LocalisationTest.php     (5 tests)
│   ├── PaymentTest.php          (7 tests)
│   ├── ReservationTest.php      (15 tests)
│   ├── TarifTest.php            (3 tests)
│   ├── TemoignageTest.php       (7 tests)
│   └── UserTest.php             (12 tests)
├── Service/
│   ├── SanitizerServiceTest.php (27 tests — XSS, email, tel, troncature)
│   ├── DateServiceTest.php      (9 tests — jours ouvrés, weekends)
│   └── DistanceServiceTest.php  (6 tests — Haversine, symétrie)
├── Controller/
│   ├── PublicRoutesTest.php     (9 tests — smoke test pages publiques)
│   ├── AdminControllerTest.php  (9 tests — accès protégé + avec auth)
│   ├── ClientControllerTest.php (6 tests — espace client)
│   └── SecurityControllerTest.php (4 tests — login, register)
├── Security/
│   └── UserCheckerTest.php      (4 tests — compte actif/inactif)
├── EventSubscriber/
│   ├── SecurityHeadersSubscriberTest.php (2 tests)
│   └── FormSanitizerSubscriberTest.php   (1 test)
├── Twig/
│   └── CloudinaryExtensionTest.php (14 tests — presets, URLs, fallback)
└── bootstrap.php
```

---

## Déploiement Heroku

```bash
# 1. Connexion
heroku login

# 2. Vérifier les variables d'environnement
heroku run printenv

# 3. Mettre à jour une variable
heroku config:set MODE=PRODUCTION

# 4. Déployer
git push heroku main
```

---

## Cloudinary — Migration images

```bash
# 1. Dry-run pour voir les fichiers détectés
php bin/console app:cloudinary:sync-images --dry-run

# 2. Lancement de l'importation
php bin/console app:cloudinary:sync-images
```

---


## Sécurité

- **Authentification** : Symfony Security avec formulaire de login
- **Rôles** : `ROLE_USER` (client), `ROLE_ADMIN` (administrateur)
- **CSRF** : protection sur tous les formulaires et actions sensibles
- **Sanitisation globale** : `FormSanitizerSubscriber` + `SanitizerFormExtension` nettoient automatiquement toutes les entrées de formulaires Symfony (XSS, strip_tags, validation email/téléphone)
- **Mots de passe** : validation temps réel (8 caractères, majuscule, minuscule, 2 chiffres, caractère spécial), barre de force, toggle visibilité
- **Réinitialisation** : système complet avec token sécurisé par email
- **Headers HTTP** : `SecurityHeadersSubscriber` (X-Frame-Options, X-Content-Type-Options, Referrer-Policy)
- **AJAX admin** : vérification `X-Requested-With` sur toutes les routes API admin
- **Stripe** : signature webhook vérifiée, clés secrètes jamais exposées côté client


| Menace | Statut | Détail |
|--------|--------|--------|
| **Injection SQL** | ✅ | Doctrine QueryBuilder partout, aucune requête raw |
| **XSS** | ✅ | Twig auto-escape + FormSanitizerSubscriber global + SanitizerService |
| **Brute force** | ✅ | login_throttling + rate limiters sur check-email, inscription, forgot |
| **Sessions** | ✅ | cookie_httponly, cookie_secure, cookie_samesite |
| **Upload malveillant** | ✅ | Vérif MIME + limite 5 Mo |
| **Headers sécurité** | ✅ | SecurityHeadersSubscriber (X-Frame, X-Content-Type, Referrer-Policy) |
| **LFI / RFI** | ✅ | Aucun include/require dynamique |
| **Secrets** | ✅ | .env nettoyé, .env.local dans .gitignore |
| **Access control** | ✅ | /admin, /espace-client, /mon-profil protégés par RBAC |
| **Stripe webhook** | ✅ | Signature vérifiée |
| **Failles connues** | ✅ | Symfony 7.4 LTS à jour |

---


## Flux témoignages

1. **Séjour terminé** : la commande CRON `app:send-demande-avis` envoie un email au client avec un lien vers le formulaire d'avis
2. **Client connecté** : remplit le formulaire (note en étoiles + commentaire) lié à l'appartement et à la réservation
3. **Témoignage créé** : statut `en_attente`, invisible sur le site
4. **Admin** : depuis le dashboard témoignages, peut approuver, refuser, ou relancer un client par email
5. **Témoignage approuvé** : s'affiche sur la page d'accueil avec le badge de l'appartement concerné

---

## Flux factures PDF

1. **Réservation confirmée ou terminée** : le numéro de facture est auto-généré (`FAC-2026-0001`)
2. **Admin** : depuis la liste des réservations, cliquer sur l'icône PDF pour télécharger la facture
3. **Client** : depuis le détail de sa réservation dans l'espace client, cliquer sur "Télécharger la facture"
4. **Contenu de la facture** : en-tête Appart Hôtel Tricastin, infos client, détail prestation (prix/nuit × nuits), totaux (TTC, déjà payé, solde restant), historique des paiements Stripe
5. **Technologie** : DomPDF, template Twig dédié (`templates/pdf/facture.html.twig`)

---

## Dashboard analytique (Chart.js)

Le dashboard admin affiche 4 graphiques en plus des statistiques :

| Graphique | Type | Données |
|-----------|------|---------|
| **Revenus mensuels** | Barres | Paiements réussis groupés par mois |
| **Réservations par statut** | Doughnut | En attente / Confirmées / Annulées / Terminées |
| **Taux d'occupation** | Courbe | % jours occupés par mois (toutes réservations confirmées/terminées) |
| **Top appartements** | Barres horizontales | Top 5 par chiffre d'affaires |

Les données sont calculées côté serveur par `AnalyticsService` et transmises au template en JSON pour Chart.js 4.

---

## Export Comptable CSV — Guide d'intégration

### Structure du CSV généré

```
═══════════════════════════════════════════════════
BILAN COMPTABLE 2026 — Tous les appartements
═══════════════════════════════════════════════════

═══════════════════════════════════════════════════
Janvier 2026
═══════════════════════════════════════════════════
Client;N° Facture;Appartement;Date début;Date fin;Nb jours;Prix unitaire (€/nuit);Total hébergement (€)
Jean Dupont;FAC-2026-001;Studio Lavande;05/01/2026;12/01/2026;7;85,00;595,00
Marie Martin;FAC-2026-002;T2 Olivier;15/01/2026;22/01/2026;7;110,00;770,00

SOUS-TOTAL JANVIER;;;;;14 jours;Taux occupation : 22,6 %;1 365,00 €

...

═══════════════════════════════════════════════════
RÉCAPITULATIF TAUX D'OCCUPATION
═══════════════════════════════════════════════════
Mois;Jours occupés;Jours disponibles;Taux (%)
Janvier;14;31;45,2 %
...
TOTAL ANNUEL;180;365;49,3 %

═══════════════════════════════════════════════════
DÉTAIL DES FRAIS — 2026
═══════════════════════════════════════════════════
Type;Libellé;Appartement;Périodicité;Mois;Montant (€)
Hébergement du site;Hébergement site web + nom de domaine;Global;Annuel;—;120,00
...
TOTAL FRAIS ANNUELS;;;;;1 350,00 €

═══════════════════════════════════════════════════
BILAN FINANCIER — 2026
═══════════════════════════════════════════════════
Total revenus hébergement;18 500,00 €
Total frais annuels;1 350,00 €

RÉSULTAT NET;17 150,00 €
```

### Routes comptabilité

| Route                              | Méthode | Description                    |
|------------------------------------|---------|--------------------------------|
| `admin_comptabilite`               | GET     | Dashboard comptabilité         |
| `admin_comptabilite_export_csv`    | GET     | Téléchargement du CSV          |
| `admin_frais_new`                  | GET/POST| Ajout d'un frais               |
| `admin_frais_edit`                 | GET/POST| Modification d'un frais        |
| `admin_frais_delete`               | POST    | Suppression d'un frais         |

### Notes techniques CSV
- **Séparateur** : point-virgule (`;`) — compatible Excel FR nativement
- **Encodage** : UTF-8 avec BOM — accents corrects dans Excel
- **Frais mensuels** : comptés ×12 dans le total annuel automatiquement
- **Frais globaux** : `appartement = null` — apparaissent dans tous les exports
- **Taux d'occupation** : calculé sur le nombre de jours calendaires du mois

---

## Résultat de l'analyse des failles de sécurité 

| Menace | Statut | Détail |
|--------|--------|--------|
| **Injection SQL** | ✅ | Doctrine QueryBuilder partout, aucune requête raw |
| **XSS** | ✅ | Twig auto-escape + FormSanitizerSubscriber global + SanitizerService |
| **Brute force** | ✅ | login_throttling + rate limiters sur check-email, inscription, forgot |
| **Sessions** | ✅ | cookie_httponly, cookie_secure, cookie_samesite |
| **Upload malveillant** | ✅ | Vérif MIME + limite 5 Mo |
| **Headers sécurité** | ✅ | SecurityHeadersSubscriber (X-Frame, X-Content-Type, Referrer-Policy) |
| **LFI / RFI** | ✅ | Aucun include/require dynamique |
| **Secrets** | ✅ | .env nettoyé, .env.local dans .gitignore |
| **Access control** | ✅ | /admin, /espace-client, /mon-profil protégés par RBAC |
| **Stripe webhook** | ✅ | Signature vérifiée |
| **Failles connues** | ✅ | Symfony 7.4 LTS à jour |

---
## gestion de démarrage Docker

## Prérequis

Un seul logiciel à installer : **Docker Desktop**

  *   Téléchargement : https://www.docker.com/products/docker-desktop/
  *   Version : Docker Desktop pour Windows (choisir la version Intel/AMD si vous pas sur une puce ARM)

Après l'installation, lancez Docker Desktop et attendez que l'icône dans la barre des tâches soit **verte** (Running).

---

Remplissez les valeurs marquées `<À_REMPLIR>` :

| Variable | Où la trouver |
|---|---|
| `APP_SECRET` | N'importe quelle chaîne de 32 caractères aléatoires |
| `MAILER_DSN` | Votre interface Brevo → SMTP & API → Clés SMTP |
| `CLOUDINARY_URL` | Votre tableau de bord Cloudinary → Dashboard |
| `STRIPE_PUBLIC_KEY` | Votre compte Stripe → Développeurs → Clés API |
| `STRIPE_SECRET_KEY` | Idem |
| `STRIPE_WEBHOOK_SECRET` | Stripe → Développeurs → Webhooks |

### Étape 2 – Ouvrir un terminal dans le dossier

Faites un **clic droit** sur le dossier du projet → **Ouvrir dans le terminal**
(ou tapez `cmd` dans la barre d'adresse de l'Explorateur Windows)

### Étape 3 – Lancer l'application

```
docker compose up --build
```

Le premier démarrage prend **5 à 10 minutes** (téléchargement et compilation).
Les démarrages suivants prennent **moins d'une minute**.

Quand vous voyez `Application disponible sur http://localhost`, ouvrez votre navigateur :

→ **http://localhost**

---

## Commandes utiles

| Action | Commande |
|---|---|
| Démarrer | `docker compose up -d` |
| Arrêter | `docker compose down` |
| Voir les logs | `docker compose logs -f app` |
| Redémarrer | `docker compose restart app` |
| Accéder au terminal PHP | `docker compose exec app bash` |

---

## En cas de problème

**La page ne s'affiche pas**
→ Vérifiez que Docker Desktop est bien lancé (icône verte dans la barre des tâches)
→ Attendez 30 secondes supplémentaires après le message "Application disponible"

**Erreur "port 80 already in use"**
→ Un autre logiciel utilise le port 80 (IIS, WAMP, etc.)
→ Arrêtez-le ou modifiez dans `docker-compose.yml` la ligne `"80:80"` en `"8080:80"`
→ Accédez ensuite via http://localhost:8080

**Perte de données**
→ Les données sont stockées dans un volume Docker nommé `tricastin_db_data`
→ Elles persistent entre les redémarrages
→ Pour une sauvegarde, contactez votre prestataire

---

## Sauvegarde de la base de données

Pour exporter la base :
```
docker compose exec db mysqldump -u tricastin -pTricastinPass123! appart_hotel_tricastin > sauvegarde.sql
```

Pour restaurer :
```
docker compose exec -T db mysql -u tricastin -pTricastinPass123! appart_hotel_tricastin < sauvegarde.sql
```

Remplissez les valeurs marquées `<À_REMPLIR>` :

| Variable | Où la trouver |
|---|---|
| `APP_SECRET` | N'importe quelle chaîne de 32 caractères aléatoires |
| `MAILER_DSN` | Votre interface Brevo → SMTP & API → Clés SMTP |
| `CLOUDINARY_URL` | Votre tableau de bord Cloudinary → Dashboard |
| `STRIPE_PUBLIC_KEY` | Votre compte Stripe → Développeurs → Clés API |
| `STRIPE_SECRET_KEY` | Idem |
| `STRIPE_WEBHOOK_SECRET` | Stripe → Développeurs → Webhooks |

### Étape 2 – Ouvrir un terminal dans le dossier

Faites un **clic droit** sur le dossier du projet → **Ouvrir dans le terminal**
(ou tapez `cmd` dans la barre d'adresse de l'Explorateur Windows)

### Étape 3 – Lancer l'application

```
docker compose up --build
```

Le premier démarrage prend **5 à 10 minutes** (téléchargement et compilation).
Les démarrages suivants prennent **moins d'une minute**.

Quand vous voyez `Application disponible sur http://localhost`, ouvrez votre navigateur :

→ **http://localhost**

---

## Commandes utiles

| Action | Commande |
|---|---|
| Démarrer | `docker compose up -d` |
| Arrêter | `docker compose down` |
| Voir les logs | `docker compose logs -f app` |
| Redémarrer | `docker compose restart app` |
| Accéder au terminal PHP | `docker compose exec app bash` |

---

## En cas de problème

**La page ne s'affiche pas**
→ Vérifiez que Docker Desktop est bien lancé (icône verte dans la barre des tâches)
→ Attendez 30 secondes supplémentaires après le message "Application disponible"

**Erreur "port 80 already in use"**
→ Un autre logiciel utilise le port 80 (IIS, WAMP, etc.)
→ Arrêtez-le ou modifiez dans `docker-compose.yml` la ligne `"80:80"` en `"8080:80"`
→ Accédez ensuite via http://localhost:8080

**Perte de données**
→ Les données sont stockées dans un volume Docker nommé `tricastin_db_data`
→ Elles persistent entre les redémarrages
→ Pour une sauvegarde, contactez votre prestataire

---

## Sauvegarde de la base de données

Pour exporter la base :
```
docker compose exec db mysqldump -u tricastin -pTricastinPass123! appart_hotel_tricastin > sauvegarde.sql
```

Pour restaurer :
```
docker compose exec -T db mysql -u tricastin -pTricastinPass123! appart_hotel_tricastin < sauvegarde.sql
```

Remplissez les valeurs marquées `<À_REMPLIR>` :

| Variable | Où la trouver |
|---|---|
| `APP_SECRET` | N'importe quelle chaîne de 32 caractères aléatoires |
| `MAILER_DSN` | Votre interface Brevo → SMTP & API → Clés SMTP |
| `CLOUDINARY_URL` | Votre tableau de bord Cloudinary → Dashboard |
| `STRIPE_PUBLIC_KEY` | Votre compte Stripe → Développeurs → Clés API |
| `STRIPE_SECRET_KEY` | Idem |
| `STRIPE_WEBHOOK_SECRET` | Stripe → Développeurs → Webhooks |

### Étape 2 – Ouvrir un terminal dans le dossier

Faites un **clic droit** sur le dossier du projet → **Ouvrir dans le terminal**
(ou tapez `cmd` dans la barre d'adresse de l'Explorateur Windows)

### Étape 3 – Lancer l'application

```
docker compose up --build
```

Le premier démarrage prend **5 à 10 minutes** (téléchargement et compilation).
Les démarrages suivants prennent **moins d'une minute**.

Quand vous voyez `Application disponible sur http://localhost`, ouvrez votre navigateur :

→ **http://localhost**

---

## Commandes utiles

| Action | Commande |
|---|---|
| Démarrer | `docker compose up -d` |
| Arrêter | `docker compose down` |
| Voir les logs | `docker compose logs -f app` |
| Redémarrer | `docker compose restart app` |
| Accéder au terminal PHP | `docker compose exec app bash` |

---

## En cas de problème

**La page ne s'affiche pas**
→ Vérifiez que Docker Desktop est bien lancé (icône verte dans la barre des tâches)
→ Attendez 30 secondes supplémentaires après le message "Application disponible"

**Erreur "port 80 already in use"**
→ Un autre logiciel utilise le port 80 (IIS, WAMP, etc.)
→ Arrêtez-le ou modifiez dans `docker-compose.yml` la ligne `"80:80"` en `"8080:80"`
→ Accédez ensuite via http://localhost:8080

**Perte de données**
→ Les données sont stockées dans un volume Docker nommé `tricastin_db_data`
→ Elles persistent entre les redémarrages
→ Pour une sauvegarde, contactez votre prestataire

---

## Sauvegarde de la base de données

Pour exporter la base :
```
docker compose exec db mysqldump -u tricastin -pTricastinPass123! appart_hotel_tricastin > sauvegarde.sql
```

Pour restaurer :
```
docker compose exec -T db mysql -u tricastin -pTricastinPass123! appart_hotel_tricastin < sauvegarde.sql
```

Remplissez les valeurs marquées `<À_REMPLIR>` :

| Variable | Où la trouver |
|---|---|
| `APP_SECRET` | N'importe quelle chaîne de 32 caractères aléatoires |
| `MAILER_DSN` | Votre interface Brevo → SMTP & API → Clés SMTP |
| `CLOUDINARY_URL` | Votre tableau de bord Cloudinary → Dashboard |
| `STRIPE_PUBLIC_KEY` | Votre compte Stripe → Développeurs → Clés API |
| `STRIPE_SECRET_KEY` | Idem |
| `STRIPE_WEBHOOK_SECRET` | Stripe → Développeurs → Webhooks |

### Étape 2 – Ouvrir un terminal dans le dossier

Faites un **clic droit** sur le dossier du projet → **Ouvrir dans le terminal**
(ou tapez `cmd` dans la barre d'adresse de l'Explorateur Windows)

### Étape 3 – Lancer l'application

```
docker compose up --build
```

Le premier démarrage prend **5 à 10 minutes** (téléchargement et compilation).
Les démarrages suivants prennent **moins d'une minute**.

Quand vous voyez `Application disponible sur http://localhost`, ouvrez votre navigateur :

→ **http://localhost**

---

## Commandes utiles

| Action | Commande |
|---|---|
| Démarrer | `docker compose up -d` |
| Arrêter | `docker compose down` |
| Voir les logs | `docker compose logs -f app` |
| Redémarrer | `docker compose restart app` |
| Accéder au terminal PHP | `docker compose exec app bash` |

---

## En cas de problème

**La page ne s'affiche pas**
→ Vérifiez que Docker Desktop est bien lancé (icône verte dans la barre des tâches)
→ Attendez 30 secondes supplémentaires après le message "Application disponible"

**Erreur "port 80 already in use"**
→ Un autre logiciel utilise le port 80 (IIS, WAMP, etc.)
→ Arrêtez-le ou modifiez dans `docker-compose.yml` la ligne `"80:80"` en `"8080:80"`
→ Accédez ensuite via http://localhost:8080

**Perte de données**
→ Les données sont stockées dans un volume Docker nommé `tricastin_db_data`
→ Elles persistent entre les redémarrages
→ Pour une sauvegarde, contactez votre prestataire

---

## Sauvegarde de la base de données

Pour exporter la base :
```
docker compose exec db mysqldump -u tricastin -pTricastinPass123! appart_hotel_tricastin > sauvegarde.sql
```

Pour restaurer :
```
docker compose exec -T db mysql -u tricastin -pTricastinPass123! appart_hotel_tricastin < sauvegarde.sql
```

Remplissez les valeurs marquées `<À_REMPLIR>` :

| Variable | Où la trouver |
|---|---|
| `APP_SECRET` | N'importe quelle chaîne de 32 caractères aléatoires |
| `MAILER_DSN` | Votre interface Brevo → SMTP & API → Clés SMTP |
| `CLOUDINARY_URL` | Votre tableau de bord Cloudinary → Dashboard |
| `STRIPE_PUBLIC_KEY` | Votre compte Stripe → Développeurs → Clés API |
| `STRIPE_SECRET_KEY` | Idem |
| `STRIPE_WEBHOOK_SECRET` | Stripe → Développeurs → Webhooks |

### Étape 2 – Ouvrir un terminal dans le dossier

Faites un **clic droit** sur le dossier du projet → **Ouvrir dans le terminal**
(ou tapez `cmd` dans la barre d'adresse de l'Explorateur Windows)

### Étape 3 – Lancer l'application

```
docker compose up --build
```

Le premier démarrage prend **5 à 10 minutes** (téléchargement et compilation).
Les démarrages suivants prennent **moins d'une minute**.

Quand vous voyez `Application disponible sur http://localhost`, ouvrez votre navigateur :

→ **http://localhost**

---

## Commandes utiles

| Action | Commande |
|---|---|
| Démarrer | `docker compose up -d` |
| Arrêter | `docker compose down` |
| Voir les logs | `docker compose logs -f app` |
| Redémarrer | `docker compose restart app` |
| Accéder au terminal PHP | `docker compose exec app bash` |

---

## En cas de problème

**La page ne s'affiche pas**
→ Vérifiez que Docker Desktop est bien lancé (icône verte dans la barre des tâches)
→ Attendez 30 secondes supplémentaires après le message "Application disponible"

**Erreur "port 80 already in use"**
→ Un autre logiciel utilise le port 80 (IIS, WAMP, etc.)
→ Arrêtez-le ou modifiez dans `docker-compose.yml` la ligne `"80:80"` en `"8080:80"`
→ Accédez ensuite via http://localhost:8080

**Perte de données**
→ Les données sont stockées dans un volume Docker nommé `tricastin_db_data`
→ Elles persistent entre les redémarrages
→ Pour une sauvegarde, contactez votre prestataire

---

## Sauvegarde de la base de données

Pour exporter la base :
```
docker compose exec db mysqldump -u tricastin -pTricastinPass123! appart_hotel_tricastin > sauvegarde.sql
```

Pour restaurer :
```
docker compose exec -T db mysql -u tricastin -pTricastinPass123! appart_hotel_tricastin < sauvegarde.sql
```

## Démarrage en 3 étapes

### Étape 1 – Configurer vos clés

Ouvrez le fichier `.env.docker`

# 1. Vérifie que le fichier existe bien

## gestion des commande est exemple 

# 1. Vérifie que le fichier existe bien
dir src\Command\CleanDisponibilitesCommand.php

# 2. Vide le cache d'abord
php bin/console cache:clear

# 3. Vérifie s'il y a une erreur de syntaxe
php -l src\Command\CleanDisponibilitesCommand.php

# 4. Reliste les commandes
php bin/console list app


Remplissez les valeurs marquées `<À_REMPLIR>` :

| Variable | Où la trouver |
|---|---|
| `APP_SECRET` | N'importe quelle chaîne de 32 caractères aléatoires |
| `MAILER_DSN` | Votre interface Brevo   *   SMTP & API   *   Clés SMTP |
| `CLOUDINARY_URL` | Votre tableau de bord Cloudinary   *   Dashboard |
| `STRIPE_PUBLIC_KEY` | Votre compte Stripe   *   Développeurs   *   Clés API |
| `STRIPE_SECRET_KEY` | Idem |
| `STRIPE_WEBHOOK_SECRET` | Stripe   *   Développeurs   *   Webhooks |

### Étape 2 – Ouvrir un terminal dans le dossier

Faites un **clic droit** sur le dossier du projet   *   **Ouvrir dans le terminal**
(ou tapez `cmd` dans la barre d'adresse de l'Explorateur Windows)

### Étape 3 – Lancer l'application

```
docker compose up --build
```

Le premier démarrage prend **5 à 10 minutes** (téléchargement et compilation).
Les démarrages suivants prennent **moins d'une minute**.

Quand vous voyez `Application disponible sur http://localhost`, ouvrez votre navigateur :

  *   **http://localhost**

---

## Commandes utiles

| Action | Commande |
|---|---|
| Démarrer | `docker compose up -d` |
| Arrêter | `docker compose down` |
| Voir les logs | `docker compose logs -f app` |
| Redémarrer | `docker compose restart app` |
| Accéder au terminal PHP | `docker compose exec app bash` |

---

## En cas de problème

**La page ne s'affiche pas**
  *   Vérifiez que Docker Desktop est bien lancé (icône verte dans la barre des tâches)
  *   Attendez 30 secondes supplémentaires après le message "Application disponible"

**Erreur "port 80 already in use"**
  *   Un autre logiciel utilise le port 80 (IIS, WAMP, etc.)
  *   Arrêtez-le ou modifiez dans `docker-compose.yml` la ligne `"80:80"` en `"8080:80"`
  *   Accédez ensuite via http://localhost:8080

**Perte de données**
  *   Les données sont stockées dans un volume Docker nommé `tricastin_db_data`
  *   Elles persistent entre les redémarrages
  *   Pour une sauvegarde, contactez votre prestataire

---

## Sauvegarde de la base de données

Pour exporter la base :
```
docker compose exec db mysqldump -u tricastin -pTricastinPass123! appart_hotel_tricastin > sauvegarde.sql
```

Pour restaurer :
```
docker compose exec -T db mysql -u tricastin -pTricastinPass123! appart_hotel_tricastin < sauvegarde.sql
```

---

## Instalation et mise en place des tests unitaires

# 1. Instalation de PHPUnit
composer require --dev phpunit/phpunit
composer require --dev symfony/browser-kit symfony/css-selector

# 2. Lancement des tests

# Tous les tests unitaires
php vendor/bin/phpunit --testsuite Unit --coverage-html

# Tous les tests fonctionnels (nécessitent une BDD test)
php vendor/bin/phpunit --testsuite Functional --coverage-html

# Tout d'un coup
php vendor/bin/phpunit --testsuite Unit --coverage-html ResultatCouvertureCode

# Avec couverture de code (nécessite Xdebug)
php vendor/bin/phpunit --testsuite Unit --coverage-html ResultatCouvertureCode

pour generer un rapport html utiliser l'option --coverage-html

# 3. Architecture des tests

tests/
├── Entity/
│   ├── AppartementTest.php      (10 tests)
│   ├── DisponibiliteTest.php    (6 tests)
│   ├── FraisTest.php            (5 tests)
│   ├── LocalisationTest.php     (5 tests)
│   ├── PaymentTest.php          (7 tests)
│   ├── ReservationTest.php      (15 tests)
│   ├── TarifTest.php            (3 tests)
│   ├── TemoignageTest.php       (7 tests)
│   └── UserTest.php             (12 tests)
├── Service/
│   ├── SanitizerServiceTest.php (27 tests — XSS, email, tel, troncature)
│   ├── DateServiceTest.php      (9 tests — jours ouvrés, weekends)
│   └── DistanceServiceTest.php  (6 tests — Haversine, symétrie)
├── Controller/
│   ├── PublicRoutesTest.php     (9 tests — smoke test pages publiques)
│   ├── AdminControllerTest.php  (9 tests — accès protégé + avec auth)
│   ├── ClientControllerTest.php (6 tests — espace client)
│   └── SecurityControllerTest.php (4 tests — login, register)
├── Security/
│   └── UserCheckerTest.php      (4 tests — compte actif/inactif)
├── EventSubscriber/
│   ├── SecurityHeadersSubscriberTest.php (2 tests)
│   └── FormSanitizerSubscriberTest.php   (1 test)
├── Twig/
│   └── CloudinaryExtensionTest.php (14 tests — presets, URLs, fallback)
└── bootstrap.php           

## Déploiement sur de l'application sur Heroku

# 1. login sur heroku 
heroku login

# 2. Vérifier les variables d'environement sur heroku du projet ciblé
heroku run printenv

# 3. Mise à jour des variables d'environement sur heroku 
heroku config:set MODE=PRODUCTION

# 4. Déploiment de heroku
git push heroku main



## Stratégies Andrieu implémentées

| Chapitre | Stratégie | Implémentation |
|----------|-----------|----------------|
| Ch.3 | robots.txt crawl budget | `SitemapController::robots()` — bloque /admin/, /mon-espace/, bots scrapers |
| Ch.4 | Sitemap XML + HTML | `/sitemap.xml` (priorités différenciées) + `/plan-du-site` (maillage) |
| Ch.5 | **Title ≠ H1** | `SeoPage.titre` ≠ `SeoPage.h1` — principe fondamental |
| Ch.5 | Title 55-65 car. + mot-clé en tête | `SeoAuditService` + compteur admin |
| Ch.5 | Meta description 120-160 car. + CTA | Champ + compteur + audit |
| Ch.6 | Fil d'Ariane + BreadcrumbList | `SeoService::buildBreadcrumbList()` + `base.html.twig` |
| Ch.7 | Cocon sémantique | Entity `SeoCocon`, `SeoPage.cocon`, page pivot/satellites |
| Ch.7 | Mot-clé cible (1 par page) | `SeoPage.focusKeyword` + mots-clés LSI secondaires |
| Ch.8 | Canonical par page | `SeoPage.canonical` ou URL courante auto |
| Ch.9 | Performances | `preconnect`, `dns-prefetch` dans `base.html.twig` |
| Ch.10 | Données structurées | LodgingBusiness + WebSite/SearchAction, Apartment, BreadcrumbList, ContactPage, FAQPage, AggregateRating |
| Ch.10 | E-E-A-T | `schemaExtra` JSON (téléphone, sameAs, horaires) |
| Ch.11 | Open Graph + Twitter Card | `base.html.twig` — og:image 1200×630, og:type, og:locale |
| Ch.12 | Hreflang FR/EN + x-default | Génération auto ou manuelle, `base.html.twig` |

---

## Utilisation dans les templates enfants

### Surcharge SEO contextuelle (pages dynamiques)
Le controller passe `seoOverride` à la vue :
```php
// Dans un controller
$seoOverride = $seoService->buildForAppartement($appartement, $temoignages);
return $this->render('appartement/detail.html.twig', [
    'appartement' => $appartement,
    'seoOverride' => $seoOverride,  // ← transmis à base.html.twig
]);
```
`base.html.twig` appelle automatiquement `seo_resolve(seoOverride ?? {})`.

### Surcharge légère depuis un template Twig
```twig
{% extends 'base.html.twig' %}
{% set seoOverride = {
    titre: 'Mon titre custom',
    description: 'Ma description',
    robots: 'noindex, follow'
} %}
```

### Injection d'un schéma JSON-LD supplémentaire
```twig
{% block seo_extra %}
<script type="application/ld+json">
{{ seo_breadcrumb_list([
    {label: 'Accueil', url: url('app_home')},
    {label: 'Ma page', url: app.request.uri}
])|raw }}
</script>
{% endblock %}
```

---

## Configuration initiale recommandée (admin)

Accédez à `/admin/seo` et complétez :

1. **Cocons** (`/admin/seo/cocons`) :
   - Vérifiez que les 2 cocons sont bien créés
   - Assignez chaque page à son cocon

2. **Page d'accueil** (`app_home`) :
   - Schéma : `LodgingBusiness`
   - Renseignez `schemaExtra` avec le numéro de téléphone réel et les réseaux sociaux

3. **Appartements** : SEO généré automatiquement depuis la BDD — aucune config requise

4. **Images OG** :
   - Créez `public/images/og-default.jpg` (1200×630 px) pour le fallback
   - Renseignez une image par page dans le champ "Image Open Graph"

## Sauvegarde base de données

```bash
# Exporter la base (Docker)
docker compose exec db mysqldump -u tricastin -pTricastinPass123! appart_hotel_tricastin > sauvegarde.sql

# Restaurer la base (Docker)
docker compose exec -T db mysql -u tricastin -pTricastinPass123! appart_hotel_tricastin < sauvegarde.sql
```
