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

    case 'POST': 

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                
            if(isset($_POST['id']) && $_POST['id'] !== "" && isset($_POST['url']) && $_POST['url'] !== ""){
                $url = $_POST['url'];
                $technology_id = $_POST['id']; 
         
                $conn = new PDO("$servername;dbname=$dbname; charset=utf8", $username, $password_db);

                $sql = "SELECT name FROM technologies where id = :id "; 
                $stmt = $conn->prepare($sql); 
                $stmt->bindParam(':id', $technology_id, PDO::PARAM_INT);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC); 
                

                if ($result !== false && $result !== null){
                    $nameResult = $result['name'];
                    $sql = "INSERT INTO ressources(technology_id, url) VALUES (:id, :url)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':id', $technology_id, PDO::PARAM_INT);
                    $stmt->bindPAram(':url', $url, PDO::PARAM_STR);
                    $stmt->execute();

                    echo "L'url à bien été ajouté à $nameResult.";
                } else {
                    echo "Cet identifiant ne correspond à aucune technologie.";
                }

            }
        }
}



?>