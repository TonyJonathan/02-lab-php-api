<?php
$method = $_SERVER['REQUEST_METHOD'];

$servername = "mysql:host=mysql";
$username = getenv("MYSQL_USER");
$password_db = getenv("MYSQL_PASSWORD");
$dbname = getenv("MYSQL_DATABASE");

$conn = new PDO("$servername;dbname=$dbname; charset=utf8", $username, $password_db);

switch($method){
    case 'GET': 
        $sql= "SELECT id, name FROM categories";
        $stmt = $conn->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['Status: ' => "success", 'Data: ' => $result]);
        break;
    
    case 'POST': 

        
}

?>