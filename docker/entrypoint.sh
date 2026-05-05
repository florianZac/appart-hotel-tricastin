#!/bin/bash
# entrypoint.sh – Appart Hôtel Tricastin
set -e

echo "=========================================="
echo " Démarrage Appart Hôtel Tricastin"
echo "=========================================="

# ----------------------------------------------------------
# 1. Attente que MySQL soit prêt
# ----------------------------------------------------------
echo "[1/4] Attente connexion MySQL..."

MAX_TRIES=40
count=0
until php -r "
    \$dsn = 'mysql:host=db;port=3306;dbname=appart_hotel_tricastin';
    try {
        new PDO(\$dsn, 'tricastin', 'TricastinPass123!');
        exit(0);
    } catch (Exception \$e) {
        exit(1);
    }
" 2>/dev/null; do
    count=$((count + 1))
    if [ "$count" -ge "$MAX_TRIES" ]; then
        echo "ERREUR : MySQL injoignable après ${MAX_TRIES} tentatives."
        exit 1
    fi
    echo "  Tentative $count/$MAX_TRIES... nouvelle tentative dans 5s"
    sleep 5
done

echo "  MySQL OK."

# ----------------------------------------------------------
# 2. Migrations Doctrine
# ----------------------------------------------------------
echo "[2/4] Exécution des migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# ----------------------------------------------------------
# 3. Warm-up du cache Symfony (prod)
# ----------------------------------------------------------
echo "[3/4] Warm-up du cache..."
php bin/console cache:warmup --env=prod

# Assure que le cache appartient à www-data
chown -R www-data:www-data var/cache var/log 2>/dev/null || true

# ----------------------------------------------------------
# 4. Démarrage Apache
# ----------------------------------------------------------
echo "[4/4] Démarrage d'Apache..."
echo "  Application disponible sur http://localhost"
echo "=========================================="

exec "$@"