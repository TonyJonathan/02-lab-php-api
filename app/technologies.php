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

            if(isset($_POST['name']) && $_POST['name'] !== "" && isset($_POST['categories']) && $_POST['categories'] !== "" && isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK){
                $name = $_POST['name']; 
                $idCategories = $_POST['categories']; 

                $checkIfExists = "SELECT COUNT(*) FROM technologies WHERE name = :name";
                $stmtCheck = $conn->prepare($checkIfExists); 
                $stmtCheck->bindParam(':name', $name, PDO::PARAM_STR);
                $stmtCheck->execute();
                $count = $stmtCheck->fetchColumn();

                if ($count > 0){
                    echo "La technologie '$name' existe déja."; 
                } else {
                    
                    $logoName = $_FILES['logo']['name']; 
                    $logoPath = '/var/www/html/logo/';

                    // variable qui stocke le chemin temporaire du fichier téléchargé
                    $logoTempPath = $_FILES['logo']['tmp_name']; 

                    // on concatène le chemin jusqu'au dossier logo avec le nom du logo pour avoir l'adresse complete 
                    $logoFullPath = $logoPath . $logoName;

                    // Déplacez le fichier téléchargé vers le dossier spécifié
                    move_uploaded_file($logoTempPath, $logoFullPath);

                    // on créer la technologie
                    $sql = "INSERT INTO technologies(name, logo_name, logo_path) VALUES (:name, :logoName, :logoPath)";
                    $stmt = $conn->prepare($sql); 
                    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
                    $stmt->bindParam(':logoName', $logoName, PDO::PARAM_STR);
                    $stmt->bindParam(':logoPath', $logoFullPath, PDO::PARAM_STR);
                    $stmt->execute();

                    echo "Vous avez ajouter le logo '$logoName' \n";

                    // On récupere son ID
                    $sql = "SELECT id FROM technologies WHERE name = :name"; 
                    $stmt = $conn->prepare($sql); 
                    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
                    $stmt->execute();

                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $technology_id = $result['id']; 

                    echo "La technologie '$name' a été ajoutée avec succès.\n";

                    // Divise la chaine en tableau d'id 
                    $arrayIdCategory = explode(',', $idCategories);
                    
                    // On effectue un foreach pour avoir chaque id individuellement 

                    foreach($arrayIdCategory as $rowId){
                        $sql = "SELECT name FROM categories where id in (:rowId)";

                        $stmt = $conn->prepare($sql); 
                        $stmt->bindParam(':rowId', $rowId, PDO::PARAM_INT);
                        $stmt->execute();
                        $nameResult = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        // Vérification de l'existence de la catégorie (en fonction de si son id renvoie une valeur valeur name)
                        if($nameResult){
                            // Existance vérifiée, on associe l'id du nom de la technologie créee aux catégories sélectionnées existante
                            $sql = "INSERT INTO technologies_categories (technology_id, category_id) VALUES (:technology_id,:category_id)";
                            $stmt = $conn->prepare($sql);
                            $stmt->bindParam(':technology_id', $technology_id, PDO::PARAM_INT); 
                            $stmt->bindParam(':category_id', $rowId, PDO::PARAM_INT); 
                            $stmt->execute(); 

                            $categoryName = $nameResult['name']; 

                            echo "La catégorie '$categoryName' est maintenant associée à '$name'.\n"; 

                        } else {
                            // si l'id rentré n'a pas de name dans le tableau
                            echo "L'identifiant $rowId ne correspond à aucune catégorie.\n";
                        }
                    }   
                }
              
            } else {
                echo "Insérer 'name' dans la clé, le nom de la nouvelle technologie en value. Insérer également 'id' en dans une autre clé et ajouter la ou les identifiants des catégories à associer dans value (exemple de value: 1,3,8). La 3e et dernière clé a inserer est 'logo', suivie de la value qui sera un fichier présent dans le dossier logo";
            }

        break;

        case 'PUT' : 

            if ($_SERVER['REQUEST_METHOD'] === 'PUT') {

                if(isset($_SERVER['HTTP_NAME']) && $_SERVER['HTTP_NAME'] !== "" ){
                    $name = $_SERVER['HTTP_NAME'];
                    
                    // verifie que le nom rentré existe bien
                    $sql = "SELECT name, id FROM technologies where name = :name";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
                    $stmt->execute();

                    $result = $stmt->fetch(PDO::FETCH_ASSOC);

                    $nameResult = $result['name'];
                    $idResult = $result['id'];

                if($nameResult){


                    if(isset($_SERVER['HTTP_CATEGORIES']) && $_SERVER['HTTP_CATEGRORIES'] !== ""){

                        $newCategories = $_SERVER['HTTP_CATEGRORIES']; 
                        $arrayIdCategory = explode(',', $newCategories);

                        $sql = "select categories.id from technologies right join technologies_categories on technologies.id = technologies_categories.technology_id left join categories on technologies_categories.category_id = categories.id where technologies.name = ':name'";
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
                        $stmt->execute();

                        $categoriesId = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        

                        foreach($categoriesId as $categoryId){
                            $sql = "DELETE FROM technologies_categories WHERE technology_id = :technology_id AND category_id = :category_id";
                            $stmt = $conne->prepare($sql);
                            $stmt->bindParam(':technology_id', $idResult, PDO::PARAM_INT);
                            $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
                            $stmt->execute();
                        }

                        foreach($arrayIdCategory as $rowId){
                            $sql = "SELECT name FROM categories where id in (:rowId)";
    
                            $stmt = $conn->prepare($sql); 
                            $stmt->bindParam(':rowId', $rowId, PDO::PARAM_INT);
                            $stmt->execute();
                            $nameResult = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            // Vérification de l'existence de la catégorie (en fonction de si son id renvoie une valeur valeur name)
                            if($nameResult){
                                // Existance vérifiée, on associe l'id du nom de la technologie créee aux catégories sélectionnées existante
                                $sql = "INSERT INTO technologies_categories (technology_id, category_id) VALUES (:technology_id,:category_id)";
                                $stmt = $conn->prepare($sql);
                                $stmt->bindParam(':technology_id', $technology_id, PDO::PARAM_INT); 
                                $stmt->bindParam(':category_id', $rowId, PDO::PARAM_INT); 
                                $stmt->execute(); 
    
                                $categoryName = $nameResult['name']; 
    
                                echo "La catégorie '$categoryName' est maintenant associée à '$name'.\n"; 
    
                            } else {
                                // si l'id rentré n'a pas de name dans le tableau
                                echo "L'identifiant $rowId ne correspond à aucune catégorie.\n";
                            }
                    }

                }
                }
             
            }

            // }

          
            
        break;
        }
}

?>