<?php
session_start();
require "config.php";

// Récupérer tous les commentaires avec les informations des utilisateurs
$stmt = $pdo->prepare("
    SELECT c.id, c.commentaire, c.date, u.login 
    FROM commentaires c 
    JOIN utilisateurs u ON c.id_utilisateur = u.id 
    ORDER BY c.date DESC
");
$stmt->execute();
$commentaires = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Livre d'or - Messages</title>
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
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="profil.php">Mon profil</a></li>
                        <li><a href="commentaire.php">Écrire un message</a></li>
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
            <section class="page-header">
                <h1>Livre d'or</h1>
                <p>Les messages laissés par les visiteurs, du plus récent au plus ancien.</p>
            </section>

            <?php if (isset($_SESSION['user_id'])): ?>
                <section class="add-comment-section">
                    <h2>Laisser un message</h2>
                    <p>Vous êtes connecté : vous pouvez déposer un message.</p>
                    <a href="commentaire.php" class="btn">Écrire un message</a>
                </section>
            <?php else: ?>
                <div class="login-prompt">
                    <p>La lecture est libre. Pour écrire un message,
                        <a href="login.php">connectez-vous</a> ou
                        <a href="register.php">créez un compte</a>.</p>
                </div>
            <?php endif; ?>

            <section class="comments-section">
                <h2 class="comments-title">Messages (<?= count($commentaires) ?>)</h2>

                <?php if (empty($commentaires)): ?>
                    <div class="no-comments">
                        <p>Aucun message pour le moment.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($commentaires as $commentaire): ?>
                        <div class="comment">
                            <div class="comment-meta">
                                Posté le <span class="comment-date"><?= date('d/m/Y à H:i', strtotime($commentaire['date'])) ?></span>
                                par <span class="comment-author"><?= htmlspecialchars($commentaire['login']) ?></span>
                            </div>
                            <div class="comment-text">
                                "<?= nl2br(htmlspecialchars($commentaire['commentaire'])) ?>"
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>

</html>