<?php

require "config.php";
$error = "";
$success = "";

if ($_POST) {
    $login = trim($_POST['login']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($login) || empty($password) || empty($confirm_password)) {
        $error = "tous les champs sont obligatoires";
    } elseif ($password !== $confirm_password) {
        $error = "les mots de passe ne sont pas identiques !";
    } else { 
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE login = ?");
        $stmt->execute([$login]);

        if ($stmt->fetch()) {
            $error = "Ce login existe déjà";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO utilisateurs (login, password) VALUES (?, ?)");
            
            if ($stmt->execute([$login, $password_hash])) {
                $success = "Inscription réussie ! Vous pouvez vous connecter.";
            }
            header("Location: login.php");
            exit();

        }
    }
}


?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>

    <h1>Inscription</h1>

    <?php
    if ($error) {
        echo $error;
    }
    if ($success) {
        echo $success;
    }

    ?>

    <form action="" method="post" class="form-example">

        <div class="form-example">
            <label for="login">Entrer votre login : </label>
            <input type="text" name="login" id="login" />
        </div>

        <div class="form-example">
            <label>Entrer votre mot de passe : </label>
            <input type="password" name="password" id="password" />
        </div>

        <div class="form-example">
            <label>Confirmer votre mot de passe : </label>
            <input type="password" name="confirm_password" id="confirm_password" />
        </div>

        <div class="form-example">
            <input type="submit" value="Subscribe!" />
        </div>

    </form>

</body>
</html>
