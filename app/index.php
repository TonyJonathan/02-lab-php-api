<?php

// Le contenu est JSON avec encodage UTF-8
header('Content-Type: application/json; charset=utf-8');
// Autorise toutes les domaines à accéder à l'API
header('Access-Control-Allow-Origin: *');
// Définit les méthodes HTTP autorisées (GET, POST, PUT, DELETE)
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
// Définit les en-têtes HTTP autorisés 
header('Access-Control-Allow-Headers: Content-Type, API-Key, Name, Categories');

// Récupère la méthode HTTP (GET, POST, PUT, DELETE)
$method = $_SERVER['REQUEST_METHOD'];

// Divise l'URI en segments
$uri = explode('/', trim($_SERVER['REQUEST_URI'], '/'));


 // Établir une connexion à la base de données avec PDO
 $servername = "mysql:host=mysql";
 $username = getenv("MYSQL_USER");
 $password_db = getenv("MYSQL_PASSWORD");
 $dbname = getenv("MYSQL_DATABASE");
 
 $conn = new PDO("$servername;dbname=$dbname; charset=utf8", $username, $password_db);


// Routeur
 switch($uri[0]){

    case 'technologies':
        return require("technologie.php");
        break;

    case 'categories': 
        return require('categories.php'); 
        break;

    case 'ressources': 
        return require('ressources.php');
        break;
 }




?>