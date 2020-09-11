<?php
header('Access-Control-Allow-Origin: *');
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
    if(array_key_exists("idDepto", $_GET)){
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
    }else{
        try{
            $query = $db->prepare('select id_depto, nom_depto from departamentos');
            $query->execute();

            $rowCount = $query->rowCount();
            $deptosArray = array();

            while($row = $query->fetch(PDO::FETCH_ASSOC)){
                $depto = new Departamento($row['id_depto'], $row['nom_depto']);
                $deptosArray[] = $depto->returnDepartamentoAsArray();
            }

            $returnData = array();
            $returnData['filas_retornadas'] = $rowCount;
            $returnData['deptos'] = $deptosArray;

            $response = new Response();
            $response->setSuccess(true);
            $response->setHttpStatusCode(200);
            $response->toCache(true);
            $response->setData($returnData);
            $response->send();
            exit;

        }catch(DepartamentoException $ex){
            $response = new Response();
            $response->setSuccess(false);
            $response->setHttpStatusCode(400);
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
}elseif($_SERVER['REQUEST_METHOD']==='DELETE'){
    try{
        //Validar que el departamento no tenga ciudades relacionadas
        $query = $db->prepare('select count(*) as conteo from ciudades where id_depto = :idDepto ');
        $query->bindParam(':idDepto', $idDepto);
        $query->execute();

        while($row = $query->fetch(PDO::FETCH_ASSOC)){
            $numRows = $row['conteo'];            
        }

        if($numRows > 0){
            $response = new Response();
            $response->setSuccess(false);
            $response->setHttpStatusCode(500);
            $response->addMessage('No es posible eliminar departamento. Ciudades asociadas');
            $response->send();
            exit();
        }

        $query = $db->prepare('delete from departamentos where id_depto = :idDepto');
        $query->bindParam(':idDepto', $idDepto);
        $query->execute();

        $rowCount = $query->rowCount();

        if($rowCount===0){
            $response = new Response();
            $response->setSuccess(false);
            $response->setHttpStatusCode(404);
            $response->addMessage('Departamento no encontrado');
            $response->send();
            exit();
        }
        $response = new Response();
        $response->setSuccess(true);
        $response->setHttpStatusCode(200);
        $response->addMessage('Departamento eliminado');
        $response->send();
        exit();
    }catch(PDOException $ex){
        $response = new Response();
        $response->setSuccess(false);
        $response->setHttpStatusCode(500);
        $response->addMessage('Error eliminando departamento');
        $response->send();
        exit();
    }
}elseif($_SERVER['REQUEST_METHOD'] === 'POST'){
    try{
        if($_SERVER['CONTENT_TYPE'] !== 'application/json'){
            $response = new Response();
            $response->setSuccess(false);
            $response->setHttpStatusCode(400);
            $response->addMessage('Content Type no corresponde a formato JSON');
            $response->send();
            exit();
        }   
        $rawPOSTData = file_get_contents('php://input');
        if(!$jsonData = json_decode($rawPOSTData)){
            $response = new Response();
            $response->setSuccess(false);
            $response->setHttpStatusCode(400);
            $response->addMessage('Request Body no corresponde a formato JSON');
            $response->send();
            exit();
        }
        if(!isset($jsonData->nomDepto)){
            $response = new Response();
            $response->setSuccess(false);
            $response->setHttpStatusCode(400);
            $response->addMessage('Nombre de departamento es obligatorio');
            $response->send();
            exit();
        }
        $newDepto = new Departamento(null, $jsonData->nomDepto);
        $query = $db->prepare('insert into departamentos (nom_depto) values (:nomDepto)');
        $query->bindParam(':nomDepto', $newDepto->getNomDepto(), PDO::PARAM_STR);
        $query->execute();
        $rowCount = $query->rowCount();
        
        if($rowCount===0){
            $response = new Response();
            $response->setSuccess(false);
            $response->setHttpStatusCode(400);
            $response->addMessage('Falló creación de departamento');
            $response->send();
            exit();
        }
        $lastIdDepto = $db->lastInsertId();

        $response = new Response();
        $response->setSuccess(true);
        $response->setHttpStatusCode(201);
        $response->addMessage('Departamento creado');
        $response->setData($lastIdDepto);
        $response->send();
        exit();
    }catch(DepartamentoException $ex){
        $response = new Response();
        $response->setSuccess(false);
        $response->setHttpStatusCode(400);
        $response->addMessage($ex->getMessage());
        $response->send();
        exit();
    }catch(PDOException $ex){
        $response = new Response();
        $response->setSuccess(false);
        $response->setHttpStatusCode(500);
        $response->addMessage('Falló conexión a BD');
        $response->send();
        exit();
    }
}