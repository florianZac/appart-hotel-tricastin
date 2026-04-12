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
- **Gestion du profil** : modification des informations personnelles, désactivation de compte
- **Dépôt d'avis** : formulaire avec notation en étoiles après un séjour terminé (soumis à validation admin)

### Back-office (admin)
- **Dashboard** : statistiques globales (appartements, réservations, témoignages publiés + en attente)
- **Gestion des réservations** : liste complète avec changement de statut (confirmer / annuler / terminer) + envoi d'emails automatiques
- **Gestion du calendrier** : ajout/suppression de disponibilités par appartement via FullCalendar
- **Gestion des utilisateurs** : liste, édition des rôles
- **Gestion des témoignages** : dashboard complet (en attente / approuvés / refusés), approbation, refus, relance email ciblée, suivi des séjours sans avis
- **Gestion des paiements** : liste, suivi des paiements en retard, revenus par type
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
| **JS**          | Vanilla JS + FullCalendar 6          |
| **Mailer**      | Symfony Mailer (Brevo SMTP)          |
| **Paiement**    | Stripe (acompte + solde)             |
| **Images**      | Cloudinary (upload + CDN)            |
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
│   │   ├── AdminController.php            # Dashboard admin, gestion réservations, calendrier, uploads
│   │   ├── AdminTemoignageController.php  # Admin : approuver/refuser/relancer témoignages
│   │   ├── UserController.php             # Admin : gestion des utilisateurs (liste, édition, rôles)
│   │   └── LocaleController.php           # Switcher de langue FR/EN
│   ├── DataFixtures/
│   │   ├── LocalisationFixtures.php       # Données initiales : 3 villes (Pierrelatte, SPTC, Montélimar)
│   │   ├── AppartementFixtures.php        # Données initiales : appartements avec prix, équipements
│   │   ├── TemoignageFixtures.php         # Données initiales : 6 avis approuvés liés aux appartements
│   │   └── UserFixtures.php               # Données initiales : comptes utilisateurs de test
│   ├── Entity/
│   │   ├── Appartement.php                # Appartement : nom, type, surface, capacité, prix, galerie, localisation
│   │   ├── Disponibilite.php              # Créneaux de disponibilité FullCalendar (disponible/réservé/ménage/bloqué)
│   │   ├── Localisation.php               # Ville/adresse regroupant plusieurs appartements
│   │   ├── Payment.php                    # Paiement Stripe : montant, type (acompte/solde), statut
│   │   ├── Reservation.php               # Réservation : dates, client, appartement, statut, paiement, flag avis
│   │   ├── Temoignage.php                # Avis client : note, commentaire, statut (en_attente/approuvé/refusé), appartement
│   │   └── User.php                       # Utilisateur : nom, prénom, email, adresse, rôles, mot de passe
│   ├── EventSubscriber/                   # Subscribers Symfony (événements Kernel)
│   ├── Form/
│   │   ├── ContactType.php                # Formulaire de contact (sujet, email, message)
│   │   ├── ProfileType.php                # Formulaire édition profil client
│   │   ├── RegistrationType.php           # Formulaire inscription (prenom, nom, email, adresse, mdp)
│   │   ├── ReservationType.php            # Formulaire réservation (appartement, dates, personnes)
│   │   ├── TemoignageType.php             # Formulaire avis (note en étoiles + commentaire)
│   │   └── UserType.php                   # Formulaire admin édition utilisateur
│   ├── Repository/
│   │   ├── AppartementRepository.php      # Requêtes : findAllActifs()
│   │   ├── DisponibiliteRepository.php    # Requêtes : findByAppartementAndPeriode()
│   │   ├── LocalisationRepository.php     # Requêtes : findAllWithAppartements()
│   │   ├── PaymentRepository.php          # Requêtes : findRecents(), findEnRetard(), getRevenusParType()
│   │   ├── ReservationRepository.php      # Requêtes : findRecentes(), findByUser(), findSejoursTerminesSansDemandeAvis()
│   │   ├── TemoignageRepository.php       # Requêtes : findActifs(), findByStatut(), findByUserAndReservation()
│   │   └── UserRepository.php             # Requêtes utilisateurs
│   ├── Service/
│   │   ├── CloudinaryService.php          # Upload/suppression d'images sur Cloudinary CDN
│   │   ├── DateService.php                # Utilitaires de manipulation de dates
│   │   ├── DistanceService.php            # Calcul de distance entre deux points GPS
│   │   ├── LogService.php                 # Service de journalisation applicative
│   │   ├── MailerService.php              # 10 méthodes d'envoi d'emails (réservation, avis, paiement, etc.)
│   │   ├── NominatimService.php           # Géocodage d'adresses via API Nominatim (OpenStreetMap)
│   │   ├── OsrmService.php                # Calcul d'itinéraire via API OSRM
│   │   ├── SanitizerService.php           # Nettoyage/sanitization des entrées utilisateur
│   │   └── StripeService.php              # Création de sessions Stripe, gestion webhook paiement
│   ├── Twig/
│   │   └── CloudinaryExtension.php        # Filtre Twig pour transformer les URLs Cloudinary (resize, format)
│   └── Kernel.php                         # Kernel Symfony (bootstrap de l'application)
├── templates/
│   ├── admin/
│   │   ├── base.html.twig                 # Layout admin : sidebar navigation + flash messages
│   │   ├── dashboard.html.twig            # Dashboard : stats, dernières réservations, badge avis en attente
│   │   ├── reservations.html.twig         # Liste des réservations avec actions (confirmer/annuler)
│   │   ├── temoignages.html.twig          # Gestion des avis : en attente, approuvés, refusés, relance
│   │   ├── calendrier.html.twig           # FullCalendar admin : gestion des disponibilités
│   │   ├── users.html.twig                # Liste des utilisateurs avec édition des rôles
│   │   ├── paiements.html.twig            # Suivi des paiements, revenus, retards
│   │   └── ...
│   ├── client/
│   │   ├── dashboard.html.twig            # Espace client : stats, dernières réservations/paiements, liens rapides
│   │   ├── reservations.html.twig         # Historique complet des réservations du client
│   │   ├── reservation_detail.html.twig   # Détail d'une réservation avec paiements associés
│   │   ├── paiements.html.twig            # Historique des paiements du client
│   │   └── profile_edit.html.twig         # Édition du profil (infos personnelles)
│   ├── temoignage/
│   │   └── new.html.twig                  # Formulaire dépôt d'avis : étoiles cliquables + compteur caractères
│   ├── emails/                            # 10 templates emails (héritent de base_email.html.twig)
│   │   ├── base_email.html.twig           # Layout email : header bleu nuit/doré, footer, styles inline
│   │   ├── bienvenue.html.twig            # Email de bienvenue à l'inscription
│   │   ├── confirmation_reservation.html.twig  # Confirmation de réservation au client
│   │   ├── rappel_reservation.html.twig   # Rappel J-3 avant arrivée
│   │   ├── annulation_reservation.html.twig    # Notification d'annulation
│   │   ├── confirmation_paiement.html.twig     # Confirmation de paiement Stripe
│   │   ├── echeance_paiement.html.twig    # Rappel d'échéance de paiement
│   │   ├── reset_password.html.twig       # Lien de réinitialisation du mot de passe
│   │   ├── admin_nouvelle_reservation.html.twig # Notification admin : nouvelle réservation reçue
│   │   └── demande_avis.html.twig         # Demande d'avis post-séjour avec bouton CTA
│   ├── security/
│   │   ├── login.html.twig                # Page de connexion avec validation JS
│   │   ├── register.html.twig             # Inscription : validation mdp temps réel, vérif email AJAX, toggle œil
│   │   ├── forgot_password.html.twig      # Formulaire mot de passe oublié
│   │   └── reset_password.html.twig       # Nouveau mot de passe avec barre de force + règles
│   ├── appartement/                       # Listing et détail des appartements
│   ├── contact/                           # Page contact : formulaire + carte + coordonnées
│   ├── home/                              # Accueil : hero, localisations, carrousel témoignages avec badge appart
│   ├── reservation/                       # Formulaire de réservation public
│   ├── payment/                           # Pages de paiement Stripe (succès, annulation)
│   ├── user/                              # Templates admin édition utilisateur
│   ├── bundles/TwigBundle/Exception/
│   │   └── error404.html.twig             # Page 404 personnalisée
│   └── base.html.twig                     # Layout principal : navbar, footer, meta, CDN Bootstrap/Icons
├── translations/
│   ├── messages.fr.yaml                   # Traductions françaises (labels, messages, admin)
│   └── messages.en.yaml                   # Traductions anglaises
├── .env                                   # Variables d'environnement (template)
├── composer.json                          # Dépendances PHP (Symfony, Doctrine, Stripe, etc.)
├── package.json                           # Dépendances JS (Webpack Encore)
├── webpack.config.js                      # Configuration Webpack Encore
└── README.md                              # Documentation du projet
```

---

## Design

Le design s'inspire du site [appart-hotel-tricastin.com](https://appart-hotel-tricastin.com/) avec :
- **Palette** : doré (`#c8a962`), bleu nuit (`#1a2744`), crème (`#fdf8f0`)
- **Typographie** : Cormorant Garamond (titres) + Montserrat (corps)
- **Composants** : hero plein écran, cards appartements avec hover, carrousel témoignages, formulaires stylisés, dashboard admin sidebar
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
- **Mots de passe** : validation temps réel (8 caractères, majuscule, minuscule, 2 chiffres, caractère spécial), barre de force, toggle visibilité
- **Réinitialisation** : système complet avec token sécurisé par email

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

