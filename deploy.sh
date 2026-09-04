#!/usr/bin/env bash
#
# Script de déploiement — Livre d'Or
#
# Enchaîne la sauvegarde de la base, la récupération de la nouvelle version et
# la vérification de l'environnement. S'interrompt à la première erreur pour ne
# jamais laisser le site dans un état intermédiaire.
#
# Utilisation :
#   ./deploy.sh                    déploie la branche main
#   ./deploy.sh dev                déploie une autre branche
#
# Prérequis : accès SSH à l'hébergement, Git et mysqldump disponibles.
# La procédure complète est décrite dans DEPLOIEMENT.md.

set -euo pipefail

BRANCHE="${1:-main}"
RACINE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DOSSIER_SAUVEGARDES="$RACINE/sauvegardes"
HORODATAGE="$(date +%Y-%m-%d_%H%M%S)"

cd "$RACINE"

echo "Déploiement de la branche $BRANCHE"
echo "------------------------------------------------------------"

# --- 1. Contrôles préalables ------------------------------------------------

if [ ! -f config.local.php ] && [ -z "${DB_NAME:-}" ]; then
    echo "Erreur : ni config.local.php ni variables d'environnement." >&2
    echo "Copier config.example.php en config.local.php avant de déployer." >&2
    exit 1
fi

if [ -n "$(git status --porcelain)" ]; then
    echo "Erreur : des modifications non validées existent sur le serveur." >&2
    echo "Le déploiement écraserait ces changements, il est interrompu." >&2
    git status --short >&2
    exit 1
fi

# --- 2. Sauvegarde de la base ------------------------------------------------

if command -v mysqldump > /dev/null 2>&1 && [ -n "${DB_NAME:-}" ]; then
    mkdir -p "$DOSSIER_SAUVEGARDES"
    SAUVEGARDE="$DOSSIER_SAUVEGARDES/livreor-$HORODATAGE.sql.gz"

    echo "Sauvegarde de la base vers $SAUVEGARDE"
    mysqldump \
        --host="${DB_HOST:-localhost}" \
        --user="$DB_USER" \
        --password="$DB_PASSWORD" \
        "$DB_NAME" | gzip > "$SAUVEGARDE"
else
    echo "Sauvegarde ignorée : mysqldump indisponible ou variables non définies."
    echo "Sauvegarder manuellement depuis Plesk avant de continuer."
fi

# --- 3. Récupération de la nouvelle version ----------------------------------

VERSION_PRECEDENTE="$(git rev-parse --short HEAD)"
echo "Version actuelle : $VERSION_PRECEDENTE"

git fetch origin "$BRANCHE"
git checkout "$BRANCHE"
git pull origin "$BRANCHE"

echo "Nouvelle version : $(git rev-parse --short HEAD)"

# --- 4. Vérification ---------------------------------------------------------

echo "------------------------------------------------------------"

if php bin/verifier-deploiement.php; then
    echo "Déploiement terminé."
else
    echo "Erreur : la vérification a échoué." >&2
    echo "Pour revenir en arrière :  git checkout $VERSION_PRECEDENTE" >&2
    exit 1
fi
