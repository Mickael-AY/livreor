# Livre d'Or

Application web permettant à des visiteurs inscrits de déposer et de consulter
des messages. Réalisée en PHP et MySQL dans le cadre de la formation
Développeur Web et Web Mobile à La Plateforme_ de Toulon.

**En ligne : https://mickael-ayilan.students-laplateforme.io/livreor/**

## Fonctionnalités

- Inscription avec contrôle d'unicité du nom d'utilisateur
- Connexion et déconnexion, mot de passe haché en bcrypt
- Consultation publique du livre d'or, commentaires triés du plus récent au plus ancien
- Dépôt d'un commentaire, réservé aux utilisateurs connectés
- Espace profil : modification du nom d'utilisateur et du mot de passe, nombre de commentaires déposés

## Stack technique

| Élément | Choix |
|---|---|
| Langage serveur | PHP 8.1, sans framework |
| Base de données | MySQL 8 en local, MariaDB 5.5 en production, InnoDB, `utf8mb4_unicode_ci` |
| Accès aux données | PDO, requêtes préparées, émulation désactivée |
| Interface | HTML5 sémantique, feuille CSS3 unique, adaptation aux petits écrans |
| Environnement local | Laragon (Apache, PHP 8.1, MySQL 8) |
| Production | Plesk : nginx devant Apache, PHP 8.0, MariaDB 5.5 |

Aucune dépendance externe : ni Composer, ni npm. Le périmètre du projet ne le
justifie pas, et cela rend le déploiement immédiat.

## Structure

```
livreor/
├── .htaccess                    Règles Apache : ferme l'accès aux fichiers non publics
├── index.php                    Page d'accueil
├── livre-or.php                 Liste publique des commentaires
├── commentaire.php              Dépôt d'un commentaire (connecté)
├── login.php                    Connexion
├── register.php                 Inscription
├── logout.php                   Déconnexion
├── profil.php                   Espace profil (connecté)
├── assets/
│   └── styles.css               Feuille de style unique, partagée par les 7 pages
├── config.php                   Configuration et connexion PDO
├── config.example.php           Modèle de configuration locale
├── livreor.sql                  Schéma de la base et jeu de démonstration
├── deploy.sh                    Script de déploiement
├── DEPLOIEMENT.md               Procédure de déploiement
└── bin/
    ├── verifier-deploiement.php Contrôles sur la machine (ligne de commande)
    └── verifier-en-ligne.php    Contrôles du site déployé, depuis l'extérieur
```

## Installation en local

```bash
git clone https://github.com/Mickael-AY/livreor.git
cd livreor
mysql -u root -e "CREATE DATABASE IF NOT EXISTS livreor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -u root livreor < livreor.sql
```

`livreor.sql` ne crée pas la base : sur un hébergement mutualisé, l'utilisateur
applicatif n'a pas le droit d'exécuter `CREATE DATABASE`. Le même fichier sert
donc en local et en production.

Placer le dossier dans la racine web de Laragon (`C:\laragon\www\`) puis ouvrir
`http://livreor.test/`.

Aucune configuration n'est nécessaire : sans `config.local.php` ni variables
d'environnement, `config.php` retombe sur les valeurs par défaut de Laragon.

Un compte de démonstration est créé par le script : `admin` / `admin123`.
**Il doit être supprimé avant toute mise en ligne.**

## Base de données

Deux tables :

- **`utilisateurs`** — `id`, `login` (unique), `password` (haché), `created_at`, `updated_at`
- **`commentaires`** — `id`, `commentaire`, `id_utilisateur`, `date`

Seule `created_at` est remplie par le serveur : MariaDB 5.5, utilisée en
production, n'accepte qu'une colonne auto-remplie par table. `updated_at` et
`date` sont renseignées par l'application. Le schéma reste ainsi importable
sur MariaDB 5.5 comme sur MySQL 8.

La clé étrangère `commentaires.id_utilisateur` référence `utilisateurs.id` en
`ON DELETE CASCADE` : supprimer un compte supprime ses commentaires, qui n'ont
aucun sens sans leur auteur. Un index composite sur `(id_utilisateur, date)`
couvre l'affichage du livre d'or et le décompte par utilisateur.

## Sécurité

| Risque | Protection en place |
|---|---|
| Injection SQL | Requêtes préparées PDO, `ATTR_EMULATE_PREPARES` à `false` |
| Mots de passe en clair | `password_hash()` / `password_verify()` en bcrypt |
| XSS | `htmlspecialchars()` sur toutes les sorties, `nl2br()` appliqué après échappement |
| Fixation de session | `session_regenerate_id(true)` après authentification |
| Fuite d'informations serveur | `display_errors` désactivé en production, erreurs journalisées |
| Secrets dans le dépôt | Identifiants hors des fichiers versionnés, `config.local.php` ignoré par Git |
| Fichiers exposés par le déploiement | `.htaccess` : schéma SQL, documentation, scripts et fichiers cachés inaccessibles depuis le web |

## Déploiement

La procédure complète, de la création de la base à l'activation du HTTPS, est
décrite dans [DEPLOIEMENT.md](DEPLOIEMENT.md).

```bash
./deploy.sh                      # met à jour et vérifie
php bin/verifier-deploiement.php # contrôle la machine    (local ou SSH)
php bin/verifier-en-ligne.php    # contrôle le site publié (depuis partout)
```

L'hébergement utilisé n'accorde pas d'accès SSH : le premier script tourne donc
en local avant chaque mise en ligne, et le second vérifie la production depuis
l'extérieur, en n'interrogeant que des URL publiques.

## Organisation Git

- `main` — version déployée en production
- `dev` — intégration
- `feature/*` — une branche par fonctionnalité, fusionnée dans `dev` par pull request
