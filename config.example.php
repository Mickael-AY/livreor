<?php

/**
 * Modèle de configuration locale.
 *
 * Copier ce fichier en config.local.php, puis renseigner les identifiants
 * de la base de données de l'environnement concerné.
 *
 * config.local.php est listé dans .gitignore : il ne doit jamais être
 * envoyé sur GitHub.
 */

return [
    'db_host'     => 'localhost',
    'db_name'     => 'livreor',
    'db_user'     => 'livreor_user',
    'db_password' => 'a_remplacer_par_le_mot_de_passe',

    // false en production : masque le détail des erreurs aux visiteurs.
    'debug'       => false,
];
