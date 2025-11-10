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
    </style>
</head>

<body>
    <a href="index.php" class="back-home">← Retour à l'accueil</a>

    <div class="form-container">
        <div class="form-header">
            <h1>📖</h1>
            <h1>Connexion</h1>
            <p>Connectez-vous pour accéder au livre d'or</p>
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
</body>

</html>