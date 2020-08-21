<?php
require_once('db.php');
require_once('../model/Departamento.php');
require_once('../model/Response.php');

//Conectar a la base de datos
try{
    $db = DB::conectarDB();
}catch(PDOException $ex){
    $response = new Response();
    $response->setSuccess(false);
    $response->setHttpStatusCode(500);
    $response->addMessage("Error de conexión a la BD");
    $response->send();
    exit;
}

if(array_key_exists("idDepto", $_GET)){
    $idDepto = $_GET['idDepto'];
    if($idDepto == '' || !is_numeric($idDepto)){
        $response = new Response();
        $response->setSuccess(false);
        $response->setHttpStatusCode(400);
        $response->addMessage("Id de departamento no válido");
        $response->send();
        exit;
    }    
}

if($_SERVER['REQUEST_METHOD']==='GET'){
    try {
        $query = $db->prepare('select id_depto, nom_depto from departamentos where id_depto = :idDepto');
        $query->bindParam(':idDepto', $idDepto);
        $query->execute();

        $rowCount = $query->rowCount();

        if($rowCount === 0){
            $response = new Response();
            $response->setSuccess(false);
            $response->setHttpStatusCode(404);
            $response->addMessage("Departamento no encontrado");
            $response->send();
            exit;
        }

        while($row = $query->fetch(PDO::FETCH_ASSOC)){
            $depto = new Departamento($row['id_depto'], $row['nom_depto']);
            $deptoArray[] = $depto->returnDepartamentoAsArray();
        }
        $returnData = array();
        $returnData['nro_filas'] = $rowCount;
        $returnData['deptos'] = $deptoArray;

        $response = new Response();
        $response->setSuccess(true);
        $response->setHttpStatusCode(200);
        $response->setData($returnData);
        $response->send();
        exit;

    } catch (DepartamentoException $ex) {
        $response = new Response();
        $response->setSuccess(false);
        $response->setHttpStatusCode(500);
        $response->addMessage($ex->getMessage());
        $response->send();
        exit;
    }catch(PDOException $ex){
        $response = new Response();
        $response->setSuccess(false);
        $response->setHttpStatusCode(500);
        $response->addMessage("Error conectando a Base de Datos");
        $response->send();
        exit;
    }
}