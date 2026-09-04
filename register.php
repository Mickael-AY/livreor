<?php
session_start();
require "config.php";
$error = "";
$success = "";

// Rediriger si déjà connecté
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_POST) {
    $login = trim($_POST['login']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($login) || empty($password) || empty($confirm_password)) {
        $error = "Tous les champs sont obligatoires";
    } elseif ($password !== $confirm_password) {
        $error = "Les mots de passe ne sont pas identiques !";
    } elseif (strlen($password) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE login = ?");
        $stmt->execute([$login]);

        if ($stmt->fetch()) {
            $error = "Ce nom d'utilisateur existe déjà";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO utilisateurs (login, password) VALUES (?, ?)");

            if ($stmt->execute([$login, $password_hash])) {
                $success = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
                header("Location: login.php?success=1");
                exit();
            } else {
                $error = "Erreur lors de l'inscription";
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
    <title>Inscription - Livre d'or</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>

<body>
    <div class="container">
        <a href="index.php" class="back-home">← Retour à l'accueil</a>

        <div class="form-container">
            <div class="form-header">
                <h1>Inscription</h1>
                <p>L'inscription ne demande qu'un nom d'utilisateur et un mot de passe.</p>
            </div>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form action="" method="post">
                <div class="form-group">
                    <label for="login">Nom d'utilisateur</label>
                    <input type="text" name="login" id="login" required value="<?= isset($_POST['login']) ? htmlspecialchars($_POST['login']) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" name="password" id="password" required>
                    <div class="password-info">Minimum 6 caractères</div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmer le mot de passe</label>
                    <input type="password" name="confirm_password" id="confirm_password" required>
                </div>

                <button type="submit" class="btn">S'inscrire</button>
            </form>

            <div class="form-links">
                <p>Déjà un compte ? <a href="login.php">Connectez-vous ici</a></p>
            </div>
        </div>
    </div>
</body>

</html>