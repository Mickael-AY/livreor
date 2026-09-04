# Procédure de déploiement — Livre d'Or

Ce document décrit la mise en production de l'application Livre d'Or sur un
hébergement mutualisé Plesk, ainsi que la procédure de mise à jour et de retour
arrière. Il est tenu à jour à chaque évolution des dépendances ou de la
configuration.

Toutes les valeurs entre chevrons (`<...>`) sont à remplacer par les valeurs
réelles de l'hébergement.

---

## 1. Architecture déployée

L'application est un site PHP servi directement par Apache, sans étape de build
ni gestionnaire de dépendances. Elle se compose de deux éléments :

| Composant | Rôle | Version cible |
|---|---|---|
| Serveur web + PHP | Sert les 7 pages et exécute la logique applicative | PHP 8.1 ou supérieur |
| Base MySQL | Stocke les comptes et les commentaires | MySQL 8.0 / MariaDB 10.5+ |

Il n'y a ni `composer.json`, ni `package.json` : le déploiement se réduit donc à
copier les fichiers et à configurer l'accès à la base. C'est un choix assumé pour
ce projet, dont le périmètre ne justifie aucune dépendance externe.

---

## 2. Prérequis

Sur l'hébergement :

- PHP **8.1 minimum**, avec l'extension **`pdo_mysql`** activée
- Une base de données MySQL et un **utilisateur dédié** à cette base
- Un accès FTP/SFTP ou Git depuis le panneau Plesk
- Un nom de domaine ou sous-domaine pointant vers l'hébergement

En local, pour préparer le déploiement :

- Git
- Un client MySQL en ligne de commande, ou l'accès phpMyAdmin de Plesk

> **Ne jamais déployer avec l'utilisateur `root` de MySQL.** L'application n'a
> besoin que des droits `SELECT`, `INSERT`, `UPDATE` et `DELETE` sur sa propre
> base.

---

## 3. Fichiers de configuration

Les identifiants ne sont écrits dans aucun fichier versionné.

| Fichier | Versionné | Rôle |
|---|---|---|
| `config.php` | oui | Charge la configuration et ouvre la connexion PDO |
| `config.example.php` | oui | Modèle à recopier, sans identifiant réel |
| `config.local.php` | **non** (`.gitignore`) | Identifiants réels de l'environnement |

`config.php` lit les paramètres dans cet ordre, du moins au plus prioritaire :

1. les valeurs par défaut du développement local (Laragon) ;
2. `config.local.php` s'il existe ;
3. les variables d'environnement `DB_HOST`, `DB_NAME`, `DB_USER`,
   `DB_PASSWORD` et `APP_DEBUG`.

Les variables d'environnement priment, ce qui permet de configurer la production
depuis le panneau Plesk sans déposer le moindre secret sur le serveur de
fichiers. Si l'hébergement ne les expose pas à PHP, `config.local.php` prend le
relais.

---

## 4. Déploiement initial sur Plesk

### 4.1 Créer la base et son utilisateur

Dans Plesk : **Bases de données → Ajouter une base de données**.

- Nom de la base : `<livreor>`
- Créer un utilisateur dédié : `<livreor_user>`
- Mot de passe : généré aléatoirement, au moins 16 caractères
- Accès : **local uniquement**, pas d'accès distant

Noter ces informations, elles serviront à l'étape 4.4.

### 4.2 Déposer les fichiers

Deux méthodes, au choix.

**Par Git (recommandé)** — Plesk, **Git → Ajouter un dépôt** :

```
https://github.com/Mickael-AY/livreor.git
```

Branche à déployer : `main`. Répertoire cible : la racine web du domaine
(`httpdocs`). Plesk propose ensuite un déploiement automatique à chaque `push`.

**Par FTP** — envoyer le contenu du dépôt dans `httpdocs`, à l'exclusion de
`.git/`, `.gitignore` et `DEPLOIEMENT.md`.

### 4.3 Importer le schéma de la base

Le script `livreor.sql` crée la base, les deux tables et un compte de
démonstration.

Depuis phpMyAdmin (Plesk → Bases de données → phpMyAdmin), importer
`livreor.sql`.

En ligne de commande, si l'hébergement fournit un accès SSH :

```bash
mysql -u <livreor_user> -p <livreor> < livreor.sql
```

> Le script commence par `DROP DATABASE IF EXISTS`. Sur un hébergement
> mutualisé, l'utilisateur n'a généralement pas le droit de créer ou de
> supprimer une base : supprimer alors les trois premières instructions
> (`DROP DATABASE`, `CREATE DATABASE`, `USE`) et importer le reste dans la base
> déjà créée à l'étape 4.1.

> **Supprimer le compte de démonstration après l'import.** Le script insère un
> utilisateur `admin` dont le mot de passe en clair est lisible dans le fichier :
>
> ```sql
> DELETE FROM utilisateurs WHERE login = 'admin';
> ```

### 4.4 Configurer les accès

**Option A — variables d'environnement (préférée).**
Plesk → **PHP → Paramètres PHP → variables d'environnement** :

```
DB_HOST=localhost
DB_NAME=<livreor>
DB_USER=<livreor_user>
DB_PASSWORD=<mot_de_passe>
APP_DEBUG=false
```

**Option B — fichier local.**
Copier `config.example.php` en `config.local.php` sur le serveur et y renseigner
les mêmes valeurs, avec `'debug' => false`.

### 4.5 Régler les permissions

```bash
find httpdocs -type d -exec chmod 755 {} \;
find httpdocs -type f -exec chmod 644 {} \;
chmod 600 httpdocs/config.local.php
```

La dernière commande ne s'applique que si l'option B a été retenue.

### 4.6 Activer HTTPS

Plesk → **Certificats SSL/TLS → Installer un certificat Let's Encrypt gratuit**,
puis cocher **Rediriger le HTTP vers le HTTPS**.

Sans HTTPS, les identifiants de connexion circulent en clair : cette étape n'est
pas optionnelle.

---

## 5. Vérification après déploiement

Exécuter le script de contrôle, qui vérifie la version de PHP, la présence de
l'extension PDO, la configuration, la connexion à la base, les deux tables, le
moteur InnoDB et la clé étrangère :

```bash
php bin/verifier-deploiement.php
```

Le script sort en code `0` si tout est conforme, `1` sinon. Il refuse de
s'exécuter depuis un navigateur pour ne rien divulguer publiquement.

Contrôles manuels complémentaires :

| Contrôle | Résultat attendu |
|---|---|
| Ouvrir `https://<domaine>/` | La page d'accueil s'affiche, cadenas présent |
| Créer un compte puis se connecter | Redirection vers l'accueil, statut « Connecté » |
| Déposer un commentaire | Il apparaît en tête du livre d'or |
| Ouvrir `https://<domaine>/config.local.php` | Page blanche, **aucun identifiant affiché** |
| Provoquer une erreur SQL | Message générique, **aucun chemin serveur visible** |

---

## 6. Mise à jour d'une version

```bash
# 1. Sauvegarder la base avant toute intervention
mysqldump -u <livreor_user> -p <livreor> > sauvegarde-$(date +%F).sql

# 2. Récupérer la nouvelle version
git pull origin main

# 3. Contrôler que l'environnement est toujours conforme
php bin/verifier-deploiement.php
```

`config.local.php` étant ignoré par Git, il n'est jamais écrasé par une mise à
jour.

Le script `deploy.sh` enchaîne ces trois étapes et s'interrompt à la première
erreur.

---

## 7. Retour arrière

En cas de régression, revenir au dernier commit fonctionnel :

```bash
git log --oneline -5
git checkout <hash_du_commit>
php bin/verifier-deploiement.php
```

Si la base a été modifiée, la restaurer depuis la sauvegarde de l'étape 6 :

```bash
mysql -u <livreor_user> -p <livreor> < sauvegarde-<date>.sql
```

---

## 8. Sauvegarde

La base contient les comptes et les commentaires : c'est la seule donnée non
reproductible du projet. Les fichiers, eux, sont intégralement dans Git.

Sauvegarde manuelle :

```bash
mysqldump -u <livreor_user> -p <livreor> | gzip > sauvegarde-$(date +%F).sql.gz
```

Plesk propose également une sauvegarde planifiée (**Sauvegarde et restauration →
Paramètres de sauvegarde**), à régler sur une périodicité quotidienne avec une
rétention de sept jours.

---

## 9. Journalisation et diagnostic

En production, `display_errors` est désactivé et `log_errors` activé : les
erreurs partent dans le journal du serveur, consultable dans Plesk sous
**Journaux**.

| Symptôme | Cause probable | Vérification |
|---|---|---|
| « Service momentanément indisponible » | Identifiants de base erronés, ou MySQL arrêté | Journal des erreurs PHP |
| Page blanche | Erreur fatale PHP avec `display_errors` désactivé | Journal des erreurs PHP |
| Caractères accentués incorrects | Base créée sans `utf8mb4` | `SHOW CREATE DATABASE <livreor>` |
| Connexion impossible malgré un bon mot de passe | Session non conservée | Cookies autorisés, HTTPS actif |

---

## 10. Environnement de développement local

Pour reconstituer le projet sur un poste :

```bash
git clone https://github.com/Mickael-AY/livreor.git
cd livreor
mysql -u root < livreor.sql
```

Aucune configuration n'est nécessaire : en l'absence de `config.local.php` et de
variables d'environnement, `config.php` retombe sur les valeurs par défaut de
Laragon (`localhost`, base `livreor`, utilisateur `root`, mot de passe vide) et
le mode `debug` reste activé.
