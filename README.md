#  Appart Hôtel Tricastin — Projet Symfony

Site vitrine et système de réservation pour un appart-hôtel à Montélimar.  
Projet monolithique **Symfony 7** avec **Bootstrap 5** (front + back dans le même projet).

---

## Fonctionnalités

### Front-office (public)
- **Page d'accueil** : hero, présentation, carrousel d'appartements, avis clients, services
- **Les Appartements** : listing avec détails, équipements, galerie photos
- **Réservation** : formulaire complet avec choix d'appartement, dates, nombre de personnes
- **Contact** : formulaire d'envoi de message + carte Google Maps + coordonnées
- **Page 404** personnalisée
- **Design responsive** : mobile-first avec Bootstrap 5

### Back-office (admin)
- **Dashboard** : statistiques (appartements, réservations, témoignages)
- **Gestion des réservations** : liste complète avec changement de statut (confirmer/annuler)

---

## Stack technique

| Composant      | Technologie                |
|----------------|----------------------------|
| **Framework**  | Symfony 7.1                |
| **Template**   | Twig                       |
| **CSS**        | Bootstrap 5.3 + CSS custom |
| **Icons**      | Bootstrap Icons            |
| **Fonts**      | Cormorant Garamond + Montserrat |
| **ORM**        | Doctrine ORM               |
| **BDD**        | SQLite (dev) / MySQL / PostgreSQL |
| **JS**         | Vanilla JS (scroll reveal, navbar) |
| **Mailer**     | Symfony Mailer             |

---

## Installation

### Prérequis
- **PHP 8.2+** avec extensions : `pdo_sqlite`, `intl`, `mbstring`, `xml`
- **Composer** (gestionnaire de dépendances PHP) pour windows -> https://getcomposer.org/Composer-Setup.exe
- **Symfony CLI** scoop update symfony-cli
- **Verification** composer -v et symfony -v
Pour mon cas :
Composer version 2.9.5 2026-01-29 11:40:53
PHP version 8.4.15 (D:\wamp64\bin\php\php8.4.15\php.exe)
Symfony CLI version 5.16.1 (c) 2021-2026 Fabien Potencier (2025-11-25T07:30:20Z - stable)


### Étapes

```bash
# 1. Cloner le projet
git clone <votre-repo> appart-hotel-tricastin
cd appart-hotel-tricastin

# 2. Installer les dépendances
composer install

# 3. Configurer l'environnement
cp .env .env.local
# Éditer .env.local pour configurer DATABASE_URL et MAILER_DSN

# 4. Créer la base de données et les tables
php bin/console doctrine:database:create
php bin/console doctrine:schema:create

# 5. Charger les données initiales (appartements + témoignages)
composer require --dev doctrine/doctrine-fixtures-bundle
php bin/console doctrine:fixtures:load --no-interaction

# 6. Lancer le serveur de développement
symfony server:start
# OU
php -S localhost:8000 -t public/
```

### Accès
- **Site public** : http://localhost:8000
- **Admin** : http://localhost:8000/admin

---

## Structure du projet

```
appart-hotel-symfony/
├── config/
│   ├── packages/           # Configuration des bundles
│   │   ├── doctrine.yaml
│   │   ├── framework.yaml
│   │   ├── mailer.yaml
│   │   ├── twig.yaml
│   │   └── ...
│   ├── routes/
│   ├── bundles.php
│   ├── routes.yaml
│   └── services.yaml
├── migrations/             # Migrations Doctrine
├── public/
│   ├── css/
│   │   └── style.css       # Feuille de style principale
│   ├── js/
│   │   └── main.js         # JavaScript principal
│   ├── images/             # Images des appartements
│   └── index.php           # Point d'entrée
├── src/
│   ├── Controller/
│   │   ├── HomeController.php
│   │   ├── AppartementController.php
│   │   ├── ReservationController.php
│   │   ├── ContactController.php
│   │   └── AdminController.php
│   ├── DataFixtures/
│   │   └── AppFixtures.php # Données initiales
│   ├── Entity/
│   │   ├── Appartement.php
│   │   ├── Reservation.php
│   │   └── Temoignage.php
│   ├── Form/
│   │   ├── ContactType.php
│   │   └── ReservationType.php
│   ├── Repository/
│   │   ├── AppartementRepository.php
│   │   ├── ReservationRepository.php
│   │   └── TemoignageRepository.php
│   └── Kernel.php
├── templates/
│   ├── admin/
│   │   ├── base.html.twig
│   │   ├── dashboard.html.twig
│   │   └── reservations.html.twig
│   ├── appartement/
│   │   ├── index.html.twig
│   │   └── detail.html.twig
│   ├── bundles/TwigBundle/Exception/
│   │   └── error404.html.twig
│   ├── contact/
│   │   └── index.html.twig
│   ├── home/
│   │   └── index.html.twig
│   ├── reservation/
│   │   └── index.html.twig
│   └── base.html.twig
├── .env
├── .gitignore
├── composer.json
└── README.md
```

---

##  Design

Le design s'inspire du site [appart-hotel-tricastin.com](https://appart-hotel-tricastin.com/) avec :
- **Palette** : tons dorés (`#b8860b`), bleu-gris foncé (`#2c3e50`), crème (`#fdf8f0`)
- **Typographie** : Cormorant Garamond (titres) + Montserrat (corps)
- **Composants** : hero plein écran, cards appartements avec hover, carrousel témoignages, formulaires stylisés
- **Animations** : scroll-reveal, hover transitions, navbar dynamique

---

##  Configuration Base de données

### SQLite (défaut, rien à configurer)
```env
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
```

### MySQL
```env
DATABASE_URL="mysql://user:password@127.0.0.1:3306/appart_hotel?serverVersion=8.0"
```

---

##  Configuration Email

Pour le formulaire de contact, configurez `MAILER_DSN` dans `.env.local` :

```env
# Gmail
MAILER_DSN=gmail://user:password@default

# Brevo (ex-Sendinblue)
MAILER_DSN=sendinblue+api://KEY@default

# Mailhog (dev)
MAILER_DSN=smtp://localhost:1025
```

---

##  Configuration Cloudynary

1. Tester en dry-run pour voir les fichiers leurs chemin d'après le dossier /public/images


php bin/console app:cloudinary:sync-images --dry-run

2. lancement de l'importation Cloudinary

php bin/console app:cloudinary:sync-images


##  Sécurité (à ajouter)

Le back-office `/admin` n'est pas protégé par défaut.  
Pour ajouter l'authentification :

```
Email admin : admin@appart-hotel-tricastin.com
Mdp admin : Moncode23+
---

## Images

Placez vos images d'appartements dans `public/images/` avec les noms :
- `imperial.jpg`
- `suite.jpg`
- `equinoxe.jpg`
- `solstice.jpg`
- `atlantide.jpg`
- `neptune.jpg`
- `poitiers.jpg` (photo de la ville pour la section "À propos")

> Les images de fallback Unsplash sont utilisées automatiquement si les fichiers locaux sont absents.

---

## Licence

Projet privé — Tous droits réservés.
