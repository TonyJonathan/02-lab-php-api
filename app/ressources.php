<?php
$method = $_SERVER['REQUEST_METHOD'];

$servername = "mysql:host=mysql";
$username = getenv("MYSQL_USER");
$password_db = getenv("MYSQL_PASSWORD");
$dbname = getenv("MYSQL_DATABASE");

switch($method){
    case 'GET':

        $conn = new PDO("$servername;dbname=$dbname; charset=utf8", $username, $password_db);

        $sql = "SELECT id, technology_id, url FROM ressources";
        $stmt = $conn->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['Status : ' => 'Success', 'Data : ' => $result]);
        break;


}



?>