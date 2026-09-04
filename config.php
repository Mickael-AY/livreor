<?php

/**
 * Configuration de l'application et connexion à la base de données.
 *
 * Aucun identifiant n'est écrit dans ce fichier, qui est versionné sur GitHub.
 * Les paramètres sont lus dans cet ordre, du moins au plus prioritaire :
 *   1. les valeurs par défaut ci-dessous (développement local sous Laragon) ;
 *   2. le fichier config.local.php, non versionné (voir config.example.php) ;
 *   3. les variables d'environnement, utilisées en production sur Plesk.
 */

// 1. Valeurs par défaut de l'environnement de développement local
$config = [
    'db_host'     => 'localhost',
    'db_name'     => 'livreor',
    'db_user'     => 'root',
    'db_password' => '',
    'debug'       => true,
];

// 2. Fichier de configuration locale, s'il existe
if (is_file(__DIR__ . '/config.local.php')) {
    $config = array_merge($config, require __DIR__ . '/config.local.php');
}

// 3. Variables d'environnement
$variables = [
    'db_host'     => 'DB_HOST',
    'db_name'     => 'DB_NAME',
    'db_user'     => 'DB_USER',
    'db_password' => 'DB_PASSWORD',
];

foreach ($variables as $cle => $variable) {
    $valeur = getenv($variable);
    if ($valeur !== false && $valeur !== '') {
        $config[$cle] = $valeur;
    }
}

$debug = getenv('APP_DEBUG');
if ($debug !== false) {
    $config['debug'] = filter_var($debug, FILTER_VALIDATE_BOOLEAN);
}

// En production, aucune erreur PHP ne doit s'afficher dans le navigateur :
// les messages révèlent les chemins du serveur et la structure de l'application.
// Elles sont systématiquement écrites dans le journal du serveur.
ini_set('display_errors', $config['debug'] ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

try {
    $pdo = new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
        $config['db_user'],
        $config['db_password'],
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

            // Émulation désactivée : MySQL prépare réellement chaque requête et
            // sépare le code SQL des valeurs, ce qui rend l'injection SQL
            // impossible par construction plutôt que par échappement.
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Le détail part dans le journal du serveur, jamais à l'écran du visiteur.
    error_log('Connexion à la base de données impossible : ' . $e->getMessage());

    $message = $config['debug']
        ? 'Erreur de connexion à la base de données : ' . $e->getMessage()
        : 'Service momentanément indisponible. Merci de réessayer dans quelques instants.';

    // En ligne de commande, sortir en code d'erreur pour que les scripts de
    // déploiement détectent l'échec au lieu de le considérer comme un succès.
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }

    http_response_code(503);
    die($message);
}
