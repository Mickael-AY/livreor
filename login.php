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

// Message de succès après inscription
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
}

if ($_POST) {
    $login = trim($_POST['login']);
    $password = $_POST['password'];

    if (empty($login) || empty($password)) {
        $error = "Tous les champs sont obligatoires";
    } else {
        $stmt = $pdo->prepare("SELECT id, login, password FROM utilisateurs WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user["password"])) {
            // Nouvel identifiant de session après authentification : un jeton
            // imposé au visiteur avant sa connexion devient inutilisable.
            // C'est la parade contre la fixation de session.
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_login'] = $user['login'];

            header("Location: index.php");
            exit();
        } else {
            $error = "Login ou mot de passe incorrect";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Livre d'Or</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>

<body>
    <div class="container">
        <a href="index.php" class="back-home">← Retour à l'accueil</a>

        <div class="form-container">
            <div class="form-header">
                <h1>Connexion</h1>
                <p>Accédez à votre compte pour déposer un message.</p>
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
                    <input type="text" name="login" id="login" required>
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" name="password" id="password" required>
                </div>

                <button type="submit" class="btn">Se connecter</button>
            </form>

            <div class="form-links">
                <p>Pas encore de compte ? <a href="register.php">Inscrivez-vous ici</a></p>
            </div>
        </div>
    </div>
</body>

</html>