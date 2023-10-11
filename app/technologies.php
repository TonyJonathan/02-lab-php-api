<?php
$method = $_SERVER['REQUEST_METHOD'];

$servername = "mysql:host=mysql";
$username = getenv("MYSQL_USER");
$password_db = getenv("MYSQL_PASSWORD");
$dbname = getenv("MYSQL_DATABASE");

$conn = new PDO("$servername;dbname=$dbname; charset=utf8", $username, $password_db);


$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';

// Nom du serveur
$server = $_SERVER['HTTP_HOST'];

// Chemin du script
$script_path = $_SERVER['SCRIPT_NAME'];

$uri = $_SERVER['REQUEST_URI'];


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

switch($method){

    case 'GET': 
        
        $sql = "SELECT
        technologies.id,
        technologies.name AS Technologie,
        GROUP_CONCAT(DISTINCT categories.name SEPARATOR ', ') AS Catégories,
        GROUP_CONCAT(DISTINCT ressources.url SEPARATOR ', ') AS Ressources FROM technologies LEFT JOIN technologies_categories ON technologies.id = technologies_categories.technology_id LEFT JOIN categories ON technologies_categories.category_id = categories.id LEFT JOIN ressources ON technologies.id = ressources.technology_id GROUP BY technologies.id";
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

                    $logoPath = '/var/www/html/logo/';

                    // variable qui stocke le chemin temporaire du fichier téléchargé
                    $logoTempPath = $_FILES['logo']['tmp_name']; 

                    // on concatène le chemin jusqu'au dossier logo avec le nom du logo pour avoir l'adresse complete 
                    $logoFullPath = $logoPath . $logoName;

                    // Déplacez le fichier téléchargé vers le dossier spécifié
                    move_uploaded_file($logoTempPath, $logoFullPath);


                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';

                    // Nom du serveur
                    $server = $_SERVER['HTTP_HOST'];
                    
                    // Chemin du script
                    $script_path = $_SERVER['SCRIPT_NAME'];
            
                    $uri = $_SERVER['REQUEST_URI'];
                    
                    // Combiner les parties pour obtenir l'URL complète
                    $url = $protocol . '://' . $server . $uri . '/logo' . '/' . $logoName ;
            
                 

                    // on créer la technologie
                    $sql = "INSERT INTO technologies(name, logo_name, logo_path, url_logo) VALUES (:name, :logoName, :logoPath, :url)";
                    $stmt = $conn->prepare($sql); 
                    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
                    $stmt->bindParam(':logoName', $logoName, PDO::PARAM_STR);
                    $stmt->bindParam(':logoPath', $logoFullPath, PDO::PARAM_STR);
                    $stmt->bindParam(':url', $url, PDO::PARAM_STR); 
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
                echo "Insérer 'name' dans la clé, le nom de la nouvelle technologie en value. Insérer également 'catégories' en dans une autre clé et ajouter la ou les identifiants des catégories à associer dans value (exemple de value: 1,3,8). La 3e et dernière clé a inserer est 'logo', suivie de la value qui sera un fichier présent dans le dossier logo";
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



                if($result){

                    $nameResult = $result['name'];
                    $idResult = $result['id'];

                    // Vérifie si un logo existe déja 
                    $sql = 'SELECT logo_name FROM technologies WHERE name = :name';
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':name', $name, PDO::PARAM_STR); 
                    $stmt->execute();
                    $logoResult = $stmt->fetch(PDO::FETCH_ASSOC);


                    // si l'on modifier ou ajouter un logo sans modifier le nom
                    if(isset($_SERVER['CONTENT_TYPE']) && $_SERVER['HTTP_NEWNAME'] == ""){
                        
                        // Dans le cas ou un logo serait déja présent, on le supprime en amont
                        if($logoResult['logo_name'] !== null){
                            $logoName = $logoResult['logo_name'];
                            $logoOldPath = '/var/www/html/logo/' . $logoName;
                            unlink($logoOldPath);
                        }

                        // Récupère le logo dans binary
                        $input = file_get_contents("php://input");

                        // Défini l'extension du logo grâce à la fonction getExtensionFromMimeType en haut du fichier
                        $file_extension = isset($_SERVER['CONTENT_TYPE']) ? getExtensionFromMimeType($_SERVER['CONTENT_TYPE']) : 'png';

                        // défini le nouveau nom du logo et son nouveau chemin 
                        $newLogoName = $name . "." . $file_extension;
                        $logoPath = '/var/www/html/logo/' . $newLogoName;

                        // insert le nouveau logo dans l'environnement docker 
                        file_put_contents($logoPath, $input); 

                        echo "L'image '$newLogoName' a été reçue avec succès. \n";

                        // Combiner les parties pour obtenir l'URL complète
                        $url = $protocol . '://' . $server . $uri . '/logo' . '/' . $newLogoName ;

                        // Modifie les différentes infos liées au logo dans la base de données
                        $sql = 'UPDATE technologies set logo_name = :logoName, logo_path = :logoPath, url_logo = :url WHERE name = :name'; 
                        $stmt = $conn->prepare($sql); 
                        $stmt->bindParam(':logoName', $newLogoName, PDO::PARAM_STR);
                        $stmt->bindParam(':logoPath', $logoPath, PDO::PARAM_STR);
                        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
                        $stmt->bindParam(':url', $url, PDO::PARAM_STR); 
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

                       
                        // Dans le cas ou un logo existe, et que l'on modifie le nom sans modifier le logo
                        if($logoResult['logo_name'] !== null && !isset($_SERVER['CONTENT_TYPE'])){

                            // récupere l'ancien nom du logo et son chemin
                            $logoName = $logoResult['logo_name'];
                            $logoOldPath = '/var/www/html/logo/' . $logoName;
                            
                            // récupere l'extension 
                            $logoNameParts = explode('.', $logoName);

                            $logoNameExtension = $logoNameParts[1]; 

                            // Change l'ancien nom/chemin/url par le nouveau avec la meme extension
                            $logoNewName = $new_name . '.' . $logoNameExtension;

                            $logoNewPath = '/var/www/html/logo/' . $logoNewName;
                            $url = $protocol . '://' . $server . $uri . '/logo' . '/' . $logoNewName ;

                            //Insert les modifications dans la base de données 
                            $sql = "UPDATE technologies SET logo_name = :logoName, logo_path = :logoPath, url_logo = :url WHERE name = :name"; 
                            $stmt = $conn->prepare($sql);
                            $stmt->bindParam(':logoName', $logoNewName, PDO::PARAM_STR);
                            $stmt->bindParam(':logoPath', $logoNewPath, PDO::PARAM_STR);
                            $stmt->bindParam(':name', $new_name, PDO::PARAM_STR); 
                            $stmt->bindParam(':url', $url, PDO::PARAM_STR); 
                            $stmt->execute();

                            echo "Logo_name, logo_path et url_logo ont été renommés dans la base de données. \n";

                            // Modifie le nom du fichier dans l'environnement docker 
                            if(file_exists($logoOldPath)){

                                if(rename($logoOldPath, $logoNewPath)){

                                    echo "Le fichier à été renommé avec succès.\n";
                                    
                                } else {
                                    echo "Erreur lors du renommage du fichier. \n";

                                }
                            } else {
                                echo "Aucun logo pour cette technologie. \n";
                            }

                            // Dans le cas ou un logo existe, que l'on modifie le nom et le logo (dans binary)
                        } else if($logoResult['logo_name'] !== null && isset($_SERVER['CONTENT_TYPE'])){

                            // récupère le nouveau fichier
                            $input = file_get_contents("php://input");

                            // défini son extension grâce a la fonction 
                            $file_extension = isset($_SERVER['CONTENT_TYPE']) ? getExtensionFromMimeType($_SERVER['CONTENT_TYPE']) : 'png';

                            // Ancien nom/chemin
                            $logoName = $logoResult['logo_name'];
                            $logoOldPath = '/var/www/html/logo/' . $logoName;

                            // nouveau nom/chelin
                        
                            $logoNewName = $new_name . '.' . $file_extension;

                            $logoNewPath = '/var/www/html/logo/' . $logoNewName;
            
                            
                            file_put_contents($logoOldPath, $input); 

                            echo "L'image '$logoNewName' a été reçue avec succès. \n";

                            // Combiner les parties pour obtenir l'URL complète
                            $url = $protocol . '://' . $server . $uri . '/logo' . '/' . $logoNewName ;

                            $sql = 'UPDATE technologies set logo_name = :logoName, logo_path = :logoPath, url_logo = :url WHERE name = :name'; 
                            $stmt = $conn->prepare($sql); 
                            $stmt->bindParam(':logoName', $logoNewName, PDO::PARAM_STR);
                            $stmt->bindParam(':logoPath', $logoNewPath, PDO::PARAM_STR);
                            $stmt->bindParam(':name', $new_name, PDO::PARAM_STR);
                            $stmt->bindParam(':url', $url, PDO::PARAM_STR); 
                            $stmt->execute(); 

                            rename($logoOldPath, $logoNewPath);

                            echo "Le fichier à été renommé avec succès.\n";
                      
                        } else if($logoResult['logo_name'] == null && isset($_SERVER['CONTENT_TYPE'])) {

                        // Récupère le logo dans binary
                        $input = file_get_contents("php://input");

                        // Défini l'extension du logo grâce à la fonction getExtensionFromMimeType en haut du fichier
                        $file_extension = isset($_SERVER['CONTENT_TYPE']) ? getExtensionFromMimeType($_SERVER['CONTENT_TYPE']) : 'png';

                        // défini le nouveau nom du logo et son nouveau chemin 
                        $newLogoName = $new_name . "." . $file_extension;
                        $logoPath = '/var/www/html/logo/' . $newLogoName;

                        // insert le nouveau logo dans l'environnement docker 
                        file_put_contents($logoPath, $input); 

                        echo "L'image '$newLogoName' a été reçue avec succès. \n";

                        // Combiner les parties pour obtenir l'URL complète
                        $url = $protocol . '://' . $server . $uri . '/logo' . '/' . $newLogoName ;

                        // Modifie les différentes infos liées au logo dans la base de données
                        $sql = 'UPDATE technologies set logo_name = :logoName, logo_path = :logoPath, url_logo = :url WHERE name = :name'; 
                        $stmt = $conn->prepare($sql); 
                        $stmt->bindParam(':logoName', $newLogoName, PDO::PARAM_STR);
                        $stmt->bindParam(':logoPath', $logoPath, PDO::PARAM_STR);
                        $stmt->bindParam(':name', $new_name, PDO::PARAM_STR);
                        $stmt->bindParam(':url', $url, PDO::PARAM_STR); 
                        $stmt->execute(); 
                        }
                    }
                } else {
                    echo "La technologie '$name' n'existe pas.";
                }
            } else {
                echo "Dans Headers une clé 'name' et sa valeur représentant le nom d'une technologie existante dans la base de données.\n";
                echo "Pour changer le nom d'une technologie il faut rajouter une clé 'newname' ainsi que sa valeur représentant le nouveau nom que l'on souhaite donner à la technologie. \n";
                echo "Pour lier des categories à une technologie, inserez la clé 'categories' et comme value rentrez l'ensemble des catégories que vous souhaitez voir associées à la technologie sous cette forme ex: 2,8,10,11. \n"; 
                echo "Pour ajouter un logo à la technologie, il faut sélectionner un fichier dans Body > binary. \n";
            }
        } 
    break;

    case 'DELETE':

        // permet de récuperer le contenu brut de la requêtes et de pouvoir utiliser '$_DELETE' qui n'existe pas de base
        parse_str(file_get_contents("php://input"), $_DELETE);

        if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            
            if(isset($_DELETE['name']) && $_DELETE['name'] !== ""){
                
                $name = $_DELETE['name']; 

                $sql = "SELECT id, name, logo_path FROM technologies WHERE name = :name";
                $stmt =$conn->prepare($sql);
                $stmt->bindParam(':name', $name, PDO::PARAM_STR); 
                $stmt->execute(); 

                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                if($result){

                    $id = $result['id']; 
                    
                    if(isset($_DELETE['logo']) || isset($_DELETE['all'])){
                        
                        if($result['logo_path'] !== null && $result['logo_path'] !== ""){
                            $logoPath = $result['logo_path']; 

                            if(file_exists($logoPath)){
                                if(unlink($logoPath)){
                                    echo "Le logo lié à la technologie $name à bien été supprimé. \n";
                                } else {
                                    echo "Erreur lors de la suppression du logo. \n";
                                }
                            }

                            $sql = "UPDATE technologies SET logo_name = NULL, logo_path = NULL, url_logo = NULL WHERE name = :name";
                            $stmt = $conn->prepare($sql); 
                            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
                            $stmt->execute();

                        } else {
                            echo "Aucun logo n'est lié à la technologie $name. \n";
                        } 
                    }

                    if(isset($_DELETE['categories']) && $_DELETE['categories'] !== ""){


                        $categories = $_DELETE['categories']; 
                        $arrayIdCategory = explode(',', $categories);

                        

                        foreach($arrayIdCategory as $categoryId){

                            $sql = "SELECT categories.name FROM technologies RIGHT JOIN technologies_categories ON technologies.id = technologies_categories.technology_id LEFT JOIN categories ON technologies_categories.category_id = categories.id WHERE technologies.name = :name AND categories.id = :id";
                            $stmt = $conn->prepare($sql);
                            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
                            $stmt->bindParam(':id', $categoryId, PDO::PARAM_INT);
                            $stmt->execute();
                            $resultId = $stmt->fetch(PDO::FETCH_ASSOC);

                            if($resultId){
                                $nameResult = $resultId['name'];
                                $sql = "DELETE FROM technologies_categories WHERE technology_id = :technology_id AND category_id = :category_id";
                                $stmt = $conn->prepare($sql);
                                $stmt->bindParam(':technology_id', $id, PDO::PARAM_INT);
                                $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
                                $stmt->execute();

                                echo "La catégorie '$nameResult' n'est plus lié à la technologie '$name'. \n";

                            } else {
                                echo "La technologie '$name' n'est pas liée à une catégories dont l'identifiant est '$categoryId'. \n"; 
                            }      
                              
                        }

                    }

                    
                if(isset($_DELETE['all'])){
                    $sql = "DELETE FROM technologies WHERE name = :name";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
                    $stmt->execute(); 

                    echo "La technologie '$name' à complètement été supprimée. \n";
                }
                    
                } else {
                    echo "Il n'existe pas de technologies portant le nom $name. \n";
                }


            } else {
                echo "Inserez la clé 'name' ainsi que le nom de la technologie en valeur. \n";
                echo "Si vous souhaitez supprimer des liaisons avec les catégories, inserez la clé 'catégorie' et ajoutez en valeur le ou les identifiants des différentes catégories, ex : 2,3,8,10. \n";
                echo "Si vous souhaitez supprimer le logo de la technologie, inserez simplement la clé 'logo', sans valeur. \n";
                echo "Si vous souhaitez supprimer la technologie dans son ensemble, inserez la clé 'all', sans valeur. \n";
            }
        } 
}
// url_logo prend le nom du vrai fichier et pas le nom de la tehcnologie, il faut faire le sql pour avoir le chemin dans l'api 
?>

