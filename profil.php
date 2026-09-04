<?php
session_start();
require "config.php";

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = "";
$success = "";

// Récupérer les informations actuelles de l'utilisateur
$stmt = $pdo->prepare("SELECT login FROM utilisateurs WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Récupérer les statistiques de l'utilisateur
$stmt = $pdo->prepare("SELECT COUNT(*) as nb_comments FROM commentaires WHERE id_utilisateur = ?");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();

if ($_POST) {
    $new_login = trim($_POST['login']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($new_login)) {
        $error = "Le nom d'utilisateur ne peut pas être vide";
    } else {
        // Vérifier si le nouveau login est différent de l'actuel
        if ($new_login !== $user['login']) {
            // Vérifier si le nouveau login existe déjà
            $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE login = ? AND id != ?");
            $stmt->execute([$new_login, $_SESSION['user_id']]);

            if ($stmt->fetch()) {
                $error = "Ce nom d'utilisateur est déjà utilisé";
            }
        }

        // Si on veut changer le mot de passe
        if (!empty($new_password) || !empty($current_password) || !empty($confirm_password)) {
            if (empty($current_password)) {
                $error = "Veuillez saisir votre mot de passe actuel";
            } elseif (empty($new_password)) {
                $error = "Veuillez saisir un nouveau mot de passe";
            } elseif ($new_password !== $confirm_password) {
                $error = "Les nouveaux mots de passe ne correspondent pas";
            } elseif (strlen($new_password) < 6) {
                $error = "Le nouveau mot de passe doit contenir au moins 6 caractères";
            } else {
                // Vérifier le mot de passe actuel
                $stmt = $pdo->prepare("SELECT password FROM utilisateurs WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $current_user = $stmt->fetch();

                if (!password_verify($current_password, $current_user['password'])) {
                    $error = "Mot de passe actuel incorrect";
                }
            }
        }

        // Si pas d'erreur, effectuer les modifications
        if (empty($error)) {
            if (!empty($new_password)) {
                // Modifier login et mot de passe.
                // updated_at est renseignée ici, et non par le serveur :
                // MariaDB 5.5, utilisée en production, n'accepte qu'une seule
                // colonne auto-remplie par table, réservée à created_at.
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE utilisateurs SET login = ?, password = ?, updated_at = NOW() WHERE id = ?");
                $result = $stmt->execute([$new_login, $password_hash, $_SESSION['user_id']]);
            } else {
                // Modifier seulement le login
                $stmt = $pdo->prepare("UPDATE utilisateurs SET login = ?, updated_at = NOW() WHERE id = ?");
                $result = $stmt->execute([$new_login, $_SESSION['user_id']]);
            }

            if ($result) {
                $_SESSION['user_login'] = $new_login;
                $user['login'] = $new_login;
                $success = "Profil mis à jour avec succès !";
            } else {
                $error = "Erreur lors de la mise à jour du profil";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon profil - Livre d'or</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>

<body>
    <div class="container">
        <header>
            <nav>
                <a href="index.php" class="logo">Livre d'or</a>
                <ul class="nav-links">
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="livre-or.php">Livre d'or</a></li>
                    <li><a href="profil.php">Mon profil</a></li>
                    <li><a href="commentaire.php">Écrire un message</a></li>
                    <li><a href="logout.php">Déconnexion</a></li>
                </ul>
                <div class="user-status">
                    <span class="status-online">● Connecté en tant que <?= htmlspecialchars($_SESSION['user_login']) ?></span>
                </div>
            </nav>
        </header>

        <main>
            <div class="profile-container">
                <aside class="profile-sidebar">
                    <div class="profile-avatar">
                        <div class="avatar-icon"><?= htmlspecialchars(strtoupper(mb_substr($user['login'], 0, 1))) ?></div>
                        <h3><?= htmlspecialchars($user['login']) ?></h3>
                    </div>

                    <div class="profile-info">
                        <h2>Mes statistiques</h2>
                        <div class="stat-item">
                            <div class="stat-label">Messages déposés</div>
                            <div class="stat-value"><?= $stats['nb_comments'] ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Statut</div>
                            <div class="stat-value">Membre actif</div>
                        </div>
                    </div>
                </aside>

                <div class="profile-main">
                    <div class="form-header">
                        <h1>Mon profil</h1>
                        <p>Modifiez vos informations personnelles</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="error"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <form action="" method="post">
                        <div class="form-section">
                            <h3>Informations générales</h3>
                            <div class="form-group">
                                <label for="login">Nom d'utilisateur</label>
                                <input type="text" name="login" id="login" value="<?= htmlspecialchars($user['login']) ?>" required>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3>Changer le mot de passe</h3>
                            <p class="password-info">Laissez ces champs vides si vous ne souhaitez pas modifier votre mot de passe.</p>

                            <div class="form-group">
                                <label for="current_password">Mot de passe actuel</label>
                                <input type="password" name="current_password" id="current_password">
                            </div>

                            <div class="form-group">
                                <label for="new_password">Nouveau mot de passe</label>
                                <input type="password" name="new_password" id="new_password">
                                <div class="password-info">Minimum 6 caractères</div>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                                <input type="password" name="confirm_password" id="confirm_password">
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn">Enregistrer les modifications</button>
                            <a href="livre-or.php" class="btn btn-secondary">Retour au livre d'or</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>

</html>