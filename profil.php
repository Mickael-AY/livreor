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

// Récupérer les informations actuelles de l'utilisateur
$stmt = $pdo->prepare("SELECT login FROM utilisateurs WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Récupérer les statistiques de l'utilisateur
$stmt = $pdo->prepare("SELECT COUNT(*) as nb_comments FROM commentaires WHERE id_utilisateur = ?");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();

if ($_POST) {
    $new_login = trim($_POST['login']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($new_login)) {
        $error = "Le nom d'utilisateur ne peut pas être vide";
    } else {
        // Vérifier si le nouveau login est différent de l'actuel
        if ($new_login !== $user['login']) {
            // Vérifier si le nouveau login existe déjà
            $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE login = ? AND id != ?");
            $stmt->execute([$new_login, $_SESSION['user_id']]);

            if ($stmt->fetch()) {
                $error = "Ce nom d'utilisateur est déjà utilisé";
            }
        }

        // Si on veut changer le mot de passe
        if (!empty($new_password) || !empty($current_password) || !empty($confirm_password)) {
            if (empty($current_password)) {
                $error = "Veuillez saisir votre mot de passe actuel";
            } elseif (empty($new_password)) {
                $error = "Veuillez saisir un nouveau mot de passe";
            } elseif ($new_password !== $confirm_password) {
                $error = "Les nouveaux mots de passe ne correspondent pas";
            } elseif (strlen($new_password) < 6) {
                $error = "Le nouveau mot de passe doit contenir au moins 6 caractères";
            } else {
                // Vérifier le mot de passe actuel
                $stmt = $pdo->prepare("SELECT password FROM utilisateurs WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $current_user = $stmt->fetch();

                if (!password_verify($current_password, $current_user['password'])) {
                    $error = "Mot de passe actuel incorrect";
                }
            }
        }

        // Si pas d'erreur, effectuer les modifications
        if (empty($error)) {
            if (!empty($new_password)) {
                // Modifier login et mot de passe
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE utilisateurs SET login = ?, password = ? WHERE id = ?");
                $result = $stmt->execute([$new_login, $password_hash, $_SESSION['user_id']]);
            } else {
                // Modifier seulement le login
                $stmt = $pdo->prepare("UPDATE utilisateurs SET login = ? WHERE id = ?");
                $result = $stmt->execute([$new_login, $_SESSION['user_id']]);
            }

            if ($result) {
                $_SESSION['user_login'] = $new_login;
                $user['login'] = $new_login;
                $success = "Profil mis à jour avec succès !";
            } else {
                $error = "Erreur lors de la mise à jour du profil";
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
    <title>Mon Profil - Livre d'Or</title>
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
            max-width: 900px;
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

        .profile-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }

        .profile-sidebar {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            height: fit-content;
        }

        .profile-main {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .profile-avatar {
            text-align: center;
            margin-bottom: 25px;
        }

        .avatar-icon {
            font-size: 4em;
            background: linear-gradient(45deg, #667eea, #764ba2);
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
        }

        .profile-info h2 {
            color: #667eea;
            margin-bottom: 20px;
            text-align: center;
        }

        .stat-item {
            background: #f8f9ff;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
            border-left: 4px solid #667eea;
        }

        .stat-label {
            font-weight: bold;
            color: #333;
        }

        .stat-value {
            color: #667eea;
            font-size: 1.2em;
        }

        .form-header {
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

        .form-section {
            margin-bottom: 35px;
            padding: 25px;
            background: #f8f9ff;
            border-radius: 15px;
            border-left: 4px solid #667eea;
        }

        .form-section h3 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 1.3em;
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
            padding: 15px 30px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 10px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
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

        .password-info {
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
            .profile-container {
                grid-template-columns: 1fr;
            }

            .nav-links {
                flex-direction: column;
                gap: 10px;
            }

            nav {
                flex-direction: column;
                gap: 20px;
            }

            .form-header h1 {
                font-size: 2em;
            }

            .profile-main {
                padding: 25px;
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
            <div class="profile-container">
                <aside class="profile-sidebar">
                    <div class="profile-avatar">
                        <div class="avatar-icon">👤</div>
                        <h3><?= htmlspecialchars($user['login']) ?></h3>
                    </div>

                    <div class="profile-info">
                        <h2>📊 Mes statistiques</h2>
                        <div class="stat-item">
                            <div class="stat-label">💬 Commentaires postés</div>
                            <div class="stat-value"><?= $stats['nb_comments'] ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">🎯 Statut</div>
                            <div class="stat-value">Membre actif</div>
                        </div>
                    </div>
                </aside>

                <div class="profile-main">
                    <div class="form-header">
                        <h1>⚙️ Mon Profil</h1>
                        <p>Modifiez vos informations personnelles</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="error"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <form action="" method="post">
                        <div class="form-section">
                            <h3>👤 Informations générales</h3>
                            <div class="form-group">
                                <label for="login">Nom d'utilisateur</label>
                                <input type="text" name="login" id="login" value="<?= htmlspecialchars($user['login']) ?>" required>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3>🔒 Changer le mot de passe</h3>
                            <p class="password-info">Laissez ces champs vides si vous ne souhaitez pas modifier votre mot de passe.</p>

                            <div class="form-group">
                                <label for="current_password">Mot de passe actuel</label>
                                <input type="password" name="current_password" id="current_password">
                            </div>

                            <div class="form-group">
                                <label for="new_password">Nouveau mot de passe</label>
                                <input type="password" name="new_password" id="new_password">
                                <div class="password-info">Minimum 6 caractères</div>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                                <input type="password" name="confirm_password" id="confirm_password">
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn">💾 Enregistrer les modifications</button>
                            <a href="livre-or.php" class="btn btn-secondary">📖 Retour au livre d'or</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>

</html>