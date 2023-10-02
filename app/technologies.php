<?php
$method = $_SERVER['REQUEST_METHOD'];

$servername = "mysql:host=mysql";
$username = getenv("MYSQL_USER");
$password_db = getenv("MYSQL_PASSWORD");
$dbname = getenv("MYSQL_DATABASE");

$conn = new PDO("$servername;dbname=$dbname; charset=utf8", $username, $password_db);

switch($method){

    case 'GET': 
        
        $sql = "select technologies.id,  technologies.name as Technology, GROUP_CONCAT(categories.name SEPARATOR ', ') AS Categories  FROM technologies  RIGHT JOIN technologies_categories ON technologies.id = technologies_categories.technology_id  LEFT JOIN categories ON technologies_categories.category_id = categories.id  GROUP BY technologies.id";
        $stmt = $conn->prepare($sql); 
        $stmt->execute(); 
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['Status' => 'succes', 'data' => $result]);
        break;

    case 'POST': 
        echo $method;
        $sql = "INSERT INTO technologies(name) values ('test')";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        break;
}



?>