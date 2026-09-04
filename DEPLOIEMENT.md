# Procédure de déploiement — Livre d'Or

Ce document décrit la mise en production de l'application, la procédure de mise
à jour et le retour arrière. Il est tenu à jour à chaque évolution des
dépendances ou de la configuration.

**Cette procédure a été exécutée le 4 septembre 2026.** Les valeurs ci-dessous
sont celles de l'installation réelle, pas des exemples.

---

## 1. Environnement de production

| Élément | Valeur |
|---|---|
| Hébergeur | Plesk fourni par La Plateforme_ |
| Panneau | `https://students-laplateforme.io:8443` |
| Domaine | `mickael-ayilan.students-laplateforme.io` |
| URL de l'application | **https://mickael-ayilan.students-laplateforme.io/livreor/** |
| Racine de déploiement | `/httpdocs/livreor` |
| Serveur web | **nginx en frontal, Apache derrière** |
| PHP | 8.0.26 |
| Base de données | **MariaDB 5.5.68**, `localhost:3306` |
| Nom de la base | `mickael-ayilan_livreor` |
| Utilisateur | `livreor_user` |
| HTTPS | Certificat générique `*.students-laplateforme.io`, redirection HTTP → HTTPS automatique |

### Trois écarts avec l'environnement de développement

Ce sont les contraintes réelles de l'hébergement, et le code les prend en charge.

| Poste local | Production | Conséquence |
|---|---|---|
| MySQL 8 | **MariaDB 5.5** | `DATETIME DEFAULT CURRENT_TIMESTAMP` interdit, et une seule colonne auto-remplie par table |
| `root` peut tout faire | `livreor_user` n'a aucun droit `CREATE DATABASE` | Le script SQL ne crée que les tables |
| Nom de base libre | Préfixe `mickael-ayilan_` imposé | Le nom complet va dans la configuration |
| Apache seul | nginx devant Apache | Le `.htaccess` reste appliqué, car Apache est dans la chaîne |

### Cohabitation avec un autre projet

Le domaine héberge déjà le dépôt `runtrack2.git`, déployé dans `/httpdocs/module`.
Livre d'Or est installé dans `/httpdocs/livreor` : les deux ne se recouvrent pas,
et la page d'index fournie par l'école à la racine reste intacte.

---

## 2. Prérequis

- PHP **8.0 minimum**, avec l'extension **`pdo_mysql`**
- Une base de données et un **utilisateur dédié** à cette base
- Un accès Git ou FTP depuis le panneau
- Un nom de domaine ou sous-domaine

Il n'y a ni `composer.json` ni `package.json` : aucune dépendance à installer,
aucune étape de build. Le déploiement se réduit à copier les fichiers et à
configurer l'accès à la base.

> **Ne jamais déployer avec le compte `root` de la base.** L'application n'a
> besoin que de `SELECT`, `INSERT`, `UPDATE` et `DELETE` sur sa propre base.

---

## 3. Fichiers de configuration

Aucun identifiant n'est écrit dans un fichier versionné.

| Fichier | Versionné | Rôle |
|---|---|---|
| `config.php` | oui | Charge la configuration et ouvre la connexion PDO |
| `config.example.php` | oui | Modèle à recopier, sans identifiant réel |
| `config.local.php` | **non** (`.gitignore`) | Identifiants réels de l'environnement |

`config.php` lit les paramètres dans cet ordre, du moins au plus prioritaire :

1. les valeurs par défaut du développement local ;
2. `config.local.php` s'il existe ;
3. les variables d'environnement `DB_HOST`, `DB_NAME`, `DB_USER`,
   `DB_PASSWORD`, `APP_DEBUG`.

En production, c'est `config.local.php` qui est utilisé.

---

## 4. Déploiement initial

### 4.1 Créer la base et son utilisateur

Panneau → **Bases de données** → **Ajouter une base de données**.

- Nom : `livreor`. Plesk préfixe automatiquement en `mickael-ayilan_livreor`,
  le préfixe n'est pas modifiable.
- Cocher **Créer un utilisateur de base de données**, le nommer `livreor_user`.
- Mot de passe : **Générer**, puis le conserver, il ne sera plus affiché.
- **Laisser décochée** la case « accès à toutes les bases de l'abonnement » :
  l'utilisateur ne doit voir que sa propre base.

### 4.2 Importer le schéma

Sur la ligne de la base → **Import Dump** → **Upload** → `livreor.sql`.

> **Décocher « Recreate the database ».** Cette option supprime puis recrée la
> base, qui reprendrait alors le jeu de caractères par défaut du serveur —
> `latin1` sur MariaDB 5.5.

La base doit passer de `Tables : 0` à `Tables : 2`.

### 4.3 Déployer le code

Panneau → **Websites & Domains** → **Git** → **Add Repository**.

| Champ | Valeur |
|---|---|
| Code location | Remote repository |
| Repository URL | `https://github.com/Mickael-AY/livreor.git` |
| Username / Password | vides — le dépôt est public |
| Repository name | `livreor.git` |
| Deployment mode | Automatic |
| Server path | `/httpdocs/livreor` |

> Le formulaire de création ne propose pas de choisir la branche : Plesk prend
> la branche par défaut du dépôt. **Après création, vérifier la liste déroulante
> `Branch` et la positionner sur `main`.** La ligne *Deployment* doit afficher
> `main branch automatically to /httpdocs/livreor`.

Puis **Pull now**, et **Deploy now**.

### 4.4 Configurer les accès

Panneau → **File Manager** → `httpdocs/livreor` → **+** → *Créer un fichier*,
nommé exactement `config.local.php` :

```php
<?php

return [
    'db_host'     => 'localhost',
    'db_name'     => 'mickael-ayilan_livreor',
    'db_user'     => 'livreor_user',
    'db_password' => '...',
    'debug'       => false,
];
```

`debug` à `false` désactive l'affichage des erreurs aux visiteurs ; elles
continuent d'être écrites dans le journal du serveur.

### 4.5 Supprimer le compte de démonstration

Le script SQL insère un compte `admin` dont le mot de passe est écrit en clair
dans le fichier. **Créer d'abord son propre compte depuis le site**, puis :

```sql
DELETE FROM utilisateurs WHERE login = 'admin';
```

### 4.6 HTTPS

Sur cet hébergement, un certificat générique `*.students-laplateforme.io`
couvre déjà le domaine, et la redirection HTTP → HTTPS est active par défaut.
Aucune action n'a été nécessaire.

Sur un hébergement qui n'en fournirait pas : **Certificats SSL/TLS → Installer
un certificat Let's Encrypt gratuit**, puis cocher la redirection.

---

## 5. Vérification après déploiement

### 5.1 Script automatisé

```bash
php bin/verifier-deploiement.php
```

Il contrôle la version de PHP, l'extension PDO, la configuration, la connexion,
les deux tables, le moteur InnoDB, la clé étrangère et l'absence du compte de
démonstration. Sortie en code `0` si tout est conforme, `1` sinon. Il refuse de
s'exécuter depuis un navigateur.

### 5.2 Contrôles effectués le 4 septembre 2026

Relevés depuis l'extérieur, sur l'installation réelle.

| Contrôle | Attendu | Obtenu |
|---|---|---|
| `/livreor/index.php` | 200 | **200** |
| `/livreor/livre-or.php` | 200 | **200** |
| `/livreor/assets/styles.css` | 200 | **200** |
| Lecture de la base | Page affichée, pas d'erreur | **« Messages (0) »** |
| `/livreor/livreor.sql` | refusé | **403** |
| `/livreor/README.md` | refusé | **403** |
| `/livreor/DEPLOIEMENT.md` | refusé | **403** |
| `/livreor/deploy.sh` | refusé | **403** |
| `/livreor/config.example.php` | refusé | **403** |
| `/livreor/config.local.php` | refusé | **403** |
| `/livreor/.gitignore` | refusé | **403** |
| `/livreor/bin/verifier-deploiement.php` | masqué | **404** |
| `http://` | redirigé | **301** vers `https://` |
| Certificat TLS | valide | **valide** |

L'affichage de « Messages (0) » est le contrôle décisif : il prouve que
l'application a réellement interrogé MariaDB. Une configuration erronée aurait
produit « Service momentanément indisponible ».

---

## 6. Mise à jour d'une version

Le mode de déploiement est **Automatic** : Plesk publie dès qu'il a récupéré le
code. Pour déclencher une mise à jour :

1. Fusionner le travail dans `main` et pousser sur GitHub
2. Panneau → **Git** → **Pull now** sur `livreor.git`
3. Vérifier le site

Depuis un accès SSH, le script `deploy.sh` enchaîne sauvegarde, récupération et
vérification, et s'interrompt à la première erreur.

> **Point non encore vérifié :** on ignore si un redéploiement Plesk supprime
> les fichiers absents du dépôt. Si `config.local.php` venait à disparaître
> après un `Deploy now`, il faudrait basculer la configuration sur les variables
> d'environnement (**PHP Settings**), qui ne sont pas affectées par les
> déploiements. À tester lors de la prochaine mise à jour.

---

## 7. Retour arrière

```bash
git log --oneline -5
git checkout <hash_du_commit>
php bin/verifier-deploiement.php
```

Depuis le panneau, la carte du dépôt permet aussi de revenir à un commit
antérieur. Si la base a été modifiée, la restaurer depuis la sauvegarde.

---

## 8. Sauvegarde

La base contient les comptes et les messages : c'est la seule donnée non
reproductible. Les fichiers sont intégralement dans Git.

- Panneau → **Bases de données** → **Export Dump**
- Ou : **Websites & Domains → Backup & Restore**, avec une périodicité
  quotidienne et sept jours de rétention
- En ligne de commande :
  ```bash
  mysqldump -u livreor_user -p mickael-ayilan_livreor | gzip > sauvegarde-$(date +%F).sql.gz
  ```

---

## 9. Journalisation et diagnostic

`display_errors` est désactivé et `log_errors` activé : les erreurs partent dans
le journal du serveur, consultable dans **Websites & Domains → Logs**.

| Symptôme | Cause probable | Vérification |
|---|---|---|
| « Service momentanément indisponible » | Identifiants erronés dans `config.local.php` | Journal des erreurs PHP |
| Page blanche | Erreur fatale avec `display_errors` désactivé | Journal des erreurs PHP |
| Accents incorrects | Base recréée sans `utf8mb4` | `SHOW CREATE DATABASE` |
| Connexion impossible malgré un bon mot de passe | Session non conservée | Cookies autorisés, HTTPS actif |
| Erreur SQL à l'import | Script incompatible avec MariaDB 5.5 | Voir la section 1 |

---

## 10. Environnement de développement local

```bash
git clone https://github.com/Mickael-AY/livreor.git
cd livreor
mysql -u root -e "CREATE DATABASE IF NOT EXISTS livreor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -u root livreor < livreor.sql
```

Placer le dossier dans `C:\laragon\www\`, démarrer Laragon, ouvrir
`http://livreor.test/`.

Aucune configuration n'est nécessaire : sans `config.local.php` ni variables
d'environnement, `config.php` retombe sur les valeurs par défaut de Laragon et
le mode `debug` reste activé.
