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
            max-width: 1200px;
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

        .hero {
            text-align: center;
            background: rgba(255, 255, 255, 0.95);
            padding: 60px 30px;
            border-radius: 20px;
            margin-bottom: 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .hero h1 {
            font-size: 3.5em;
            margin-bottom: 20px;
            color: #667eea;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .hero p {
            font-size: 1.3em;
            color: #666;
            max-width: 600px;
            margin: 0 auto 30px;
            line-height: 1.6;
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

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px 30px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-card h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.5em;
        }

        .feature-card p {
            color: #666;
            line-height: 1.6;
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

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5em;
            }

            .nav-links {
                flex-direction: column;
                gap: 10px;
            }

            nav {
                flex-direction: column;
                gap: 20px;
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
            <section class="hero">
                <h1>Bienvenue sur notre Livre d'Or</h1>
                <p>Partagez vos impressions, laissez vos commentaires et découvrez ce que pensent les autres visiteurs de notre site. Votre avis compte pour nous !</p>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="register.php" class="btn">Rejoignez-nous maintenant</a>
                <?php else: ?>
                    <a href="livre-or.php" class="btn">Voir le Livre d'Or</a>
                <?php endif; ?>
            </section>

            <section class="features">
                <div class="feature-card">
                    <h3>📝 Partagez vos avis</h3>
                    <p>Exprimez-vous librement et partagez votre expérience avec la communauté. Chaque commentaire est précieux pour nous.</p>
                </div>
                <div class="feature-card">
                    <h3>👥 Communauté active</h3>
                    <p>Rejoignez une communauté dynamique d'utilisateurs qui partagent leurs opinions et leurs expériences.</p>
                </div>
                <div class="feature-card">
                    <h3>🔒 Sécurisé et fiable</h3>
                    <p>Vos données sont protégées et votre confidentialité est notre priorité. Inscrivez-vous en toute sécurité.</p>
                </div>
            </section>
        </main>
    </div>
</body>

</html>