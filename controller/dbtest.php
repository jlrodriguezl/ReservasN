<?php
require_once('db.php');
require_once('../model/Response.php');

try{
    $db = DB::conectarDB();
}catch(PDOException $ex){
    $response = new Response();
    $response->setSuccess(false);
    $response->setHttpStatusCode(500);
    $response->addMessage("Error en conexión a BD ".$ex);
    $response->send();
    exit;
}