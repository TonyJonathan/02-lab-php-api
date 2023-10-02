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

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                
                if(isset($_POST['name']) && $_POST['name'] !== ""){
                    $name = $_POST['name']; 

                    $sql = "INSERT INTO categories(name) VALUES (:name)";
                    $stmt = $conn->prepare($sql); 
                    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
                    $stmt->execute();
                    
                } else {
                 echo "Insérer 'name' dans la clé et le nom de la clé dans value";
                }

            }
        break;

        
}

?>