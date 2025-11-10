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
    <title>Livre d'Or - Commentaires</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px 0;
            margin-bottom: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 30px;
        }

        .logo {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 20px;
        }

        .nav-links a {
            text-decoration: none;
            color: #333;
            padding: 10px 20px;
            border-radius: 25px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-links a:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }

        .page-header {
            text-align: center;
            background: rgba(255, 255, 255, 0.95);
            padding: 40px 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .page-header h1 {
            font-size: 3em;
            color: #667eea;
            margin-bottom: 15px;
        }

        .page-header p {
            color: #666;
            font-size: 1.2em;
        }

        .add-comment-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .comments-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .comments-title {
            font-size: 2em;
            color: #667eea;
            margin-bottom: 30px;
            text-align: center;
        }

        .comment {
            background: #f8f9ff;
            border-left: 4px solid #667eea;
            padding: 25px;
            margin-bottom: 25px;
            border-radius: 10px;
            transition: transform 0.3s ease;
        }

        .comment:hover {
            transform: translateX(5px);
        }

        .comment-meta {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 15px;
            font-weight: 500;
        }

        .comment-author {
            color: #667eea;
            font-weight: bold;
        }

        .comment-date {
            color: #999;
        }

        .comment-text {
            color: #333;
            line-height: 1.6;
            font-size: 1.1em;
        }

        .no-comments {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px;
        }

        .user-status {
            background: rgba(255, 255, 255, 0.9);
            padding: 15px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-online {
            color: #27ae60;
            font-weight: bold;
        }

        .status-offline {
            color: #e74c3c;
        }

        .login-prompt {
            background: #fff3cd;
            color: #856404;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
            border-left: 4px solid #ffc107;
        }

        .login-prompt a {
            color: #667eea;
            text-decoration: none;
            font-weight: bold;
        }

        .login-prompt a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2em;
            }

            .nav-links {
                flex-direction: column;
                gap: 10px;
            }

            nav {
                flex-direction: column;
                gap: 20px;
            }

            .comment {
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <header>
            <nav>
                <a href="index.php" class="logo">📖 Livre d'Or</a>
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
            <section class="page-header">
                <h1>📖 Livre d'Or</h1>
                <p>Découvrez les avis et commentaires de notre communauté</p>
            </section>

            <?php if (isset($_SESSION['user_id'])): ?>
                <section class="add-comment-section">
                    <h2>💭 Partagez votre avis</h2>
                    <p>Votre opinion compte pour nous ! Laissez un commentaire dans notre livre d'or.</p>
                    <a href="commentaire.php" class="btn">Ajouter un commentaire</a>
                </section>
            <?php else: ?>
                <div class="login-prompt">
                    <p>🔒 <strong>Connectez-vous</strong> pour laisser votre commentaire et participer à la discussion !
                        <a href="login.php">Se connecter</a> ou <a href="register.php">créer un compte</a>
                    </p>
                </div>
            <?php endif; ?>

            <section class="comments-section">
                <h2 class="comments-title">💬 Commentaires (<?= count($commentaires) ?>)</h2>

                <?php if (empty($commentaires)): ?>
                    <div class="no-comments">
                        <p>🌟 Aucun commentaire pour le moment. Soyez le premier à laisser votre avis !</p>
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