<?php

require "config.php";
$error = "";
$success = "";

if ($_POST) {
    $login = trim($_POST['login']);
    $password = $_POST['password'];

    if (empty($login) || empty($password)) {
        $error = "tous les champs sont obligatoires";
    } else { 
        $stmt = $pdo->prepare("SELECT id, login, password FROM utilisateurs WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user["password"])) {
            echo "connexion réussie";

            header("Location: index.php");
            exit();

        } else {
            echo "Erreur de connexion";
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

    <h1>Connexion</h1>

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
            <label for="login">Entrer votre nom utilisateur : </label>
            <input type="text" name="login" id="login" />
        </div>

        <div class="form-example">
            <label>Entrer votre mot de passe : </label>
            <input type="password" name="password" id="password" />
        </div>

        <div class="form-example">
            <input type="submit" value="Connexion" />
        </div>

    </form>

</body>
</html>
