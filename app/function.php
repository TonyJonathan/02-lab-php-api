<?php 

function ifCategoryExist($id){
    $sql = "SELECT name FROM categories WHERE id = :id";
    $stmt = $conn->prepare($sql); 
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result !== false){
        return true;
    } else {
        return false;
    }

}

?>