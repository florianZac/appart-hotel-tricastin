# Appart Hôtel Tricastin — Projet Symfony

Site vitrine et système de réservation pour un appart-hôtel en Drôme Provençale (Pierrelatte, Saint-Paul-Trois-Châteaux, Montélimar).  
Projet monolithique **Symfony 7.4 LTS** avec **Bootstrap 5** (front + back dans le même projet).

## Licence

Projet privé — Tous droits réservés. Projet utilisable que par un AIZAC toute reproduction est interdite 
**Auteur** : Florian Aizac
**Client** : Mickael Aizac

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
- **Comptabilité** : gestion des frais (hébergement site, nettoyage, réparations, assurance, taxe de séjour), export CSV mensuel complet pour le comptable avec taux d'occupation et bilan financier net
- **Factures PDF** : génération et téléchargement de factures pour chaque réservation
- **Upload images** : image principale + galerie via Cloudinary

### Système d'emails (10 templates)
- Bienvenue à l'inscription
- Confirmation de réservation
- Rappel J-3 avant arrivée (commande CRON)
- Annulation de réservation
- Confirmation de paiement Stripe
- Échéance de paiement
- Réinitialisation de mot de passe
- Notification admin (nouvelle réservation)
- Demande d'avis post-séjour (commande CRON)
- Contact (message reçu par l'admin)

### Commandes CRON
- `app:send-rappels-reservations` — Rappel J-3 avant arrivée (tous les jours à 09h)
- `app:send-demande-avis` — Demande d'avis aux clients dont le séjour est terminé (tous les jours à 10h)
- `app:create-admin` — Création d'un compte administrateur

---

## Stack technique

| Composant       | Technologie                          |
|-----------------|--------------------------------------|
| **Framework**   | Symfony 7.4 LTS                      |
| **PHP**         | 8.2+                                 |
| **Template**    | Twig                                 |
| **CSS**         | Bootstrap 5.3 + CSS custom           |
| **Icons**       | Bootstrap Icons                      |
| **Fonts**       | Cormorant Garamond + Montserrat      |
| **ORM**         | Doctrine ORM                         |
| **BDD**         | MySQL (dev) / PostgreSQL (prod)      |
| **JS**          | Vanilla JS + FullCalendar 6 + Chart.js 4 |
| **Mailer**      | Symfony Mailer (Brevo SMTP)          |
| **Paiement**    | Stripe (acompte + solde)             |
| **Images**      | Cloudinary (upload + CDN)            |
| **PDF**         | DomPDF (factures)                    |
| **Bundler**     | Webpack Encore                       |
| **i18n**        | Symfony Translation (FR/EN)          |

---

## Installation

### Prérequis
- **PHP 8.2+** avec extensions : `pdo_mysql`, `intl`, `mbstring`, `xml`
- **Composer** — pour Windows : https://getcomposer.org/Composer-Setup.exe
- **Symfony CLI** — `scoop update symfony-cli`
- **Node.js** (pour Webpack Encore)
- **Vérification** : `composer -v` et `symfony -v`

Versions testées (mon cas sur ma machine):
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

## Structure du projet

```
appart-hotel-tricastin/
├── config/
│   ├── packages/                          # Configuration des bundles Symfony
│   │   ├── doctrine.yaml                  # ORM Doctrine + connexion BDD
│   │   ├── security.yaml                  # Authentification, rôles, firewall, access_control
│   │   ├── stripe.yaml                    # Clés API Stripe (paiement)
│   │   ├── translation.yaml              # i18n FR/EN, locale par défaut
│   │   ├── webpack_encore.yaml           # Compilation des assets JS/CSS
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
│   │   ├── CreateAdminCommand.php         # Création d'un compte admin en CLI
│   │   ├── SendRappelsReservationsCommand.php  # CRON : rappel J-3 avant arrivée
│   │   ├── SendDemandeAvisCommand.php     # CRON : email demande d'avis post-séjour
│   │   └── CloudinaryMigrateCommand.php   # Migration des images locales vers Cloudinary
│   ├── Controller/
│   │   ├── HomeController.php             # Page d'accueil + mentions légales
│   │   ├── AppartementController.php      # Listing et détail des appartements
│   │   ├── ReservationController.php      # Formulaire de réservation public
│   │   ├── ContactController.php          # Formulaire de contact + envoi email
│   │   ├── SecurityController.php         # Login, logout, mot de passe oublié, reset
│   │   ├── RegistrationController.php     # Inscription + vérification email AJAX
│   │   ├── ClientController.php           # Espace client : dashboard, réservations, paiements
│   │   ├── TemoignageController.php       # Dépôt d'avis par le client après séjour
│   │   ├── PaymentController.php          # Paiement Stripe : acompte, solde, webhook
│   │   ├── CalendrierController.php       # API FullCalendar : disponibilités publiques
│   │   ├── FactureController.php          # Génération et téléchargement de factures PDF (admin + client)
│   │   ├── AdminController.php            # Dashboard admin analytique, gestion réservations, calendrier, uploads
│   │   ├── AdminTemoignageController.php  # Admin : approuver/refuser/relancer témoignages
│   │   ├── UserController.php             # Admin : gestion des utilisateurs (liste, édition, rôles)
│   │   ├── ComptabiliteController.php     # Gestion de la comptabilité du site + export CSV
│   │   └── LocaleController.php           # Switcher de langue FR/EN
│   ├── DataFixtures/
│   │   ├── LocalisationFixtures.php       # Données initiales : 3 villes (Pierrelatte, SPTC, Montélimar)
│   │   ├── AppartementFixtures.php        # Données initiales : appartements avec prix, équipements
│   │   ├── TemoignageFixtures.php         # Données initiales : 6 avis approuvés liés aux appartements
│   │   ├── UserFixtures.php               # Données initiales : comptes utilisateurs de test
│   │   └── FraisFixtures.php              # Données initiales : données de test pour la gestion comptable
│   ├── Entity/
│   │   ├── Appartement.php                # Appartement : nom, type, surface, capacité, prix, galerie, localisation
│   │   ├── Disponibilite.php              # Créneaux de disponibilité FullCalendar (disponible/réservé/nettoyage/bloqué)
│   │   ├── Localisation.php               # Ville/adresse regroupant plusieurs appartements
│   │   ├── Payment.php                    # Paiement Stripe : montant, type (acompte/solde), statut
│   │   ├── Reservation.php                # Réservation : dates, client, appartement, statut, paiement, numéro facture
│   │   ├── Temoignage.php                 # Avis client : note, commentaire, statut, appartement
│   │   ├── User.php                       # Utilisateur : nom, prénom, email, adresse, rôles, mot de passe
│   │   └── Frais.php                      # Frais : type, montant, périodicité, appartement (comptabilité)
│   ├── EventSubscriber/
│   │   ├── LocaleSubscriber.php           # Gestion de la langue en session
│   │   ├── SecurityHeadersSubscriber.php  # Headers HTTP sécurité (X-Frame-Options, etc.)
│   │   └── FormSanitizerSubscriber.php    # Sanitisation automatique de tous les formulaires via SanitizerService
│   ├── Form/
│   │   ├── ContactType.php                # Formulaire de contact (sujet, email, message)
│   │   ├── ProfileType.php                # Formulaire édition profil client
│   │   ├── RegistrationType.php           # Formulaire inscription (prenom, nom, email, adresse, mdp)
│   │   ├── ReservationType.php            # Formulaire réservation (appartement, dates, personnes)
│   │   ├── TemoignageType.php             # Formulaire avis (note en étoiles + commentaire)
│   │   ├── UserType.php                   # Formulaire admin édition utilisateur
│   │   ├── ExportComptabiliteType.php     # Formulaire admin export comptable (année + appartement)
│   │   ├── FraisType.php                  # Formulaire CRUD des frais comptables
│   │   └── Extension/
│   │       └── SanitizerFormExtension.php # Extension globale : branche le sanitizer sur tous les formulaires
│   ├── Repository/
│   │   ├── AppartementRepository.php      # Requêtes : findAllActifs()
│   │   ├── DisponibiliteRepository.php    # Requêtes : findByAppartementAndPeriode(), isDisponible()
│   │   ├── LocalisationRepository.php     # Requêtes : findAllWithAppartements()
│   │   ├── PaymentRepository.php          # Requêtes : findRecents(), findEnRetard(), getRevenusParType()
│   │   ├── ReservationRepository.php      # Requêtes : findRecentes(), findByUser(), findConfirmeesParAppartement()
│   │   ├── TemoignageRepository.php       # Requêtes : findActifs(), findByStatut(), findByUserAndReservation()
│   │   ├── UserRepository.php             # Requêtes : utilisateurs
│   │   └── FraisRepository.php            # Requêtes : frais par année, totaux par mois
│   ├── Service/
│   │   ├── AnalyticsService.php           # Données analytiques Chart.js : revenus, occupation, top appartements
│   │   ├── CloudinaryService.php          # Upload/suppression d'images sur Cloudinary CDN
│   │   ├── ComptabiliteExporter.php       # Logique métier + génération CSV comptable
│   │   ├── DateService.php                # Utilitaires de manipulation de dates
│   │   ├── DistanceService.php            # Calcul de distance entre deux points GPS
│   │   ├── FactureService.php             # Génération de factures PDF via DomPDF
│   │   ├── LogService.php                 # Service de journalisation applicative
│   │   ├── MailerService.php              # 10 méthodes d'envoi d'emails
│   │   ├── NominatimService.php           # Géocodage d'adresses via API Nominatim
│   │   ├── OsrmService.php                # Calcul d'itinéraire via API OSRM
│   │   ├── SanitizerService.php           # Nettoyage/sanitisation des entrées utilisateur (XSS)
│   │   └── StripeService.php              # Création de sessions Stripe, gestion webhook paiement
│   ├── Twig/
│   │   └── CloudinaryExtension.php        # Filtre Twig pour transformer les URLs Cloudinary
│   └── Kernel.php                         # Kernel Symfony
├── templates/
│   ├── admin/
│   │   ├── base.html.twig                 # Layout admin : sidebar navigation + flash messages
│   │   ├── dashboard.html.twig            # Dashboard analytique : stats + 4 graphiques Chart.js
│   │   ├── reservations.html.twig         # Liste des réservations avec actions + bouton facture PDF
│   │   ├── temoignages.html.twig          # Gestion des avis : en attente, approuvés, refusés, relance
│   │   ├── calendrier.html.twig           # FullCalendar admin : 4 statuts (disponible/réservé/nettoyage/bloqué)
│   │   ├── users.html.twig                # Liste des utilisateurs avec édition des rôles
│   │   ├── user_edit.html.twig            # Édition d'un utilisateur
│   │   ├── profile_edit.html.twig         # Édition du profil admin
│   │   ├── paiements.html.twig            # Suivi des paiements, revenus, retards
│   │   └── comptabilite/
│   │       ├── index.html.twig            # Dashboard comptabilité + export CSV
│   │       └── frais_form.html.twig       # Formulaire ajout/modif frais
│   ├── client/
│   │   ├── dashboard.html.twig            # Espace client : stats, réservations/paiements récents
│   │   ├── reservations.html.twig         # Historique complet des réservations du client
│   │   ├── reservation_detail.html.twig   # Détail réservation + paiements + bouton facture PDF
│   │   ├── paiements.html.twig            # Historique des paiements du client
│   │   └── profile_edit.html.twig         # Édition du profil
│   ├── pdf/
│   │   └── facture.html.twig              # Template HTML de la facture PDF (DomPDF)
│   ├── temoignage/
│   │   └── new.html.twig                  # Formulaire dépôt d'avis : étoiles cliquables
│   ├── emails/                            # 10 templates emails (héritent de base_email.html.twig)
│   │   ├── base_email.html.twig           # Layout email : header bleu nuit/doré, footer
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
│   ├── appartement/                       # Listing et détail des appartements
│   ├── contact/                           # Page contact
│   ├── home/                              # Accueil
│   ├── reservation/                       # Formulaire de réservation public
│   ├── payment/                           # Pages Stripe (succès, annulation)
│   ├── bundles/TwigBundle/Exception/
│   │   └── error404.html.twig             # Page 404 personnalisée
│   └── base.html.twig                     # Layout principal
├── translations/
│   ├── messages.fr.yaml                   # Traductions françaises
│   └── messages.en.yaml                   # Traductions anglaises
├── .env                                   # Variables d'environnement (template)
├── composer.json                          # Dépendances PHP
├── package.json                           # Dépendances JS
├── webpack.config.js                      # Configuration Webpack Encore
└── README.md                              # Documentation du projet
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

#### MySQL (développement — WAMP)
```env
DATABASE_URL="mysql://root:@127.0.0.1:3306/appart_hotel_tricastin?serverVersion=8.0"
```

#### PostgreSQL (production)
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

---

## Cloudinary — Migration des images

```bash
# 1. Dry-run pour voir les fichiers détectés
php bin/console app:cloudinary:sync-images --dry-run

# 2. Lancement de l'importation
php bin/console app:cloudinary:sync-images
```

---

## Commandes CRON

Sur Windows, utiliser le **Planificateur de tâches** (`taskschd.msc`) ou lancer manuellement :

```bash
# Rappels J-3 avant arrivée (quotidien 09h)
php bin/console app:send-rappels-reservations

# Demande d'avis post-séjour (quotidien 10h)
php bin/console app:send-demande-avis
```

Sur Linux (production) :
```cron
0 9  * * * cd /chemin/projet && php bin/console app:send-rappels-reservations
0 10 * * * cd /chemin/projet && php bin/console app:send-demande-avis
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

---

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

## gestion des commande est exemple 

# 1. Vérifie que le fichier existe bien
dir src\Command\CleanDisponibilitesCommand.php

# 2. Vide le cache d'abord
php bin/console cache:clear

# 3. Vérifie s'il y a une erreur de syntaxe
php -l src\Command\CleanDisponibilitesCommand.php

# 4. Reliste les commandes
php bin/console list app

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