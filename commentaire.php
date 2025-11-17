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
            max-width: 800px;
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

        .form-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .form-header h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 2.5em;
        }

        .form-header p {
            color: #666;
            font-size: 1.1em;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #333;
            font-weight: 500;
            font-size: 1.1em;
        }

        .form-group textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e1e1;
            border-radius: 10px;
            font-size: 16px;
            font-family: inherit;
            resize: vertical;
            min-height: 150px;
            transition: all 0.3s ease;
        }

        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            margin-top: 10px;
        }

        .btn-secondary:hover {
            background: #5a6268;
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.4);
        }

        .error {
            background: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c62828;
        }

        .success {
            background: #e8f5e8;
            color: #2e7d32;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #2e7d32;
        }

        .user-info {
            background: #e3f2fd;
            color: #1565c0;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #1976d2;
        }

        .character-count {
            text-align: right;
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
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

        @media (max-width: 768px) {
            .nav-links {
                flex-direction: column;
                gap: 10px;
            }

            nav {
                flex-direction: column;
                gap: 20px;
            }

            .form-container {
                padding: 25px;
            }

            .form-header h1 {
                font-size: 2em;
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
                    <h1>💭 Ajouter un commentaire</h1>
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