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

        echo json_encode(['Status: ' => 'success', 'Data: ' => $result]);
        break;
    
        case 'POST':

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                
                if(isset($_POST['name']) && $_POST['name'] !== ""){
                    $name = $_POST['name']; 

                    $checkIfExists = "SELECT COUNT(*) FROM categories WHERE name = :name";
                    $stmtCheck = $conn->prepare($checkIfExists); 
                    $stmtCheck->bindParam(':name', $name, PDO::PARAM_STR);
                    $stmtCheck->execute();
                    $count = $stmtCheck->fetchColumn();

                    if ($count > 0){
                        echo "La catégorie '$name' existe déja."; 
                    } else {
                        
                        $sql = "INSERT INTO categories(name) VALUES (:name)";
                        $stmt = $conn->prepare($sql); 
                        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
                        $stmt->execute();
    
                        echo "La catégorie '$name' a été ajoutée avec succès.";
                    }

                } else {
                 echo "Insérer 'name' dans la clé et le nom de la nouvelle catégorie dans value.";
                }

            }

        break;

        case 'DELETE': 
            // permet de récuperer le contenu brut de la requêtes et de pouvoir utiliser '$_DELETE' qui n'existe pas de base
            parse_str(file_get_contents("php://input"), $_DELETE);

            if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                
                if(isset($_DELETE['id']) && $_DELETE['id'] !== ""){
                    $id = $_DELETE['id']; 

                    $sql = "SELECT name FROM categories WHERE id = :id";
                    $stmt = $conn->prepare($sql); 
                    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                    $stmt->execute();
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($result !== false){

                        $name = $result['name'];
                        
                        $sql = "DELETE FROM categories WHERE id = :id";
                        $stmt = $conn->prepare($sql); 
                        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                        $stmt->execute();

                    echo "La catégorie '$name' a été supprimée avec succès.";

                    } else {
                        echo "Aucune des catégories n'a un identifiant égal à $id.";
                    }

                } else {
                     echo "Insérer 'id' dans la clé et l'id de la catégorie dans value, utilisez la méthode GET pour connaître l'id de la catégorie";
                }

            }

        break;

        case 'PUT': 

            parse_str(file_get_contents("php://input"), $_PUT);

            if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                
                if(isset($_PUT['id']) && $_PUT['id'] !== "" && is_numeric($_PUT['id']) && isset($_PUT['name']) && $_PUT['name'] !== ""){
                    $id = $_PUT['id'];
                    $name = $_PUT['name'];

                    $sql = "SELECT name FROM categories WHERE id = :id";
                    $stmt = $conn->prepare($sql); 
                    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                    $stmt->execute();
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($result !== false){
                    $oldName = $result['name'];
                    $sql = "UPDATE categories SET name = :name where id = :id";
                    $stmt = $conn->prepare($sql); 
                    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
                    $stmt->execute();

                    echo "La catégorie '$oldName' a été rémplacé par '$name'.";

                    } else {
                        echo "Aucune des catégories n'a un identifiant égal à $id.";
                    }

                } else {
                     echo "Insérer 'id' dans la clé et l'id de la catégorie que vous souhaitez modifier dans value, ensuite inserez 'name' comme deuxième clé et le nouveau nom que vous souhaitez donner à la catégorie. Utilisez la méthode GET pour connaître l'id de la catégorie.";
                }
        
            }
        
}

?>


