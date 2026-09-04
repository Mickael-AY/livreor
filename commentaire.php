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

if ($_POST) {
    $commentaire = trim($_POST['commentaire']);

    if (empty($commentaire)) {
        $error = "Le commentaire ne peut pas être vide";
    } elseif (strlen($commentaire) < 10) {
        $error = "Le commentaire doit contenir au moins 10 caractères";
    } else {
        $stmt = $pdo->prepare("INSERT INTO commentaires (commentaire, id_utilisateur, date) VALUES (?, ?, NOW())");

        if ($stmt->execute([$commentaire, $_SESSION['user_id']])) {
            $success = "Votre commentaire a été ajouté avec succès !";
            header("Location: livre-or.php");
            exit();
        } else {
            $error = "Erreur lors de l'ajout du commentaire";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un commentaire - Livre d'Or</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>

<body>
    <div class="container">
        <header>
            <nav>
                <a href="index.php" class="logo">Livre d'Or</a>
                <ul class="nav-links">
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="livre-or.php">Livre d'Or</a></li>
                    <li><a href="profil.php">Mon Profil</a></li>
                    <li><a href="commentaire.php">Ajouter Commentaire</a></li>
                    <li><a href="logout.php">Déconnexion</a></li>
                </ul>
                <div class="user-status">
                    <span class="status-online">● Connecté en tant que <?= htmlspecialchars($_SESSION['user_login']) ?></span>
                </div>
            </nav>
        </header>

        <main>
            <div class="form-container">
                <div class="form-header">
                    <h1>Ajouter un message</h1>
                    <p>Partagez votre expérience avec notre communauté</p>
                </div>

                <div class="user-info">
                    <strong>👤 <?= htmlspecialchars($_SESSION['user_login']) ?></strong>, votre commentaire sera publié immédiatement et visible par tous les visiteurs.
                </div>

                <?php if ($error): ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <form action="" method="post">
                    <div class="form-group">
                        <label for="commentaire">Votre commentaire *</label>
                        <textarea
                            name="commentaire"
                            id="commentaire"
                            placeholder="Écrivez ici votre commentaire, avis, suggestion... (minimum 10 caractères)"
                            required
                            maxlength="1000"><?= isset($_POST['commentaire']) ? htmlspecialchars($_POST['commentaire']) : '' ?></textarea>
                        <div class="character-count">Maximum 1000 caractères</div>
                    </div>

                    <button type="submit" class="btn">📝 Publier mon commentaire</button>
                    <a href="livre-or.php" class="btn btn-secondary" style="text-decoration: none; text-align: center; display: block;">📖 Retour au livre d'or</a>
                </form>
            </div>
        </main>
    </div>


</body>

</html>