<?php
require_once('Response.php');

$response = new Response();

$response->setSuccess(true);
$response->setHttpStatusCode(200);
$response->addMessage("Mensaje Prueba 1");
$response->addMessage("Mensaje Prueba 2");
$response->send();