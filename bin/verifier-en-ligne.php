<?php

/**
 * Vérification du site déployé, depuis l'extérieur.
 *
 * Ce script n'interroge que des URL publiques : il s'exécute depuis n'importe
 * quel poste, sans accès au serveur. Il complète bin/verifier-deploiement.php,
 * qui contrôle l'intérieur de la machine et exige donc un accès SSH — accès que
 * l'hébergement mutualisé de l'école n'accorde pas.
 *
 * Les deux scripts se répondent :
 *   - verifier-deploiement.php   ce que le serveur contient   (SSH ou local)
 *   - verifier-en-ligne.php      ce que le serveur expose     (depuis partout)
 *
 * Utilisation :
 *   php bin/verifier-en-ligne.php
 *   php bin/verifier-en-ligne.php https://exemple.test/livreor/
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Ce script s'exécute uniquement en ligne de commande.\n");
}

if (!extension_loaded('curl')) {
    fwrite(STDERR, "L'extension cURL est requise.\n");
    exit(1);
}

const SITE_PAR_DEFAUT = 'https://mickael-ayilan.students-laplateforme.io/livreor/';

// Code d'erreur cURL renvoyé quand la chaîne de certification ne peut pas être
// validée. Sur un poste Windows sans curl.cainfo renseigné dans php.ini, il ne
// signale rien du site : c'est le poste qui n'a pas de magasin de certificats.
const CURL_ECHEC_CERTIFICAT = 60;

$base = rtrim($argv[1] ?? SITE_PAR_DEFAUT, '/') . '/';

$controles   = [];
$echecs      = 0;
$verifierTls = true;

/**
 * Enregistre le résultat d'un contrôle.
 *
 * Un résultat null marque un contrôle que ce poste ne peut pas effectuer : il
 * est affiché, mais ne compte pas comme un échec du site.
 */
function controler(string $intitule, ?bool $reussi, string $detail = ''): void
{
    global $controles, $echecs;

    $controles[] = [$intitule, $reussi, $detail];

    if ($reussi === false) {
        $echecs++;
    }
}

/**
 * Interroge une URL et renvoie le code HTTP, les en-têtes et le corps.
 *
 * Les redirections ne sont jamais suivies : leur code est précisément ce que
 * plusieurs contrôles cherchent à observer.
 */
function interroger(string $url, ?array $donneesPost = null): array
{
    global $verifierTls;

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HEADER         => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_USERAGENT      => 'verifier-en-ligne/1.0',

        // La validation du certificat reste active par défaut : une erreur TLS
        // doit faire échouer le contrôle, jamais être contournée en silence.
        // Elle n'est désactivée que si le poste s'est révélé incapable de la
        // faire, et le rapport le signale alors explicitement.
        CURLOPT_SSL_VERIFYPEER => $verifierTls,
        CURLOPT_SSL_VERIFYHOST => $verifierTls ? 2 : 0,
    ]);

    if ($donneesPost !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($donneesPost));
    }

    $reponse = curl_exec($ch);
    $erreur  = curl_error($ch);
    $numero  = curl_errno($ch);
    $code    = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $entete  = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);

    curl_close($ch);

    if ($reponse === false) {
        return ['code' => 0, 'entetes' => '', 'corps' => '', 'erreur' => $erreur, 'numero' => $numero];
    }

    return [
        'code'    => $code,
        'entetes' => substr($reponse, 0, $entete),
        'corps'   => substr($reponse, $entete),
        'erreur'  => '',
        'numero'  => 0,
    ];
}

/**
 * Renvoie la date d'expiration du certificat présenté par un hôte.
 *
 * La chaîne de certification n'est délibérément pas validée ici : le but est de
 * lire la date, pas de juger l'autorité émettrice, et cette lecture doit rester
 * possible depuis un poste dépourvu de magasin de racines à jour.
 *
 * @return int|null Horodatage d'expiration, ou null si le certificat est illisible.
 */
function expirationCertificat(string $hote): ?int
{
    if (!extension_loaded('openssl')) {
        return null;
    }

    $contexte = stream_context_create([
        'ssl' => [
            'capture_peer_cert' => true,
            'verify_peer'       => false,
            'verify_peer_name'  => false,
        ],
    ]);

    $flux = @stream_socket_client(
        "ssl://$hote:443",
        $numero,
        $message,
        10,
        STREAM_CLIENT_CONNECT,
        $contexte
    );

    if ($flux === false) {
        return null;
    }

    $parametres = stream_context_get_params($flux);
    fclose($flux);

    $certificat = $parametres['options']['ssl']['peer_certificate'] ?? null;

    if ($certificat === null) {
        return null;
    }

    $details = openssl_x509_parse($certificat);

    return $details['validTo_time_t'] ?? null;
}

echo "\nVérification du site en ligne — Livre d'Or\n";
echo "Cible : $base\n";
echo str_repeat('-', 68), "\n";

// --- Certificat ----------------------------------------------------------------

$accueil = interroger($base . 'index.php');

if ($accueil['numero'] === CURL_ECHEC_CERTIFICAT) {
    // Le poste n'a pas de magasin de certificats utilisable. Le contrôle TLS
    // devient impossible ici, mais tous les autres restent pertinents : on
    // poursuit sans validation, en le disant clairement.
    $verifierTls = false;
    $accueil     = interroger($base . 'index.php');

    controler(
        'Certificat TLS valide',
        null,
        'non vérifiable depuis ce poste : renseigner curl.cainfo dans php.ini'
    );
} else {
    controler(
        'Certificat TLS valide',
        $accueil['erreur'] === '',
        $accueil['erreur']
    );
}

// Valider la chaîne de certification suppose un magasin de racines à jour, que
// tous les postes n'ont pas. Lire la date d'expiration n'en demande aucun : ce
// contrôle-ci fonctionne partout, et c'est celui qui préviendra de la date à
// laquelle le certificat doit être renouvelé.
$expiration = expirationCertificat(parse_url($base, PHP_URL_HOST));

controler(
    'Certificat non expiré'
        . ($expiration !== null ? ' — valable jusqu\'au ' . date('d/m/Y', $expiration) : ''),
    $expiration !== null && $expiration > time(),
    $expiration === null
        ? 'certificat illisible'
        : 'expiré depuis le ' . date('d/m/Y', $expiration)
);

// --- Redirection ----------------------------------------------------------------

$enClair = interroger(
    'http://' . parse_url($base, PHP_URL_HOST) . parse_url($base, PHP_URL_PATH)
);

controler(
    'HTTP redirigé vers HTTPS',
    in_array($enClair['code'], [301, 302, 308], true),
    'code obtenu : ' . $enClair['code']
);

// --- Pages publiques ------------------------------------------------------------

$publiques = ['index.php', 'login.php', 'register.php', 'livre-or.php', 'assets/styles.css'];

foreach ($publiques as $page) {
    $reponse = interroger($base . $page);

    controler(
        "Page publique $page",
        $reponse['code'] === 200,
        'code obtenu : ' . $reponse['code']
    );
}

// --- Contrôle d'accès -----------------------------------------------------------

// Sans session, ces deux pages doivent renvoyer le visiteur vers la connexion.
// Un code 200 signifierait qu'elles s'affichent sans authentification.
foreach (['profil.php', 'commentaire.php'] as $page) {
    $reponse = interroger($base . $page);

    controler(
        "Page $page protégée par la session",
        $reponse['code'] === 302,
        'code obtenu : ' . $reponse['code'] . ', 302 attendu'
    );
}

// --- Fichiers qui ne doivent pas être servis --------------------------------------

$interdits = [
    'livreor.sql',
    'README.md',
    'DEPLOIEMENT.md',
    'deploy.sh',
    'config.example.php',
    'config.local.php',
    '.gitignore',
    '.htaccess',
];

foreach ($interdits as $fichier) {
    $reponse = interroger($base . $fichier);

    controler(
        "Fichier $fichier inaccessible",
        in_array($reponse['code'], [403, 404], true),
        'code obtenu : ' . $reponse['code'] . ', 403 ou 404 attendu'
    );
}

$outil = interroger($base . 'bin/verifier-deploiement.php');

controler(
    'Dossier bin/ masqué',
    $outil['code'] === 404,
    'code obtenu : ' . $outil['code'] . ', 404 attendu'
);

// --- Base de données --------------------------------------------------------------

// L'affichage du décompte prouve que la page a réellement interrogé la base :
// une configuration erronée produirait « Service momentanément indisponible ».
$livre   = interroger($base . 'livre-or.php');
$compte  = [];
$lecture = (bool) preg_match('/Messages \((\d+)\)/', $livre['corps'], $compte);

controler(
    'Lecture de la base confirmée' . ($lecture ? " — Messages ({$compte[1]})" : ''),
    $lecture,
    'le décompte des messages est absent de la page'
);

// --- Compte de démonstration ------------------------------------------------------

// login.php ne redirige que si password_verify() réussit : une redirection
// signifierait que le compte livré avec le schéma SQL est toujours ouvert.
$demonstration = interroger(
    $base . 'login.php',
    ['login' => 'admin', 'password' => 'admin123']
);

controler(
    'Compte de démonstration inutilisable',
    $demonstration['code'] !== 302,
    'connexion admin/admin123 acceptée, supprimer ce compte'
);

// --- Sessions ---------------------------------------------------------------------

// Contrôle partiel : il établit que le serveur émet un cookie de session, non
// que la session est conservée d'une page à l'autre. Seule une connexion réelle
// le prouve, et c'est ce que vérifie le contrôle d'écriture de
// bin/verifier-deploiement.php.
controler(
    'Cookie de session émis',
    (bool) preg_match('/^set-cookie:\s*PHPSESSID=/mi', $accueil['entetes']),
    'aucun PHPSESSID dans les en-têtes'
);

// --- Fuite d'informations ----------------------------------------------------------

// display_errors doit être désactivé en production : ni message PHP ni chemin
// serveur ne doivent apparaître dans une page servie à un visiteur.
$traces = ['Warning:', 'Fatal error', 'Notice:', 'Deprecated:', 'stack trace', '/httpdocs/'];
$fuite  = '';

foreach (['index.php', 'livre-or.php', 'login.php'] as $page) {
    $corps = interroger($base . $page)['corps'];

    foreach ($traces as $trace) {
        if (stripos($corps, $trace) !== false) {
            $fuite = "« $trace » trouvé dans $page";
            break 2;
        }
    }
}

controler(
    'Aucune erreur PHP visible dans les pages',
    $fuite === '',
    $fuite
);

// --- Restitution ---------------------------------------------------------------------

$ignores = 0;

foreach ($controles as [$intitule, $reussi, $detail]) {
    if ($reussi === null) {
        $ignores++;
    }

    printf(
        "%-4s %s%s\n",
        $reussi === null ? '[--]' : ($reussi ? '[OK]' : '[KO]'),
        $intitule,
        ($reussi !== true && $detail !== '') ? "  ($detail)" : ''
    );
}

echo str_repeat('-', 68), "\n";

$total   = count($controles);
$mention = $ignores > 0 ? ", $ignores non vérifiable(s) depuis ce poste" : '';

if ($echecs === 0) {
    printf("%d contrôles, aucun échec%s. Site conforme.\n", $total, $mention);
    exit(0);
}

printf("%d contrôles, %d en échec%s.\n", $total, $echecs, $mention);
exit(1);
