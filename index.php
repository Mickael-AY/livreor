<?php
session_start();
require "config.php";
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Livre d'Or - Accueil</title>
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
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="profil.php">Mon Profil</a></li>
                        <li><a href="commentaire.php">Ajouter Commentaire</a></li>
                        <li><a href="logout.php">Déconnexion</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Connexion</a></li>
                        <li><a href="register.php">Inscription</a></li>
                    <?php endif; ?>
                </ul>
                <div class="user-status">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span class="status-online">● Connecté en tant que <?= htmlspecialchars($_SESSION['user_login']) ?></span>
                    <?php else: ?>
                        <span class="status-offline">● Non connecté</span>
                    <?php endif; ?>
                </div>
            </nav>
        </header>

        <main>
            <section class="hero">
                <h1>Bienvenue sur le livre d'or</h1>
                <p>Laissez un message aux visiteurs qui passeront ici, et lisez ceux
                    qui ont été déposés avant vous.</p>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="register.php" class="btn">Créer un compte</a>
                <?php else: ?>
                    <a href="livre-or.php" class="btn">Lire les messages</a>
                <?php endif; ?>
            </section>

            <section class="features">
                <div class="feature-card">
                    <h3>Écrire un message</h3>
                    <p>Une fois inscrit, vous pouvez déposer un message de dix caractères
                        minimum. Il apparaît aussitôt en tête du livre.</p>
                </div>
                <div class="feature-card">
                    <h3>Lire sans compte</h3>
                    <p>Le livre d'or est consultable par tout le monde. L'inscription
                        n'est demandée que pour écrire.</p>
                </div>
                <div class="feature-card">
                    <h3>Vos identifiants</h3>
                    <p>Les mots de passe sont hachés avant enregistrement : ils ne sont
                        stockés en clair nulle part, ni lisibles par l'administrateur.</p>
                </div>
            </section>
        </main>
    </div>
</body>

</html>