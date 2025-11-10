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
    <title>Inscription - Livre d'Or</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            width: 100%;
            max-width: 400px;
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
            font-size: 1em;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e1e1;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
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

        .error {
            background: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c62828;
        }

        .success {
            background: #e8f5e8;
            color: #2e7d32;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #2e7d32;
        }

        .form-links {
            text-align: center;
            margin-top: 20px;
        }

        .form-links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .form-links a:hover {
            text-decoration: underline;
        }

        .back-home {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            text-decoration: none;
            font-weight: bold;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .back-home:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .password-info {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>

<body>
    <a href="index.php" class="back-home">← Retour à l'accueil</a>

    <div class="form-container">
        <div class="form-header">
            <h1>📝</h1>
            <h1>Inscription</h1>
            <p>Créez votre compte pour rejoindre notre communauté</p>
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
</body>

</html>