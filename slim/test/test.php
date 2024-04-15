<?php

use Slim\App;

$app = new App();

$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write('Hola');
    return $response;
});

return $app;