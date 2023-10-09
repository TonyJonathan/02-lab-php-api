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


                    if(isset($_SERVER['CONTENT_TYPE'])){
                        $input = file_get_contents("php://input");

                        function getExtensionFromMimeType($mimeType) {
                            // Logique pour mapper les types de contenu MIME à des extensions de fichiers
                            $mimeToExtensionMap = array(
                                'image/jpeg' => 'jpg',
                                'image/png'  => 'png',
                                'image/gif'  => 'gif',
                                'image/webp' => 'webp',
                                // Ajoutez d'autres mappages au besoin
                            );
                        
                            // Recherche de l'extension dans le tableau
                            return isset($mimeToExtensionMap[$mimeType]) ? $mimeToExtensionMap[$mimeType] : 'png';
                        }
                        
                    
                        $file_extension = isset($_SERVER['CONTENT_TYPE']) ? getExtensionFromMimeType($_SERVER['CONTENT_TYPE']) : 'png';

                        $logoName = $name . "." . $file_extension;
                        $logoPath = '/var/www/html/logo/' . $logoName;

                        file_put_contents($logoPath, $input); 

                        echo "L'image '$logoName' a été reçue avec succès. \n";

                        $sql = 'UPDATE technologies set logo_name = :logoName, logo_path = :logoPath WHERE name = :name'; 
                        $stmt = $conn->prepare($sql); 
                        $stmt->bindParam(':logoName', $logoName, PDO::PARAM_STR);
                        $stmt->bindParam(':logoPath', $logoPath, PDO::PARAM_STR);
                        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
                        $stmt->execute(); 
                    }


                    if(isset($_SERVER['HTTP_CATEGORIES']) && $_SERVER['HTTP_CATEGORIES'] !== ""){

                        $newCategories = $_SERVER['HTTP_CATEGORIES']; 
                        $arrayIdCategory = explode(',', $newCategories);

                        $sql = "select categories.id from technologies right join technologies_categories on technologies.id = technologies_categories.technology_id left join categories on technologies_categories.category_id = categories.id where technologies.name = :name";
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
                        $stmt->execute();

                        $categoriesId = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        

                        foreach($categoriesId as $categoryIdArray){

                            foreach($categoryIdArray as $categoryId){
                        
                                $sql = "DELETE FROM technologies_categories WHERE technology_id = :technology_id AND category_id = :category_id";
                                $stmt = $conn->prepare($sql);
                                $stmt->bindParam(':technology_id', $idResult, PDO::PARAM_INT);
                                $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
                                $stmt->execute();
                                }
                            
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
                                $stmt->bindParam(':technology_id', $idResult, PDO::PARAM_INT); 
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



                    // modifie le nom actuelle par le nouveau
            
                    if(isset($_SERVER['HTTP_NEWNAME']) && $_SERVER['HTTP_NEWNAME'] !== "" ){

                        $new_name = $_SERVER['HTTP_NEWNAME'];

                        $sql = "UPDATE technologies set name = :newname where name = :name";
                        $stmt = $conn->prepare($sql); 
                        $stmt->bindParam(':newname', $new_name, PDO::PARAM_STR);
                        $stmt->bindParam(':name', $name, PDO::PARAM_STR); 
                        $stmt->execute();

                        echo "$name à bien été modifié par $new_name.\n";

                        $sql = 'SELECT logo_name FROM technologies WHERE name = :name';
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(':name', $new_name, PDO::PARAM_STR); 
                        $stmt->execute();
                        $logoResult = $stmt->fetch(PDO::FETCH_ASSOC);

                        if($logoResult['logo_name'] !== null){

                            
                            $logoName = $logoResult['logo_name'];
                            $logoOldPath = '/var/www/html/logo/' . $logoName;

                            echo "$logoName \n";
                            echo "$logoOldPath \n"; 

                            $logoNameParts = explode('.', $logoName);

                            $logoNameExtension = $logoNameParts[1]; 

                            $logoNewName = $new_name . '.' . $logoNameExtension;

                            $logoNewPath = '/var/www/html/logo/' . $logoNewName;

                            echo "$logoNewName \n";
                            echo "$logoNewPath \n";

                            $sql = "UPDATE technologies SET logo_name = :logoName, logo_path = :logoPath WHERE name = :name"; 
                            $stmt = $conn->prepare($sql);
                            $stmt->bindParam(':logoName', $logoNewName, PDO::PARAM_STR);
                            $stmt->bindParam(':logoPath', $logoNewPath, PDO::PARAM_STR);
                            $stmt->bindParam(':name', $new_name, PDO::PARAM_STR); 
                            $stmt->execute();

                            echo "Logo_name et logo_path ont été renommés dans la base de données. \n";

                            if(file_exists($logoOldPath)){

                                if(rename($logoOldPath, $logoNewPath)){

                                    echo "Le fichier à été renommé avec succès.\n";
                                    
                                } else {
                                    echo "Erreur lors du renommage du fichier. \n";

                                }
                            } else {
                                echo "Aucun logo pour cette technologie. \n";
                            }
                        }
                    }
                }
            }
        } 
    break;

    case 'DELETE':

        // permet de récuperer le contenu brut de la requêtes et de pouvoir utiliser '$_DELETE' qui n'existe pas de base
        parse_str(file_get_contents("php://input"), $_DELETE);

        if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            
            if(isset($_DELETE['name']) && $_DELETE['name'] !== ""){
                $name = $_DELETE['name']; 

                $sql = "SELECT name, logo_path FROM technologies WHERE name = :name";
                $stmt =$conn->prepare($sql);
                $stmt->bindParam(':name', $name, PDO::PARAM_STR); 
                $stmt->execute(); 

                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                if($result){
                    
                    if(isset($_DELETE['logo'])){
                        
                        if($result['logo_path'] !== null || $result['logo_path'] !== ""){
                            $logoPath = $result['logo_path']; 

                            

                            if(file_exists($logoPath)){
                                if(unlink($logoPath)){
                                    echo "Le logo lié à la technologie $name à bien été supprimé. \n";
                                } else {
    
                                    echo "Erreur lors de la suppression du logo. \n";
                                }
                            }

                            $sql = "UPDATE technologies SET logo_name = NULL, logo_path = NULL WHERE name = :name";
                            $stmt = $conn->prepare($sql); 
                            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
                            $stmt->execute();

                        } else {
                            echo "Aucun logo n'est lié à la technologie $name. \n";
                        } 
                    }
                    
                    
                } else {
                    echo "Il n'existe pas de technologies portant le nom $name. \n";
                }
            }
        }
}

?>