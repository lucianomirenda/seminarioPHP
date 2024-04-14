<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();
$app->addRoutingMiddleware();

function getConnection(){
    $dbhost="db";
    $dbname="seminariophp";
    $dbuser="seminariophp";
    $dbpass="seminariophp";


    
    $connection = new PDO ("mysql:host=$dbhost;dbname=$dbname",$dbuser,$dbpass);
    $connection->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

    return $connection;
}


$app->addErrorMiddleware(true, true, true);
$app->add( function ($request, $handler) {
    $response = $handler->handle($request);

    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'OPTIONS, GET, POST, PUT, PATCH, DELETE')
        ->withHeader('Content-Type', 'application/json')
    ;
});

// ACÁ VAN LOS ENDPOINTS


$app->get('/',function (Request $request, Response $response){
    $response->getBody()->write('hola');
    return $response;
});

$app->get('/test/{id}/{nombre}',function(Request $request, Response $response){
    
    $nombre = $request->getAttribute('nombre');
    $id = $request->getAttribute('id');

    $connection = getConnection();

    $query = $connection->prepare("SELECT * FROM inquilinos
                                WHERE id = ? AND nombre = ?");
    $query->execute([$id,$nombre]);

    $test = $query->fetchAll(PDO::FETCH_ASSOC);
    
    $json = json_encode($test);

    $response->getBody()->write($json);
    
    return $response->withHeader('Content+Type','application/json');
});


$app->get('/tipos_propiedad',function (Request $request, Response $response){
    $connection = getConnection();
    try{
        $query = $connection->query('SELECT * FROM tipo_propiedades');
        $tipos = $query -> fetchAll(PDO::FETCH_ASSOC);

        $payload = json_encode([
            'status'=> 'success',
            'code'=> 200,
            'data'=> $tipos
        ]);

        $response ->getBody()->write($payload);
        return $response -> withHeader ('Content+Type','application/json');

} catch (PDOException $e){
    $payload = json_encode([
        'status'=> 'success',
        'code'=> 400,
    ]);

    $response ->getBody()->write ($payload);
    return $response -> withHeader('Content-Type','application/json');
} 
});



$app->get('/propiedades', function (Request $request, Response $response){
            
    $connection = getConnection();
    
    try{

        $dataParams = $request->getParams();
        $dataToBind = [];
        $sql = 'SELECT * FROM propiedades p
                           INNER JOIN localidades l ON p.localidad_id = l.id
                            INNER JOIN tipo_propiedades tp ON p.tipo_propiedad_id = tp.id WHERE 1 = 1 '

        //Verificar que por ejemplo disponible exista
        //si existe entonces agrego a la consulta sql and p.disponible = :disponible 
        //y en array de datatobind pusheamos el dato a bindear
        //$dataToBind.push({attr: ':disponible', $disponible})
        //Despues que verifico cada parametro de la url
        // hacer un for de la variable $dataToBind y por cada valor hacer el             $stmt->bindParam(':nombre',$nombre,PDO::PARAM_STR);
     
        $disponible = $request->getAttribute('disponible');
        $localidad_id = $request->getAttribute('localidad_id');
        $fecha_inicio_disponibilidad = $request->getAttribute('fecha_inicio_disponibilidad');
        $cantidad_huespedes = $request->getAttribute('cantidad_huespedes');

        $query = $connection->prepare("SELECT * FROM propiedades p
                                        INNER JOIN localidades l ON p.localidad_id = l.id
                                        INNER JOIN tipo_propiedades tp ON p.tipo_propiedad_id = tp.id
                                        WHERE p.disponible = ?
                                        AND p.localidad_id = ?
                                        AND p.fecha_inicio_disponibilidad >= ?
                                        AND p.cantidad_huespedes >= ?");

        $query->execute([$disponible,$localidad_id,$fecha_inicio_disponibilidad,$cantidad_huespedes]);

        $data = $query -> fetchAll(PDO::FETCH_ASSOC);      

        $payload = json_encode([
            'status'=> 'success',
            'code'=> 200,
            'data'=> $data
        ]);

        $response ->getBody()->write($payload);
        return $response -> withHeader ('Content+Type','application/json');

} catch (PDOException $e){
    $payload = json_encode([
        'status'=> 'error',
        'code'=> 400,
    ]);

    $response ->getBody()->write ($payload);
    return $response -> withHeader('Content-Type','application/json');
} 
});


$app->get('/localidades',function (Request $request, Response $response){
    $connection = getConnection();
    try{
        $query = $connection->query('SELECT * FROM localidades');
        $tipos = $query -> fetchAll(PDO::FETCH_ASSOC);

        $payload = json_encode([
            'status' => 'success',
            'code' => 200,
            'data' => $tipos

        ]);

        $response ->getBody()->write($payload);
        return $response -> withHeader('Content+Type','application/json');
    
    } catch (PDOException $e){
        $payload = json_encode([
            'status' => 'error',
            'code' => 400,
        ]);

        $response->getBody()->write($payload);
        return $response-> withHeader('Content-Type','application/json');

    }
});

$app->post('/localidades', function(Request $request,Response $response){
    
    try{

        $connection = getConnection();
        $nombre = $request->getParsedBody()['nombre'];

        //chequeo que no este vacio
        if(!isset($nombre)&&!empty($nombre)){
    
            //chequeo si el nombre existe
            $stmt = $connection->prepare("SELECT COUNT(*) FROM localidades WHERE nombre = :nombre");
            $stmt->bindParam(':nombre',$nombre,PDO::PARAM_STR);
            $stmt->execute();
            $count = $stmt->fetchColumn();
    
            if($count > 0){
                $payload = json_encode([
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'el nombre ya existe en la base de datos' 
                ]);
            } else {
                //inserto la localidad en la base de datos

                $stmt = $connection->prepare("INSERT INTO localidades (nombre) VALUES (:nombre)");
                $stmt->bindParam(':nombre',$nombre,PDO::PARAM_STR);
                $stmt->execute();
        
                $payload = json_encode([
                    'message'=> 'localidad insertada perfectamente',
                    'status' => 'success',
                    'code' => 200,
                    'data' => $nombre
                ]);
            }
        } else {
            $payload = json_encode ([
                'status' => 'error',
                'code' => 400,
                'message' => 'No ingreso ningun dato.'
            ]);
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type','application/json');

    } catch (PDOException $e){
        $json = json_encode([
            'status' => 'error',
            'code' => 400,
        ]);

        $response->getBody()->write($json);
        return $response-> withHeader('Content-Type','application/json');

    }

});

$app->post('/tipos_propiedad',function(Request $request,Response $response){

    try{
        $connection = getConnection();

        $nombre = $request->getParsedBody()['nombre'];

        if(!isset($nombre)&&!empty($nombre)){

            //chequeo si el tipo esta en la base de datos

            $stmt = $connection->prepare("SELECT COUNT(*) FROM tipo_propiedades WHERE nombre = :nombre");
            $stmt->bindParam(':nombre',$nombre,PDO::PARAM_STR);
            $stmt->execute();
            $count = $stmt->fetchColumn();

            if($count > 0){
                $payload = json_encode([
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'el nombre ya existe. Este debe ser único.'
                ]);
            } else {

                $stmt = $connection->prepare("INSERT INTO tipo_propiedades (nombre) VALUES (:nombre)");
                $stmt->bindParam(':nombre',$nombre,PDO::PARAM_STR);
                $stmt->execute();

                $payload = json_encode([
                    'message' => 'El tipo de propiedad se inserto correctamente',
                    'status' => 'success',
                    'code' => 200,
                    'data' => $nombre
                ]);
            }
        } else {
            $payload = json_encode([
                'message' => 'No ingreso el nombre'
                'code' => 400,
                'status' => 'error',
            ]);
        }

        
        $response->getBody()->write($payload);
        return $response->withHeader('Content+Type','application/json');

    } catch (PDOException $e){
        $json = json_encode([
            'status' => 'error',
            'code' => 400,
        ]);

        $response->getBody()->write($json);
        return $response-> withHeader('Content-Type','application/json');

    }

});

$app->post('/inquilinos',function(Request $request,Response $response){

    $connection = getConnection();

    $documento = $request->getParsedBody()['documento'];
    $nombre = $request->getParsedBody()['nombre'];
    $apellido = $request->getParsedBody()['apellido'];
    $email = $request->getParsedBody()['email'];
    $activo = $request->getParsedBody()['activo'];

    $stmt = $connection->prepare("INSERT INTO inquilinos(documento,nombre,apellido,email,activo) VALUES (?,?,?,?,?)");

    $stmt->bindParam(1,$documento,PDO::PARAM_STR);
    $stmt->bindParam(2,$nombre,PDO::PARAM_STR);
    $stmt->bindParam(3,$apellido,PDO::PARAM_STR);
    $stmt->bindParam(4,$email,PDO::PARAM_STR);
    $stmt->bindParam(5,$activo,PDO::PARAM_STR);

    $stmt->execute();

    $data = [
        'message' => 'Se inserto el nuevo inquilino!',
        'documento' => $documento,
        'nombre' => $nombre,
        'apellido' => $apellido,
        'email' => $email,
        'activo' => $activo
    ];

    $payload = json_encode($data);

    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type','application/json');

});



$app->get('/inquilinos',function (Request $request, Response $response){
    $connection = getConnection();
    try{
        $query = $connection->query('SELECT * FROM inquilinos');
        $inquilinos = $query -> fetchAll(PDO::FETCH_ASSOC);

        $json = json_encode([
            'status' => 'success',
            'code' => 200,
            'data' => $inquilinos

        ]);

        $response ->getBody()->write($json);
        return $response -> withHeader('Content+Type','application/json');
    
    } catch (PDOException $e){
        $json = json_encode([
            'status' => 'success',
            'code' => 400,
        ]);

        $response->getBody()->write($json);
        return $response-> withHeader('Content-Type','application/json');

    }
});


$app->get('/inquilinos/{id}',function (Request $request, Response $response){
    $connection = getConnection();

    try{

        $id = $request->getAttribute('id');
        $query = $connection->prepare("SELECT * FROM inquilinos WHERE id = ?");
        $query->execute([$id]);

        $inquilinos = $query -> fetchAll(PDO::FETCH_ASSOC);
        
        $json = json_encode([
            'status' => 'success',
            'code' => 200,
            'data' => $inquilinos

        ]);

        $json = json_encode($inquilinos);


        $response ->getBody()->write($json);
        return $response -> withHeader('Content+Type','application/json');
    
    } catch (PDOException $e){
        $json = json_encode([
            'status' => 'error',
            'code' => 400,
        ]);

        $response->getBody()->write($json);
        return $response-> withHeader('Content-Type','application/json');

    }
});




$app->run();