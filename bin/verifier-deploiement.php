<?php

/**
 * Vérification post-déploiement.
 *
 * Contrôle que l'environnement cible remplit les prérequis de l'application
 * et que la mise en production n'a rien laissé d'ouvert.
 *
 * Utilisation en ligne de commande :   php bin/verifier-deploiement.php
 *
 * Ce script n'est pas destiné à rester accessible depuis le navigateur.
 * Il refuse de s'exécuter via HTTP pour ne rien divulguer publiquement.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Ce script s'exécute uniquement en ligne de commande.\n");
}

$racine = dirname(__DIR__);

// Charge la configuration et ouvre la connexion. En cas d'échec, config.php
// journalise la cause et sort en code d'erreur : rien à traiter ici.
require $racine . '/config.php';

// config.php expose deux variables : les paramètres effectifs et la connexion.
// Les reprendre explicitement rend la dépendance visible et analysable.
/** @var array $config */
$parametres = $config;

/** @var PDO $pdo */
$connexion = $pdo;

// L'environnement se déduit du mode debug : activé en développement,
// obligatoirement désactivé en production.
$production = !$parametres['debug'];

$controles = [];
$echecs    = 0;

/**
 * Enregistre le résultat d'un contrôle.
 *
 * Un contrôle marqué « production seulement » est ignoré tant que l'application
 * tourne en mode debug : exiger en développement local ce qui n'a de sens qu'en
 * production noierait les vraies anomalies.
 */
function controler(
    string $intitule,
    bool $reussi,
    string $detail = '',
    bool $productionSeulement = false
): void {
    global $controles, $echecs, $production;

    if ($productionSeulement && !$production) {
        $controles[] = [$intitule, null, 'sans objet en développement'];
        return;
    }

    $controles[] = [$intitule, $reussi, $detail];

    if (!$reussi) {
        $echecs++;
    }
}

// --- Prérequis de la plateforme ---------------------------------------------

controler(
    'PHP ' . PHP_VERSION . ' (8.0 minimum attendu)',
    PHP_VERSION_ID >= 80000
);

controler(
    'Extension PDO MySQL disponible',
    extension_loaded('pdo_mysql')
);

// --- Configuration -----------------------------------------------------------

// En production, les identifiants viennent soit de config.local.php, soit des
// variables d'environnement : l'un des deux doit être en place, faute de quoi
// l'application tournerait sur les valeurs par défaut de développement.
controler(
    'Identifiants de production configurés',
    is_file($racine . '/config.local.php') || getenv('DB_NAME') !== false,
    'ni config.local.php ni variable DB_NAME',
    true
);

$estDepotGit = is_dir($racine . '/.git');

controler(
    'config.local.php exclu du dépôt Git',
    !$estDepotGit || str_contains(
        (string) @file_get_contents($racine . '/.gitignore'),
        'config.local.php'
    ),
    'ajouter config.local.php au .gitignore'
);

controler(
    'Erreurs PHP masquées aux visiteurs',
    ini_get('display_errors') === '0' || ini_get('display_errors') === '',
    'display_errors doit être désactivé',
    true
);

controler(
    'Journalisation des erreurs active',
    (bool) ini_get('log_errors')
);

// --- Schéma de la base -------------------------------------------------------

$tablesPresentes = $connexion->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

foreach (['utilisateurs', 'commentaires'] as $table) {
    controler(
        "Table « $table » présente",
        in_array($table, $tablesPresentes, true)
    );
}

$moteur = $connexion->query("
    SELECT ENGINE
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'commentaires'
")->fetchColumn();

controler(
    'Moteur ' . ($moteur ?: 'inconnu') . ' sur commentaires (InnoDB attendu)',
    $moteur === 'InnoDB'
);

$contraintes = $connexion->query("
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE() AND CONSTRAINT_TYPE = 'FOREIGN KEY'
")->fetchColumn();

controler(
    'Clé étrangère commentaires vers utilisateurs en place',
    (int) $contraintes >= 1
);

// Le script SQL livre un compte de démonstration dont le mot de passe est
// public. Le laisser en production ouvrirait un accès à n'importe qui.
$demonstration = $connexion->query("
    SELECT COUNT(*) FROM utilisateurs WHERE login = 'admin'
")->fetchColumn();

controler(
    'Compte de démonstration supprimé',
    (int) $demonstration === 0,
    'supprimer : DELETE FROM utilisateurs WHERE login = \'admin\'',
    true
);

// --- Stockage des sessions ---------------------------------------------------

// Une session que le serveur ne parvient pas à écrire ne provoque aucune erreur
// visible : l'authentification réussit, puis l'utilisateur se retrouve
// déconnecté à la page suivante. Le seul contrôle fiable est d'écrire
// réellement un fichier, car un dossier peut exister et rester inutilisable
// s'il est saturé.

$cheminSessions = (string) ini_get('session.save_path');

// session.save_path accepte aussi les formes « N;/chemin » et « N;mode;/chemin »
// où N est une profondeur de sous-dossiers : seul le chemin nous intéresse.
if (str_contains($cheminSessions, ';')) {
    $cheminSessions = substr($cheminSessions, strrpos($cheminSessions, ';') + 1);
}

if ($cheminSessions === '') {
    $cheminSessions = sys_get_temp_dir();
}

controler(
    "Dossier des sessions présent : $cheminSessions",
    is_dir($cheminSessions),
    'créer ce dossier, ou corriger session.save_path'
);

$sessionEcrite = false;
$causeSession  = 'dossier absent';

if (is_dir($cheminSessions)) {
    $fichierTemoin = $cheminSessions . '/sess_verification_' . bin2hex(random_bytes(6));

    if (@file_put_contents($fichierTemoin, 'verification') !== false) {
        $sessionEcrite = true;
        @unlink($fichierTemoin);
    } else {
        $derniere    = error_get_last();
        $causeSession = $derniere === null
            ? 'écriture refusée'
            : preg_replace('/^.*failed: /', '', $derniere['message']);
    }
}

controler(
    "Écriture effective d'une session",
    $sessionEcrite,
    $causeSession
);

// --- Restitution -------------------------------------------------------------

echo "\nVérification du déploiement — Livre d'Or\n";
echo 'Environnement : ', $production ? 'production' : 'développement', "\n";
echo str_repeat('-', 62), "\n";

foreach ($controles as [$intitule, $reussi, $detail]) {
    $marque = $reussi === null ? '[--]' : ($reussi ? '[OK]' : '[KO]');

    printf(
        "%-4s %s%s\n",
        $marque,
        $intitule,
        ($reussi !== true && $detail !== '') ? "  ($detail)" : ''
    );
}

echo str_repeat('-', 62), "\n";

if ($echecs === 0) {
    echo "Déploiement conforme.\n";
    exit(0);
}

echo "$echecs contrôle(s) en échec.\n";
exit(1);
