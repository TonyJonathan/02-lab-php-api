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

            } else { 
                echo "Veuillez inserer 'id' dans la clé et la valeur de l'identifiant de la technologie concernée, il faut également insérer 'url' dans une autre clé et la valeur contiendra l'url de la ressource.";
            }
        }

    break;


    case 'PUT':

        parse_str(file_get_contents("php://input"), $_PUT);

        if ($_SERVER['REQUEST_METHOD'] === 'PUT'){
            
            if(isset($_PUT['id']) && $_PUT['id'] !== "" && isset($_PUT['url']) && $_PUT['url'] !== ""){
                $id = $_PUT['id'];
                $url = $_PUT['url'];

                $conn = new PDO("$servername;dbname=$dbname; charset=utf8", $username, $password_db); 

                $sql = "SELECT technology_id, url from ressources where id = :id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->execute(); 
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if($result !== false){
                    $urlResult = $result['url'];
                    $idResult = $result['technology_id'];

                    // Récupère le nom de la technologie
                    $sql = "SELECT name FROM technologies where id = :id";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':id', $idResult, PDO::PARAM_INT);
                    $stmt->execute();
                    $technologie = $stmt->fetch(PDO::FETCH_ASSOC);

                    $technologieName = $technologie['name'];

                    $sql = "UPDATE ressources SET url = :url WHERE id = :id";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                    $stmt->bindParam(':url', $url, PDO::PARAM_STR); 
                    $stmt->execute(); 

                    echo "La ressource '$urlResult' de la technologie $technologieName à bien été remplacée par la ressource : '$url' ."; 
                } else {
                    echo "Aucune ressource ne correspond à cet identifiant.";
                }


            } else {
                echo "Veuillez inserer 'id' dans la clé et la valeur de l'identifiant de la ressource concernée, il faut également insérer 'url' dans une autre clé et la valeur contiendra le nouvel url de la ressource que vous souhaitez modifier.";
            }
        }

    case 'DELETE': 

        parse_str(file_get_contents("php://input"), $_DELETE);

        if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            
            if (isset($_DELETE['id']) && $_DELETE['id'] ==! ''){
                $id = $_DELETE['id'];
                
                $conn = new PDO("$servername;dbname=$dbname; charset=utf8", $username, $password_db); 

                $sql = "SELECT technology_id, url from ressources where id = :id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->execute(); 
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if($result !== false){
                    $urlResult = $result['url'];
                    $idResult = $result['technology_id'];

                    // Récupère le nom de la technologie
                    $sql = "SELECT name FROM technologies where id = :id";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':id', $idResult, PDO::PARAM_INT);
                    $stmt->execute();
                    $technologie = $stmt->fetch(PDO::FETCH_ASSOC);

                    $technologieName = $technologie['name'];

                    $sql = "DELETE FROM ressources where id = :id";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                    $stmt->execute(); 

                    echo "La ressource $urlResult de la technologie $technologieName à bien été supprimée."; 
                } else {
                    echo "Aucune technologie ne correspond à cet identifiant.";
                }

            } else {
                echo "Veuillez inserer 'id' dans la clé et la valeur de l'identifiant de la ressource concernée";
            }

        }


}



?>