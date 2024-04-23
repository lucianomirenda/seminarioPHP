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

$app->addBodyParsingMiddleware();
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


//TIPOS_PROPIEDAD

$app->get('/tipos_propiedad',function (Request $request, Response $response){
    try{
        $connection = getConnection();
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

$app->post('/tipos_propiedad',function(Request $request,Response $response){
   

    $errores = "";

    $data = $request->getParsedBody();

    if(!isset($data['nombre'])){
        $errores = "El nombre no esta definido.";

    } else {
        $nombre = $data['nombre'];

        if(empty($nombre)){
            $errores = "El campo nombre esta vacio.";
        }

        if(strlen($nombre) > 50){ 
            $errores = "El campo nombre contiene más caracteres de los permitidos.";        
            
        } 
    }   
    
    if(empty($errores)){
        
        try{

            $connection = getConnection();
            $stmt = $connection->prepare("SELECT COUNT(*) FROM tipo_propiedades WHERE nombre = :nombre");
            $stmt->bindParam(':nombre',$nombre,PDO::PARAM_STR);
            $stmt->execute();
            $count = $stmt->fetchColumn();

            if(!$count > 0){

                $stmt = $connection->prepare("INSERT INTO tipo_propiedades (nombre) VALUES (:nombre)");
                $stmt->bindParam(':nombre',$nombre,PDO::PARAM_STR);
                $stmt->execute();

                $payload = json_encode([
                    'status' => "success",
                    'code' => "200",
                    'message' => 'el nombre se inserto correctamente.'
                ]);

                $response-> withHeader('Content+Type','application/json');

            } else {
                $errores = "El nombre ya existe en la base de datos y debe ser único. ";
            }
        } catch (PDOException $e){
            $errores = "PDOException";
        }

    }

    if(!empty($errores)){
        $payload = json_encode([
            'status' => 'error',
            'code' => 400,
            'message' => $errores
        ]);
        $response-> withHeader('Content-Type','application/json');

    }

    $response->getBody()->write($payload);
    return $response;

});

$app->put('/tipos_propiedad/{id}',function(Request $request,Response $response){

    try{

        $error = "";

        $connection = getConnection();

        $id = $request->getAttribute('id');

        $stmt = $connection->prepare("SELECT * FROM tipo_propiedades WHERE id = :id");
        $stmt->bindParam(':id',$id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0){

            $data = $request->getParsedBody();

            if(isset($data['nombre'])){
                
                $nombre = $data['nombre'];

                if(empty($nombre)){
                    $error = "El campo nombre esta vacio.";
                }

                if(strlen($nombre) > 50){ 
                    $error = "El campo nombre contiene más caracteres de los permitidos.";        
                    
                } 
            } else {
                $error = "El nombre no esta definido.";
            }   
            
            if(empty($error)){
                
                $connection = getConnection();
                $stmt = $connection->prepare("SELECT COUNT(*) FROM tipo_propiedades WHERE nombre = :nombre");
                $stmt->bindParam(':nombre',$nombre,PDO::PARAM_STR);
                $stmt->execute();
                $count = $stmt->fetchColumn();

                if(!$count > 0){

                    $stmt = $connection->prepare("UPDATE tipo_propiedades SET nombre = :nombre WHERE id = :id");
                    $stmt->bindParam(':nombre',$nombre,);
                    $stmt->bindParam(':id',$id);
                    $stmt->execute();

                    $payload = json_encode([
                        'status' => "success",
                        'code' => "200",
                        'message' => 'el nombre se actualizo correctamente.'
                    ]);
                    
                    $response->withHeader('Content+Type','application/json');

                } else {
                    $error = "el nombre ya se encuentra en la base de datos y debe ser único";
                }
            }

        } else {
            $error = "el id no se encuentra en la base de datos";
        }

    } catch (PDOException $e){
        $error = "PDOException";
    }

    if(!empty($error)){
        
        $payload = json_encode([
            'status' => "error",
            'code' => "400",
            'message' => $error
        ]);
        $response-> withHeader('Content-Type','application/json');

    }

    $response->getBody()->write($payload);
    return $response;


});

$app->delete('/tipos_propiedad/{id}', function (Request $request, Response $response) {
    $id = (int) $request->getAttribute('id');
    try {
    $connection = getConnection();
        
        
    $consultaVerificacion = $connection->prepare('SELECT COUNT(*) FROM propiedades WHERE tipo_propiedad_id = :id');
    $consultaVerificacion->bindParam(':id', $id, PDO::PARAM_INT);
    $consultaVerificacion->execute();
    
    $registrosReferenciados = $consultaVerificacion->fetchColumn();

        if ($registrosReferenciados > 0) {
            $response -> getBody() ->write(json_encode("El tipo de propiedad no puede eliminarse porque está referenciado en la tabla 'propiedades'."));
            return $response->withStatus(409); // Conflicto
                            
        }else{
                $query = $connection->prepare('DELETE FROM tipo_propiedades WHERE id =:id');
                $query->bindParam(':id', $id, PDO::PARAM_INT);
                $query->execute();

                $deletedRows = $query->rowCount();

                if ($deletedRows > 0) {
                    $response -> getBody() -> write(json_encode("Tipo de propiedad eliminado con exito"));
                    return $response->withStatus(200);
                } else {
                    $response -> getBody() -> write(json_encode("tipo de propiedad no encontrado"));
                    return $response->withStatus(404); // No encontrado
                }
        }
    } catch (Exception $e) {
            $response->getBody()->write(json_encode(['Error'=>$e->getMessage()]));
            return $response->withStatus(500);
                }
});

//LOCALIDADES

$app->get('/localidades',function (Request $request, Response $response){
   
    try{
        $connection = getConnection();
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

$app->post('/localidades',function(Request $request,Response $response){
   

    $errores = "";

    $data = $request->getParsedBody();

    if(!isset($data['nombre'])){
        $errores = "El nombre no esta definido.";

    } else {
        $nombre = $data['nombre'];

        if(empty($nombre)){
            $errores = "El campo nombre esta vacio.";
        }

        if(strlen($nombre) > 50){ 
            $errores = "El campo nombre contiene más caracteres de los permitidos.";        
            
        } 
    }   
    
    if(empty($errores)){
        
        try{

            $connection = getConnection();
            $stmt = $connection->prepare("SELECT COUNT(*) FROM localidades WHERE nombre = :nombre");
            $stmt->bindParam(':nombre',$nombre,PDO::PARAM_STR);
            $stmt->execute();
            $count = $stmt->fetchColumn();

            if(!$count > 0){

                $stmt = $connection->prepare("INSERT INTO localidades (nombre) VALUES (:nombre)");
                $stmt->bindParam(':nombre',$nombre,PDO::PARAM_STR);
                $stmt->execute();

                $payload = json_encode([
                    'status' => "success",
                    'code' => "200",
                    'message' => 'el nombre se inserto correctamente.'
                ]);

                $response->withHeader('Content+Type','application/json');

            } else {
                $errores = "El nombre ya existe en la base de datos";
            }
        } catch (PDOException $e){
            $errores = "PDOException";
        }

    } 

    if(!empty($errores)){

        $payload = json_encode([
            'status' => 'error',
            'code' => 400,
            'message' => $errores
        ]);

        $response->withHeader('Content-Type','application/json');
    }


    $response->getBody()->write($payload);
    return $response;

});

$app->put('/localidades/{id}', function(Request $request,Response $response){

    try{

        $error = "";

        $connection = getConnection();

        $id = $request->getAttribute('id');

        $stmt = $connection->prepare("SELECT * FROM localidades WHERE id = :id");
        $stmt->bindParam(':id',$id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0){

            $data = $request->getParsedBody();

            if(isset($data['nombre'])){
                
                $nombre = $data['nombre'];

                if(empty($nombre)){
                    $error = "El campo nombre esta vacio.";
                }

                if(strlen($nombre) > 50){ 
                    $error = "El campo nombre contiene más caracteres de los permitidos.";        
                    
                } 
            } else {
                $error = "El nombre no esta definido.";
            }   
            
            if(empty($error)){
                
                $connection = getConnection();
                $stmt = $connection->prepare("SELECT COUNT(*) FROM localidades WHERE nombre = :nombre");
                $stmt->bindParam(':nombre',$nombre,PDO::PARAM_STR);
                $stmt->execute();
                $count = $stmt->fetchColumn();

                if(!$count > 0){

                    $stmt = $connection->prepare("UPDATE localidades SET nombre = :nombre WHERE id = :id");
                    $stmt->bindParam(':nombre',$nombre,);
                    $stmt->bindParam(':id',$id);
                    $stmt->execute();

                    $payload = json_encode([
                        'status' => "success",
                        'code' => "200",
                        'message' => 'el nombre se actualizo correctamente.'
                    ]);
                    
                    $response->withHeader('Content+Type','application/json');

                } else {
                    $error = "el nombre ya se encuentra en la base de datos y debe ser único";
                }
            }

        } else {
            $error = "el id no se encuentra en la base de datos";
        }

    } catch (PDOException $e){
        $error = "PDOException";
    }

    if(!empty($error)){
        
        $payload = json_encode([
            'status' => "error",
            'code' => "400",
            'message' => $error
        ]);
        $response-> withHeader('Content-Type','application/json');

    }

    $response->getBody()->write($payload);
    return $response;


});

$app->delete('/localidades/{id}', function (Request $request, Response $response) {
    $id = (int) $request->getAttribute('id');
    try {
    $connection = getConnection();
        
        
    $consultaVerificacion = $connection->prepare('SELECT COUNT(*) FROM propiedades WHERE localidad_id = :id');
    $consultaVerificacion->bindParam(':id', $id, PDO::PARAM_INT);
    $consultaVerificacion->execute();
    
    $registrosReferenciados = $consultaVerificacion->fetchColumn();

        if ($registrosReferenciados > 0) {
            $response -> getBody() ->write(json_encode("La localidad no puede eliminarse porque está referenciado en la tabla 'propiedades'."));
            return $response->withStatus(409); // Conflicto
                            
        }else{
                $query = $connection->prepare('DELETE FROM localidades WHERE id =:id');
                $query->bindParam(':id', $id, PDO::PARAM_INT);
                $query->execute();

                $deletedRows = $query->rowCount();

                if ($deletedRows > 0) {
                    $response -> getBody() -> write(json_encode("Localidad eliminado con exito"));
                    return $response->withStatus(200);
                } else {
                    $response -> getBody() -> write(json_encode("Localidad no encontrada"));
                    return $response->withStatus(404); // No encontrado
                }
        }
    } catch (Exception $e) {
            $response->getBody()->write(json_encode(['Error'=>$e->getMessage()]));
            return $response->withStatus(500);
                }
});

//INQUILINOS

$app->get('/inquilinos',function (Request $request, Response $response){
  
    try{
        $connection = getConnection();
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
    

    try{

        $connection = getConnection();
        $id = $request->getAttribute('id');
        $stmt = $connection->prepare("SELECT * FROM inquilinos WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {

            $inquilinos = $stmt -> fetchAll(PDO::FETCH_ASSOC);

            $payload = json_encode([
                'status' => 'success',
                'code' => 200,
                'data' => $inquilinos
    
            ]);

            $response -> getBody() -> write($payload);
            return $response->withStatus(200);

        } else {
            $response -> getBody() -> write(json_encode("Inquilino no encontrado"));
            return $response->withStatus(404); // No encontrado
        }
    
    } catch (PDOException $e){
        $json = json_encode([
            'status' => 'error',
            'code' => 400,
        ]);

        $response->getBody()->write($json);
        return $response-> withHeader('Content-Type','application/json');

    }
});

$app->get('/inquilinos/{id}/reservas',function (Request $request, Response $response){
    

    try{

        $connection = getConnection();
        $id = $request->getAttribute('id');
        $stmt = $connection->prepare("SELECT * FROM inquilinos WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {

            $inquilino = $stmt -> fetchAll(PDO::FETCH_ASSOC);

            $stmt = $connection->prepare("SELECT * FROM reservas WHERE inquilino_id = :id");
            $stmt->bindParam(':id',$id,PDO::PARAM_INT);
            $stmt->execute();

            $reservas = $stmt -> fetchAll(PDO::FETCH_ASSOC);

            $payload = json_encode([
                'status' => 'success',
                'code' => 200,
                'inquilino' => $inquilino,
                'reservas' => $reservas
    
            ]);

            $response -> getBody() -> write($payload);
            return $response->withStatus(200);

        } else {
            $response -> getBody() -> write(json_encode("Inquilino no encontrado"));
            return $response->withStatus(404); // No encontrado
        }
    
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
        
    $params = $request->getParsedBody();
    $errores = [];
    
    //verfico que se esten todos los parámetros necesarios para realizar el insert
    
    $requiredKeys = ["nombre","apellido","documento","email","activo"];
    $missingKeys = []; //almaceno las claves que faltan
    
    
    foreach($requiredKeys as $key){
        if(!array_key_exists($key, $params)){
            $missingKeys[] = $key;
        } else {
            $value = $params[$key];
            if(empty($value)){
                $missingKeys[] = $key; 
            }
        }
    }

    if(!empty($missingKeys)){
        $errores[] =  "Los siguientes campos son requeridos: " . implode(", ", $missingKeys);
    }


    $sizeErrorKeys = [];
    $maxChars = [
        "nombre" => 25,
        "apellido" => 15,
        "email" => 20
    ];

    foreach($params as $key => $value){
        if(in_array($key,array_keys($maxChars))){
            if(strlen($value) > $maxChars[$key]){
                $sizeErrorKeys[] = $key;
            }
        }
    }

    if(!empty($sizeErrorKeys)){
        $errores[] = 'Los siguientes campos exceden la cantidad de caracteres requerida: ' . implode(", ",$sizeErrorKeys);

    }


    if(empty($errores)){

        try{

            $connection = getConnection();

            $stmt = $connection->prepare("SELECT * FROM inquilinos WHERE documento = :documento");
            $stmt->bindParam(':documento',$params['documento']);
            $stmt->execute();
            
            if(!$stmt->rowCount() > 0){
                // no existe ningun inquilino con ese documento
                // procedo a insertar al usuario en la base de datos
                
                $stmt = $connection->prepare("INSERT INTO inquilinos (nombre,apellido,email,documento,activo)
                                            VALUES (:nombre,:apellido,:email,:documento,:activo)");
                
                $stmt->bindParam(':nombre',$params['nombre']);
                $stmt->bindParam(':apellido',$params['apellido']);
                $stmt->bindParam(':email',$params['email']);
                $stmt->bindParam(':documento',$params['documento']);
                $stmt->bindParam(':activo',$params['activo']);
                $stmt->execute();
                
                $payload = json_encode([
                    'message' => 'El usuario se inserto en la base de datos correctamente.',
                    'status' => 'success',
                    'code' => 201,
                    'data' => $params
                ]); 

                $response->withHeader('Content+Type','application/json');

            } else {

                $errores[] = "Ya existe un inquilino con ese documento y este debe ser único.";
            }

        } catch (PDOException $e){
            $errores = "PDOException";
        }
    }

    if(!empty($errores)){
        $payload = json_encode([
            'status' => 'Error',
            'code' => 400,
            'message' => $errores
        ]);
        $response-> withHeader('Content-Type','application/json');
    }


    $response->getBody()->write($payload);
    return $response;

});

$app->put('/inquilinos/{id}', function(Request $request,Response $response){    

    try{

        $errores = [];
        $connection = getConnection();

        $id = $request->getAttribute('id');
        $params = $request->getParsedBody();

        $stmt = $connection->prepare("SELECT * FROM inquilinos WHERE id = :id");
        $stmt->bindParam(':id',$id);
        $stmt->execute();

        //chequeo que exista el id del inquilino
        if($stmt->rowCount() == 0){

            $errores[] = "No existe un inquilino con el id " . $id;

        }

        //chequeo que ninguno de los campos que mando para modificar este vacio
        $keys = ["nombre","apellido","documento","email","activo"];
        $emptyFields = [];
    
        //mientras exista la calve y este vacio, la agrego a emptyFields.
        foreach ($keys as $key) {
            if (isset($params[$key]) && empty($params[$key])) {
                $emptyFields[] = $key;
            }
        }
        
        //si habia un cambio vacio, no entra, y envia caul es.
        if(!count($emptyFields) == 0){
            $errores[] = "Los siguientes campos estan vacios: " . implode(", ",$emptyFields);
        }

        //chequeo la cantidad de caracteres de los nuevos campos
        $sizeErrorKeys = [];
        $maxChars = [
            "nombre" => 25,
            "apellido" => 15,
            "email" => 20
        ];

        foreach($params as $key => $value){
            if(in_array($key,array_keys($maxChars))){
                if(strlen($value) > $maxChars[$key]){
                    $sizeErrorKeys[] = $key;
                }
            }
        }

            
        if(!empty($sizeErrorKeys)){
            $errores[] = "Los siguientes campos estan exceden la cantidad de caracteres requerida: " . implode(", ",$sizeErrorKeys);
        }

        if(empty($errores)){

            $documentoNoEsta = true;
            //chequeo si traje un valor en el campo documento, en caso de hacerlo, chequeo si ya esta en la base de datos.
            if(isset($params['documento'])){

                $stmtDocumento = $connection->prepare("SELECT COUNT(*) FROM inquilinos WHERE documento = :documento");
                $stmtDocumento->bindParam(':documento', $params['documento']);
                $stmtDocumento->execute();
                $documentoNoEsta = $stmtDocumento->fetchColumn() == 0;
                
            }

            //en caso de de que no se haya mandado ningun documento sigo.En caso de que si y no este en la base de datos también.
            //solamente cuando el documento se mando y ya esta en la base de datos esta condicion resulta falsa.
            if($documentoNoEsta){
                //ahora que paso todas las pruebas, genero la consulta sql dinámicamente

                $sql = "UPDATE inquilinos SET ";

                foreach ($params as $campo => $valor) {
                    $sql .= "`$campo` = :$campo, ";
                }

                // eliminamos la coma al final
                $sql = rtrim($sql, ', ');

                $sql .= " WHERE id = :id";

                $stmt = $connection->prepare($sql);

                //vinculo los valores a los parámetros
                $stmt->bindParam(':id', $id);
                foreach ($params as $campo => $valor) {
                    $stmt->bindParam(":$campo", $params[$campo]);
                }

                $stmt->execute();
                
                $payload = json_encode([
                    'message' => 'El inquilino con id '.$id." actualizo los siguientes datos correctamente!",
                    'status' => 'success',
                    'code' => 200,
                    'data' => $params
                ]);

                $response->withHeader('Content+Type','application/json');

            } else {
                $errores[] = "El documento ya se encuentra en la base de datos y debe ser único.";
            }
        } 

    } catch (PDOException $e){
        
        $errores[] = "PDOException";
        //preguntar si deberia agregar algo

    }

    if(!empty($errores)){

        $payload = json_encode([
            'status' => 'Error',
            'code' => 400,
            'message' => $errores
        ]);

        $response->withHeader('Content-Type','application/json');

    }

    $response->getBody()->write($payload);
    return $response;

});

$app->delete('/inquilinos/{id}', function (Request $request, Response $response) {
    $id = (int) $request->getAttribute('id');
    try {
    $connection = getConnection();
        
        
    $consultaVerificacion = $connection->prepare('SELECT COUNT(*) FROM reservas WHERE inquilino_id = :id');
    $consultaVerificacion->bindParam(':id', $id, PDO::PARAM_INT);
    $consultaVerificacion->execute();
    
    $registrosReferenciados = $consultaVerificacion->fetchColumn();

        if ($registrosReferenciados > 0) {
            $response -> getBody() ->write(json_encode("El inquilino no puede eliminarse porque está referenciado en la tabla 'reservas'."));
            return $response->withStatus(409); // Conflicto
                            
        }else{
                $query = $connection->prepare('DELETE FROM inquilinos WHERE id =:id');
                $query->bindParam(':id', $id, PDO::PARAM_INT);
                $query->execute();

                $deletedRows = $query->rowCount();

                if ($deletedRows > 0) {
                    $response -> getBody() -> write(json_encode("Inquilino eliminado con exito"));
                    return $response->withStatus(200);
                } else {
                    $response -> getBody() -> write(json_encode("Inquilino no encontrado"));
                    return $response->withStatus(404); // No encontrado
                }
        }
    } catch (Exception $e) {
            $response->getBody()->write(json_encode(['Error'=>$e->getMessage()]));
            return $response->withStatus(500);
                }
});

//PROPIEDADES

$app->get('/propiedades', function (Request $request, Response $response){
            
    $connection = getConnection();
    
    try{


        $params = $request->getQueryParams();
        
        $sql = 'SELECT p.*, l.nombre AS localidad, tp.nombre AS tipo_de_propiedad
        FROM propiedades p
        INNER JOIN localidades l ON p.localidad_id = l.id
        INNER JOIN tipo_propiedades tp ON p.tipo_propiedad_id = tp.id
        WHERE 1 = 1';

        foreach($params as $campo => $valor){
            $sql .= " AND p.`$campo` = :$campo ";
        }
        
        $stmt = $connection->prepare($sql);
        foreach ($params as $campo => $valor) {
            $stmt->bindParam(":$campo", $params[$campo]);
        }
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
         

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

$app -> post('/propiedades', function (Request $request, Response $response){

    try{

        $connection = getConnection();
        $params = $request->getParsedBody();
        $requiredKeys = ["domicilio","localidad_id","cantidad_huespedes","fecha_inicio_disponibilidad","cantidad_dias","disponible","valor_noche","tipo_propiedad_id"];
        
        $missingKeys = [];
        
        
        foreach($requiredKeys as $key){
        if(!array_key_exists($key, $params)){
            $missingKeys[] = $key;
        } else {
            $value = $params[$key];
            if(empty($value)){
                $missingKeys[] = $key; 
            }
        }
    }
 
    
    if(empty($missingKeys)){

        $stmt = $connection->prepare("SELECT * FROM localidades WHERE id = :localidad_id");
        $stmt->bindParam(':localidad_id',$params['localidad_id']);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {

            $stmt = $connection->prepare("SELECT * FROM tipo_propiedades WHERE id = :tipo_propiedad_id");
            $stmt->bindParam(':tipo_propiedad_id',$params['tipo_propiedad_id']);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
            
                $stmt = $connection->prepare("INSERT INTO propiedades(domicilio,localidad_id,cantidad_habitaciones,cantidad_banios,cochera,cantidad_huespedes,fecha_inicio_disponibilidad,cantidad_dias,disponible,valor_noche,tipo_propiedad_id,imagen,tipo_imagen)
                                            VALUES (:domicilio, :localidad_id, :cantidad_habitaciones, :cantidad_banios, :cochera, :cantidad_huespedes, :fecha_inicio_disponibilidad, :cantidad_dias, :disponible, :valor_noche, :tipo_propiedad_id, :imagen, :tipo_imagen)");

                $stmt->bindParam(':domicilio',$params['domicilio']);
                $stmt->bindParam(':localidad_id',$params['localidad_id']);
                $stmt->bindParam(':cantidad_habitaciones',$params['cantidad_habitaciones']);
                $stmt->bindParam(':cantidad_banios',$params['cantidad_banios']);
                $stmt->bindParam(':cochera',$params['cochera']);
                $stmt->bindParam(':cantidad_huespedes',$params['cantidad_huespedes']);
                $stmt->bindParam(':fecha_inicio_disponibilidad',$params['fecha_inicio_disponibilidad']);
                $stmt->bindParam(':cantidad_dias',$params['cantidad_dias']);
                $stmt->bindParam(':disponible',$params['disponible']);
                $stmt->bindParam(':valor_noche',$params['valor_noche']);
                $stmt->bindParam(':tipo_propiedad_id',$params['tipo_propiedad_id']);
                $stmt->bindParam(':imagen',$params['imagen']);
                $stmt->bindParam(':tipo_imagen',$params['tipo_imagen']);
                $stmt->execute();

                $payload = json_encode([
                    'message' => 'La propiedad se inserto en la base de datos correctamente.',
                    'status' => 'success',
                    'code' => 201,
                    'data' => $params
                ]);
            
            } else {
                $payload = json_encode([
                    'message' => 'El tipo de propiedad no existe.',
                    'status' => 'Error',
                    'code' => 400,
                ]);
            }
        } else {
            $payload = json_encode([
                'message' => 'La localidad no existe.',
                'status' => 'Error',
                'code' => 400,
            ]);
        }
                
    } else {
        $payload = json_encode([
            'message' => 'Falta completar los siguientes campos',
            'status' => 'Error',
            'code' => 400,
            'data' => $missingKeys
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

$app->put('/propiedades/{id}', function(Request $request,Response $response){    

    try{

        $errores = [];
        $connection = getConnection();

        $id = $request->getAttribute('id');
        $params = $request->getParsedBody();

        $stmt = $connection->prepare("SELECT * FROM propiedades WHERE id = :id");
        $stmt->bindParam(':id',$id);
        $stmt->execute();

        //chequeo que exista el id de la propiedad
        if($stmt->rowCount() == 0){

            $errores[] = "No existe una propiedad con el id " . $id;

        }

        //chequeo que ninguno de los campos que mando para modificar este vacio
        $keys = ["domicilio","localidad_id","cantidad_huespedes","cochera","cantidad_dias","cantidad_banios","cantidad_habitaciones","fecha_inicio_disponibilidad","cantidad_dias","disponible","valor_noche","tipo_propiedad_id","imagen","tipo_imagen"];
        $emptyFields = [];
    
        //mientras exista la calve y este vacio, la agrego a emptyFields.
        foreach ($keys as $key) {
            if (isset($params[$key]) && empty($params[$key])) {
                $emptyFields[] = $key;
            }
        }
        
        //si habia un cambio vacio, no entra, y envia caul es.
        if(!count($emptyFields) == 0){
            $errores[] = "Los siguientes campos estan vacios: " . implode(", ",$emptyFields);
        }


        if(empty($errores)){

            $localidadExiste = isset($params['localidad_id']);
            //chequeo si traje un valor en el campo documento, en caso de hacerlo, chequeo si ya esta en la base de datos.
            if($localidadExiste){

                $stmtLocalidad = $connection->prepare("SELECT COUNT(*) FROM localidades WHERE id = :localidad_id");
                $stmtLocalidad->bindParam(':localidad_id',$params['localidad_id']);
                $stmtLocalidad->execute();
                $localidadSigo = $stmtLocalidad->fetchColumn() > 0;
                
            } else {
                $localidadSigo = true;
            }

            if($localidadSigo){
                
                $propiedadExiste = isset($params['tipo_propiedad_id']);

                if($propiedadExiste){

                    $stmtPropiedad = $connection->prepare("SELECT COUNT(*) FROM tipo_propiedades WHERE id = :tipo_propiedad_id");
                    $stmtPropiedad->bindParam(':tipo_propiedad_id',$params['tipo_propiedad_id']);
                    $stmtPropiedad->execute();
                    $propiedadSigo = $stmtPropiedad->fetchColumn() > 0;
                    
                } else {
                    $propiedadSigo = true;
                }

                if($propiedadSigo){

                    $sql = "UPDATE propiedades SET ";

                    foreach ($params as $campo => $valor) {
                        $sql .= "`$campo` = :$campo, ";
                    }

                    // eliminamos la coma al final
                    $sql = rtrim($sql, ', ');

                    $sql .= " WHERE id = :id";

                    $stmt = $connection->prepare($sql);

                    //vinculo los valores a los parámetros
                    $stmt->bindParam(':id', $id);
                    foreach ($params as $campo => $valor) {
                        $stmt->bindParam(":$campo", $params[$campo]);
                    }

                    $stmt->execute();
                    
                    $payload = json_encode([
                        'message' => 'El inquilino con id '.$id." actualizo los siguientes datos correctamente!",
                        'status' => 'success',
                        'code' => 200,
                        'data' => $params
                    ]);

                    $response->withHeader('Content+Type','application/json');

                } else {

                    $errores[] = "El tipo de propiedad no encuentra en la base de datos.";
                }
            
            } else {
                $errores[] = "La localidad no encuentra en la base de datos.";
            }
        } 

    } catch (PDOException $e){
        
        $errores[] = "PDOException";
        //preguntar si deberia agregar algo

    }

    if(!empty($errores)){

        $payload = json_encode([
            'status' => 'Error',
            'code' => 400,
            'message' => $errores
        ]);

        $response->withHeader('Content-Type','application/json');

    }

    $response->getBody()->write($payload);
    return $response;

});

$app->delete('/propiedades/{id}', function (Request $request, Response $response) {
    
    $id = (int) $request->getAttribute('id');
    try {
    $connection = getConnection();
        
        
    $consultaVerificacion = $connection->prepare('SELECT COUNT(*) FROM reservas WHERE propiedad_id = :id');
    $consultaVerificacion->bindParam(':id', $id, PDO::PARAM_INT);
    $consultaVerificacion->execute();
    
    $registrosReferenciados = $consultaVerificacion->fetchColumn();

        if ($registrosReferenciados > 0) {
            $response -> getBody() ->write(json_encode("La propiedad no puede eliminarse porque está referenciado en la tabla 'reservas'."));
            return $response->withStatus(409); // Conflicto
                            
        }else{
                $query = $connection->prepare('DELETE FROM propiedades WHERE id =:id');
                $query->bindParam(':id', $id, PDO::PARAM_INT);
                $query->execute();

                $deletedRows = $query->rowCount();

                if ($deletedRows > 0) {
                    $response -> getBody() -> write(json_encode("Propiedad eliminada con exito"));
                    return $response->withStatus(200);
                } else {
                    $response -> getBody() -> write(json_encode("Propiedad no encontrada"));
                    return $response->withStatus(404); // No encontrado
                }
        }
    } catch (Exception $e) {
            $response->getBody()->write(json_encode(['Error'=>$e->getMessage()]));
            return $response->withStatus(500);
                }
});

//RESERVAS

$app -> get ('/reservas', function (Request $request, Response $response){
    $connection = getConnection();
    try{
        $query = $connection->query('SELECT * FROM reservas res 
                                    INNER JOIN propiedades p ON res.propiedad_id = p.id
                                    INNER JOIN inquilinos inq ON res.inquilino_id = inq.id');
        $tipos = $query -> fetchAll(PDO::FETCH_ASSOC);

        $payload = json_encode([
            'status'=> 'success',
            'code' => 200,
            'data'=> $tipos
        ]);

        $response -> getBody() -> write ($payload);
        return $response -> withHeader ('Content+Type', 'application/json');
    } catch (PDOException $e){
        $payload = json_encode([
            'status'=> 'ERROR',
            'code'=> 400,
        ]);
    
        $response ->getBody()->write ($payload);
        return $response -> withHeader('Content-Type','application/json');
    }
});

$app->post('/reservas', function (Request $request, Response $response) {
    try {
        $connection = getConnection();

        $params = $request -> getParsedBody();
        
        $requiredKeys = ["propiedad_id","inquilino_id","fecha_desde","cantidad_noches"];
        $missingKeys = [];
    
        foreach($requiredKeys as $key){
            if(!array_key_exists($key, $params)){
                $missingKeys[] = $key;
            }else{
                $value=$params[$key];
                if(empty($value)){
                    $missingKeys[] = $key; //las agrego al array
                }
            }
        }

        // Si faltan campos requeridos, devuelve un mensaje de error
        if (!empty($missingKeys)) {
            $payload = json_encode([
                'message' => 'Faltan los siguientes campos obligatorios: ' . implode(', ', $missingKeys),
                'status' => 'Error',
                'code' => 400,
            ]);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Consulta para verificar la disponibilidad de la propiedad
        $stmt = $connection->prepare("SELECT disponible
                                     FROM propiedades 
                                     WHERE id = :propiedad_id
                                     AND disponible = true");
        $stmt->bindParam(':propiedad_id', $params['propiedad_id']);
        $stmt->execute();

        // Si la propiedad está disponible
        if ($stmt->rowCount() > 0) {
            // Obtener el valor de la noche
            $stmt = $connection->prepare("SELECT valor_noche FROM propiedades WHERE id = :propiedad_id");
            $stmt->bindParam(':propiedad_id', $params['propiedad_id']);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $propiedad = $stmt->fetch(PDO::FETCH_ASSOC);

                if (isset($propiedad['valor_noche'])) {
                    $valor_total = $params['cantidad_noches'] * $propiedad['valor_noche'];
                    $params['valor_total'] = $valor_total;
                }
            }

            // Verificar si el inquilino está activo
            $stmt = $connection->prepare("SELECT * FROM inquilinos WHERE id = :inquilino_id AND activo = true");
            $stmt->bindParam(':inquilino_id', $params['inquilino_id']);
            $stmt->execute();

            // Si el inquilino está activo, inserta la reserva en la base de datos
            if ($stmt->rowCount() > 0) {
                $stmt = $connection->prepare("INSERT INTO reservas(propiedad_id,inquilino_id,fecha_desde,cantidad_noches,valor_total)
                                            VALUES (:propiedad_id, :inquilino_id, :fecha_desde, :cantidad_noches, :valor_total)");
                $stmt->bindParam(':propiedad_id', $params['propiedad_id']);
                $stmt->bindParam(':inquilino_id', $params['inquilino_id']);
                $stmt->bindParam(':fecha_desde', $params['fecha_desde']);
                $stmt->bindParam(':cantidad_noches', $params['cantidad_noches']);
                $stmt->bindParam(':valor_total', $params['valor_total']);
                $stmt->execute();

                // Respuesta exitosa
                $payload = json_encode([
                    'message' => 'La reserva se insertó en la base de datos correctamente.',
                    'status' => 'success',
                    'code' => 201,
                    'data' => $params
                ]);
                $response->getBody()->write($payload);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
            } else {
                // Inquilino no activo
                $payload = json_encode([
                    'message' => 'El inquilino no está activo o no existe.',
                    'status' => 'Error',
                    'code' => 400,
                ]);
                $response->getBody()->write($payload);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
        } else {
            // Propiedad no disponible
            $payload = json_encode([
                'message' => 'La propiedad no está disponible para la fecha seleccionada o no existe.',
                'status' => 'Error',
                'code' => 400,
            ]);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    } catch (PDOException $e) {
        // Error de base de datos
        $payload = json_encode([
            'message' => 'Error de base de datos: ' . $e->getMessage(),
            'status' => 'Error',
            'code' => 500,
        ]);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

$app->put('/reservas/{id}', function (Request $request, Response $response) {
    try {

        $errores = [];
        $connection = getConnection();

        $id = $request->getAttribute('id');
        $params = $request -> getParsedBody();
        
        $stmt = $connection->prepare("SELECT * FROM reservas WHERE id = :id");
        $stmt->bindParam(':id',$id);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute();


       //chequeo que exista el id de la reserva
        if($stmt->rowCount() == 0){

            $payload = json_encode([
                'message' => "No existe una reserva con el id " . $id,
                'status' => 'Error',
                'code' => 400,
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }


        //chequeo que la fecha de la reserva se menor a la fecha actual
        
        $reserva = $stmt->fetch();
        $fecha = $reserva['fecha_desde'];

        

        if($fecha <= date("Y-m-d")){

            $payload = json_encode([
                'message' => "La reserva ya comenzo y no puede modificarse",
                'status' => 'Error',
                'code' => 400,
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);

        }


        //chequeo que ninguno de los campos que mando para modificar este vacio
        $keys = ["propiedad_id","inquilino_id","fecha_desde","cantidad_noches"];
        $emptyFields = [];
        
        foreach ($keys as $key) {
            if (isset($params[$key]) && empty($params[$key])) {
                $emptyFields[] = $key;
            }
        }
        
        if(!empty($emptyFields)){
            $payload = json_encode([
                'message' => "Los siguientes campos estan vacios: " . implode(", ",$emptyFields),
                'status' => 'Error',
                'code' => 400,
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        //en caso de querer modificar la propiedad, chequeo que exista el 
        //y que este disponible

        if(isset($params['propiedad_id'])){
            $stmt = $connection->prepare("SELECT disponible FROM propiedades 
                                        WHERE id = :propiedad_id
                                        AND disponible = true");
            $stmt->bindParam(':propiedad_id', $params['propiedad_id']);
            $stmt->execute();

            if ($stmt->rowCount() == 0){

                $payload = json_encode([
                    'message' => 'La propiedad no está disponible para la fecha seleccionada o no existe.',
                    'status' => 'Error',
                    'code' => 400,
                ]);

                $response->getBody()->write($payload);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);

            }

        }

        //si modifico la cantidad de noches, también debo modificar
        //el valor total
        if(isset($params['cantidad_noches'])){

            $valorPorNoche = $reserva['valor_total'] / $reserva['cantidad_noches'];

            $params['valor_total'] = $valorPorNoche * $params['cantidad_noches'];

        }


        //si mando un inquilino, verifico si esta activo.
        if(isset($params['inquilino_id'])){

            $stmt = $connection->prepare("SELECT * FROM inquilinos WHERE id = :inquilino_id AND activo = true");
            $stmt->bindParam(':inquilino_id', $params['inquilino_id']);
            $stmt->execute();

            if ($stmt->rowCount() == 0) {

                $payload = json_encode([
                    'message' => 'El inquilino con ese id no esta activo o no existe.',
                    'status' => 'Error',
                    'code' => 400,
                ]);

                $response->getBody()->write($payload);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);

            }

        }

        $sql = "UPDATE reservas SET ";

        foreach ($params as $campo => $valor) {
            $sql .= "`$campo` = :$campo, ";
        }

        // eliminamos la coma al final
        $sql = rtrim($sql, ', ');

        $sql .= " WHERE id = :id";

        $stmt = $connection->prepare($sql);

        //vinculo los valores a los parámetros
        $stmt->bindParam(':id', $id);
        foreach ($params as $campo => $valor) {
            $stmt->bindParam(":$campo", $params[$campo]);
        }

        $stmt->execute();

        $payload = json_encode([
            'message' => 'La reserva se actualizo en la base de datos correctamente.',
            'status' => 'success',
            'code' => 201,
            'data' => $params
        ]);

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);


    } catch (PDOException $e) {
        // Error de base de datos
        $payload = json_encode([
            'message' => 'Error de base de datos: ' . $e->getMessage(),
            'status' => 'Error',
            'code' => 500,
        ]);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

$app->delete('/reservas/{id}', function (Request $request, Response $response) {
    try {

        $errores = [];
        $connection = getConnection();

        $id = $request->getAttribute('id');
        $params = $request -> getParsedBody();
        
        $stmt = $connection->prepare("SELECT * FROM reservas WHERE id = :id");
        $stmt->bindParam(':id',$id);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute();


       //chequeo que exista el id de la reserva
        if($stmt->rowCount() == 0){

            $payload = json_encode([
                'message' => "No existe una reserva con el id " . $id,
                'status' => 'Error',
                'code' => 400,
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }


        //chequeo que la fecha de la reserva se menor a la fecha actual
        
        $reserva = $stmt->fetch();
        $fecha = $reserva['fecha_desde'];

        

        if($fecha <= date("Y-m-d")){

            $payload = json_encode([
                'message' => "La reserva ya comenzo y no puede eliminarse",
                'status' => 'Error',
                'code' => 400,
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);

        }

        $stmt = $connection->prepare('DELETE FROM reservas WHERE id =:id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $payload = json_encode([
            'message' => 'La reserva se elimino de la base de datos correctamente.',
            'status' => 'success',
            'code' => 201,
        ]);

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);


    } catch (PDOException $e) {
        // Error de base de datos
        $payload = json_encode([
            'message' => 'Error de base de datos: ' . $e->getMessage(),
            'status' => 'Error',
            'code' => 500,
        ]);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});


$app->run();
