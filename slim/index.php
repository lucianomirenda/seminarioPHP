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

//localidades

$app->get('/localidades',function (Request $request, Response $response){

    try{

        $connection = getConnection();
        $query = $connection->query('SELECT * FROM localidades');
        $data = $query -> fetchAll(PDO::FETCH_ASSOC);

        $payload = json_encode([
            'status' => 'success',
            'code' => 200,
            'data' => $data
        ]);
        
        $response->getBody()->write($payload);
        return $response->withHeader('Content+Type', 'application/json');
                
    } catch (PDOException $e){
        
        $payload = json_encode([
            'message' => 'Error de base de datos: ' . $e->getMessage(),
            'status' => 'Error',
            'code' => 500,
        ]);
        
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    } 
});

$app->post('/localidades',function(Request $request,Response $response){    

    $error = [];
    $params = $request->getParsedBody();
  
    if (!isset($params['nombre'])) {
    
        $error['nombre'] = "El nombre no está definido.";
    
    } else {
    
        if (empty($params['nombre'])) {
        
            $error['nombre'] = "El campo nombre está vacío."; 
            
        } else if (strlen($params['nombre']) > 50) {
        
            $error['nombre'] = "El campo nombre contiene más caracteres de los permitidos."; 
        }
    } 
    
    if(!empty($error)){
    
        $payload = json_encode([
            'status' => 'Error',
            'code' => 400,
            'data' => $error
        ]);
        $response->getBody()->write($payload);   
        return $response->withHeader('Content+Type', 'application/json'); 
    }
         
    try{
  
        $connection = getConnection();
        $stmt = $connection->prepare("SELECT COUNT(*) FROM localidades WHERE nombre = :nombre");
        $stmt->bindParam(':nombre',$params['nombre'],PDO::PARAM_STR);
        $stmt->execute();
        $count = $stmt->fetchColumn();
  
        if($count > 0){

            $error['nombre'] = "Ya se encuentra en la base de datos";

            $payload = json_encode([
                'status' => 'Error',
                'code' => 400,
                'data' => $error
            ]);
                            
            $response->getBody()->write($payload);   
            return $response->withHeader('Content-Type', 'application/json'); 
        }

        $stmt = $connection->prepare("INSERT INTO localidades (nombre) VALUES (:nombre)");
        $stmt->bindParam(':nombre',$params['nombre'],PDO::PARAM_STR);
        $stmt->execute();

        $payload = json_encode([
            'message' => 'La localidad se inserto correctamente',
            'status' => 'success',
            'code' => 200,
        ]);
            
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
  
    } catch (PDOException $e){
  
        $payload = json_encode([
            'message' => 'Error de base de datos: ' . $e->getMessage(),
            'status' => 'Error',
            'code' => 500,
        ]);

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
  
});

$app->put('/localidades/{id}',function(Request $request,Response $response){    

    $error = [];
    $params = $request->getParsedBody();
    $id = $request->getAttribute('id');
  
    if (!isset($params['nombre'])) {
    
        $error['nombre'] = "El nombre no está definido.";
    
    } else {
    
        if (empty($params['nombre'])) {
        
            $error['nombre'] = "El campo nombre está vacío."; 
            
        } else if (strlen($params['nombre']) > 50) {
        
            $error['nombre'] = "El campo nombre contiene más caracteres de los permitidos."; 
        }
    } 
    
    if(!empty($error)){
    
        $payload = json_encode([
            'status' => 'Error',
            'code' => 400,
            'data' => $error
        ]);
        $response->getBody()->write($payload);   
        return $response->withHeader('Content+Type', 'application/json'); 
    }
         
    try{
  
        $connection = getConnection();
        $stmt = $connection->prepare("SELECT COUNT(*) FROM localidades WHERE nombre = :nombre");
        $stmt->bindParam(':nombre',$params['nombre'],PDO::PARAM_STR);
        $stmt->execute();
        $count = $stmt->fetchColumn();
  
        if($count > 0){

            $error['nombre'] = "Ya se encuentra en la base de datos";

            $payload = json_encode([
                'status' => 'Error',
                'code' => 400,
                'data' => $error
            ]);
                            
            $response->getBody()->write($payload);   
            return $response->withHeader('Content-Type', 'application/json'); 
        }

        $stmt = $connection->prepare("UPDATE localidades SET nombre = :nombre WHERE id = :id");
        $stmt->bindParam(':nombre',$params['nombre'],PDO::PARAM_STR);
        $stmt->bindParam(':id',$id,PDO::PARAM_STR);
        $stmt->execute();

        $payload = json_encode([
            'message' => 'La localidad se actualizo correctamente',
            'status' => 'success',
            'code' => 200,
        ]);
            
        $response->getBody()->write($payload);
        return $response->withHeader('Content+Type', 'application/json');
  
    } catch (PDOException $e){
  
        $payload = json_encode([
            'message' => 'Error de base de datos: ' . $e->getMessage(),
            'status' => 'Error',
            'code' => 500,
        ]);

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
  
});

$app->delete('/localidades/{id}', function (Request $request, Response $response) {
    
    $id = $request->getAttribute('id');

    try {

        $connection = getConnection();
        $stmt = $connection->prepare('SELECT COUNT(*) FROM propiedades WHERE localidad_id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $refencias = $stmt->fetchColumn();

        if ($refencias > 0) {

            $payload = json_encode([
				'message' => 'Esta localidad esta siendo usada por la tabla "propiedades".',
				'status' => 'Error',
				'code' => 400,
		    ]);

            $response->getBody()->write($payload);   
            return $response->withHeader('Content-Type', 'application/json'); 
                            
        } else {

            $stmt = $connection->prepare('DELETE FROM localidades WHERE id =:id');
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $payload = json_encode([
				'message' => 'La localidad fue eliminada',
				'status' => 'success',
				'code' => 200,
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content+Type', 'application/json');
        }

    } catch (PDOException $e) {

        $payload = json_encode([
            'message' => 'Error de base de datos: ' . $e->getMessage(),
            'status' => 'Error',
            'code' => 500,
        ]);

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

//tipos propiedad

$app->get('/tipos_propiedad',function (Request $request, Response $response){

    try{

        $connection = getConnection();
        $query = $connection->query('SELECT * FROM tipo_propiedades');
        $data = $query -> fetchAll(PDO::FETCH_ASSOC);

        $payload = json_encode([
            'status' => 'success',
            'code' => 200,
            'data' => $data
        ]);
        
        $response->getBody()->write($payload);
        return $response->withHeader('Content+Type', 'application/json');
				
    } catch (PDOException $e){
        
        $payload = json_encode([
            'message' => 'Error de base de datos: ' . $e->getMessage(),
            'status' => 'Error',
            'code' => 500,
        ]);

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
	} 
});

$app->post('/tipos_propiedad',function(Request $request,Response $response){    

    $error = [];
    $params = $request->getParsedBody();
  
    if (!isset($params['nombre'])) {
    
        $error['nombre'] = "El nombre no está definido.";
    
    } else {
    
        if (empty($params['nombre'])) {
        
            $error['nombre'] = "El campo nombre está vacío."; 
            
        } else if (strlen($params['nombre']) > 50) {
        
            $error['nombre'] = "El campo nombre contiene más caracteres de los permitidos."; 
        }
    } 
    
    if(!empty($error)){
    
        $payload = json_encode([
            'status' => 'Error',
            'code' => 400,
            'data' => $error
        ]);
        $response->getBody()->write($payload);   
        return $response->withHeader('Content-Type', 'application/json'); 
    }
         
    try{
  
        $connection = getConnection();
        $stmt = $connection->prepare("SELECT COUNT(*) FROM tipo_propiedades WHERE nombre = :nombre");
        $stmt->bindParam(':nombre',$params['nombre'],PDO::PARAM_STR);
        $stmt->execute();
        $count = $stmt->fetchColumn();
  
        if($count > 0){

            $error['nombre'] = "Ya se encuentra en la base de datos";

            $payload = json_encode([
                'status' => 'Error',
                'code' => 400,
                'data' => $error
            ]);
                            
            $response->getBody()->write($payload);   
            return $response->withHeader('Content-Type', 'application/json'); 
        }

        $stmt = $connection->prepare("INSERT INTO tipo_propiedades (nombre) VALUES (:nombre)");
        $stmt->bindParam(':nombre',$params['nombre'],PDO::PARAM_STR);
        $stmt->execute();

        $payload = json_encode([
            'message' => 'El tipo de propiedad se inserto correctamente',
            'status' => 'success',
            'code' => 200,
        ]);
            
        $response->getBody()->write($payload);
        return $response->withHeader('Content+Type', 'application/json');
  
    } catch (PDOException $e){
  
        $payload = json_encode([
            'message' => 'Error de base de datos: ' . $e->getMessage(),
            'status' => 'Error',
            'code' => 500,
        ]);

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
  
});

$app->put('/tipos_propiedad/{id}',function(Request $request,Response $response){    

    $error = [];
    $params = $request->getParsedBody();
    $id = $request->getAttribute('id');
  
    if (!isset($params['nombre'])) {
    
        $error['nombre'] = "El nombre no está definido.";
    
    } else {
    
        if (empty($params['nombre'])) {
        
            $error['nombre'] = "El campo nombre está vacío."; 
            
        } else if (strlen($params['nombre']) > 50) {
        
            $error['nombre'] = "El campo nombre contiene más caracteres de los permitidos."; 
        }
    } 
    
    if(!empty($error)){
    
        $payload = json_encode([
            'status' => 'Error',
            'code' => 400,
            'data' => $error
        ]);
        $response->getBody()->write($payload);   
        return $response->withHeader('Content-Type', 'application/json'); 
    }
         
    try{
  
        $connection = getConnection();
        $stmt = $connection->prepare("SELECT COUNT(*) FROM tipo_propiedades WHERE nombre = :nombre");
        $stmt->bindParam(':nombre',$params['nombre'],PDO::PARAM_STR);
        $stmt->execute();
        $count = $stmt->fetchColumn();
  
        if($count > 0){

            $error['nombre'] = "Ya se encuentra en la base de datos";

            $payload = json_encode([
                'status' => 'Error',
                'code' => 400,
                'data' => $error
            ]);
                            
            $response->getBody()->write($payload);   
            return $response->withHeader('Content-Type', 'application/json'); 
        }

        $stmt = $connection->prepare("UPDATE tipo_propiedades SET nombre = :nombre WHERE id = :id");
        $stmt->bindParam(':nombre',$params['nombre'],PDO::PARAM_STR);
        $stmt->bindParam(':id',$id,PDO::PARAM_STR);
        $stmt->execute();

        $payload = json_encode([
            'message' => 'El tipo de propiedad se edito correctamente',
            'status' => 'success',
            'code' => 200,
        ]);
            
        $response->getBody()->write($payload);
        return $response->withHeader('Content+Type', 'application/json');
  
    } catch (PDOException $e){
  
        $payload = json_encode([
            'message' => 'Error de base de datos: ' . $e->getMessage(),
            'status' => 'Error',
            'code' => 500,
        ]);

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
  
});

$app->delete('/tipos_propiedad/{id}', function (Request $request, Response $response) {
    
    $id = $request->getAttribute('id');

    try {

        $connection = getConnection();
        $stmt = $connection->prepare('SELECT COUNT(*) FROM propiedades WHERE tipo_propiedad_id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $refencias = $stmt->fetchColumn();

        if ($refencias > 0) {

            $payload = json_encode([
				'message' => 'El tipo de propiedad esta siendo utilizado por la tabla "propiedades".',
				'status' => 'Error',
				'code' => 400,
		    ]);

            $response->getBody()->write($payload);   
            return $response->withHeader('Content-Type', 'application/json'); 
                            
        } else {

            $stmt = $connection->prepare('DELETE FROM tipo_propiedades WHERE id =:id');
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $payload = json_encode([
				'message' => 'El tipo de propiedad fue eliminado',
				'status' => 'success',
				'code' => 200,
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content+Type', 'application/json');
        }

    } catch (PDOException $e) {

        $payload = json_encode([
            'message' => 'Error de base de datos: ' . $e->getMessage(),
            'status' => 'Error',
            'code' => 500,
        ]);

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

//inquilinos

$app->get('/inquilinos',function (Request $request, Response $response){

    try{

        $connection = getConnection();
        $query = $connection->query('SELECT * FROM inquilinos');
        $data = $query -> fetchAll(PDO::FETCH_ASSOC);

        $payload = json_encode([
            'status' => 'success',
            'code' => 200,
            'data' => $data
        ]);
        
        $response->getBody()->write($payload);
        return $response->withHeader('Content+Type', 'application/json');
				
    } catch (PDOException $e){
        
        $payload = json_encode([
            'message' => 'Error de base de datos: ' . $e->getMessage(),
            'status' => 'Error',
            'code' => 500,
        ]);

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
	} 
});

$app->get('/inquilinos/{id}',function (Request $request, Response $response){

    try{

        $connection = getConnection();
        $id = $request->getAttribute('id');
        $stmt = $connection->prepare("SELECT * FROM inquilinos WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $data = $stmt -> fetchAll(PDO::FETCH_ASSOC);

        $payload = json_encode([
            'status' => 'success',
            'code' => 200,
            'data' => $data
        ]);
                
        $response->getBody()->write($payload);
        return $response->withHeader('Content+Type', 'application/json');
				
    } catch (PDOException $e){
        
        $payload = json_encode([
            'message' => 'Error de base de datos: ' . $e->getMessage(),
            'status' => 'Error',
            'code' => 500,
        ]);

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
	} 
});

$app->get('/inquilinos/{id}/reservas',function (Request $request, Response $response){

    try{

        $connection = getConnection();
        $id = $request->getAttribute('id');
        $stmt = $connection->prepare("SELECT * FROM reservas WHERE inquilino_id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $data = $stmt -> fetchAll(PDO::FETCH_ASSOC);

        $payload = json_encode([
            'status' => 'success',
            'code' => 200,
            'data' => $data
        ]);
                
        $response->getBody()->write($payload);
        return $response->withHeader('Content+Type', 'application/json');
				
    } catch (PDOException $e){
        
        $payload = json_encode([
            'message' => 'Error de base de datos: ' . $e->getMessage(),
            'status' => 'Error',
            'code' => 500,
        ]);

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
	} 
});

$app->post('/inquilinos',function(Request $request,Response $response){
    
    $params = $request->getParsedBody();
    
    $error = [];   

    
    if (!isset($params['nombre'])) {
        $error['nombre'] = "El campo no está definido.";
    
    } else {
    
        if (empty($params['nombre'])) {
            $error['nombre'] = "El campo está vacío."; 
            
        } else if (strlen($params['nombre']) > 25) {
            $error['nombre'] = "El campo contiene más caracteres de los permitidos."; 
        }
    } 

    if (!isset($params['apellido'])) {
        $error['apellido'] = "El campo no está definido.";
    
    } else {
    
        if (empty($params['apellido'])) {
            $error['apellido'] = "El campo está vacío."; 
            
        } else if (strlen($params['apellido']) > 15) {
            $error['apellido'] = "El campo contiene más caracteres de los permitidos."; 
        }
    } 
    
    if (!isset($params['documento'])) {
        $error['documento'] = "El campo no está definido.";
    
    } else {
    
        if (empty($params['documento'])) {
            $error['documento'] = "El campo está vacío."; 
            
        } else if (strlen($params['documento']) > 25) {
            $error['documento'] = "El campo contiene más caracteres de los permitidos."; 
        }
    } 

    if (!isset($params['email'])) {
        $error['email'] = "El campo no está definido.";
    
    } else {
    
        if (empty($params['email'])) {
            $error['email'] = "El campo  está vacío."; 
            
        } else if (strlen($params['email']) > 20) {
            $error['email'] = "El campo contiene más caracteres de los permitidos."; 
        }
    } 
    
    if (!isset($params['activo'])) {
        $error['activo'] = "El campo no está definido.";
    
    } else {
    
        if ($params['activo']==="") {
            $error['activo'] = "El campo está vacío.";  
        } 
    } 

    try{
    
        $connection = getConnection();
        $stmt = $connection->prepare("SELECT COUNT(*) FROM inquilinos WHERE documento = :documento");
        $stmt->bindParam(':documento',$params['documento'],PDO::PARAM_STR);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        
        if($count > 0){
            $error['documento'] = "Ya se encuentra en la base de datos";
        }
        
        if(!empty($error)){

            $payload = json_encode([
                'status' => 'Error',
                'code' => 400,
                'data' => $error
            ]);
        
            $response->getBody()->write($payload);   
            return $response->withHeader('Content-Type', 'application/json');
        }   
			      
        $stmt = $connection->prepare("INSERT INTO inquilinos (nombre,apellido,email,documento,activo)
                                    VALUES (:nombre,:apellido,:email,:documento,:activo)");

        $stmt->bindParam(':nombre',$params['nombre']);
        $stmt->bindParam(':apellido',$params['apellido']);
        $stmt->bindParam(':email',$params['email']);
        $stmt->bindParam(':documento',$params['documento']);
        $stmt->bindParam(':activo',$params['activo']);
        $stmt->execute();
      
        $payload = json_encode([
                'message' => 'El inquilino se inserto correctamente.',
                'status' => 'success',
                'code' => 200
        ]);
        
        $response->getBody()->write($payload);
        return $response->withHeader('Content+Type', 'application/json');

    } catch (PDOException $e){

        $payload = json_encode([
            'message' => 'Error de base de datos: ' . $e->getMessage(),
            'status' => 'Error',
            'code' => 500,
        ]);

		$response->getBody()->write($payload);
		return $response->withHeader('Content-Type', 'application/json');
	 }

});

$app->put('/inquilinos/{id}',function(Request $request,Response $response){
    

    $id = $request->getAttribute('id');
    $params = $request->getParsedBody();
    $error = [];

    if (isset($params['nombre'])) {
    
        if (empty($params['nombre'])) {
            $error['nombre'] = "El campo está vacío."; 
            
        } else if (strlen($params['nombre']) > 25) {
            $error['nombre'] = "El campo contiene más caracteres de los permitidos."; 
        }
    } 

    if (isset($params['apellido'])) {
    
        if (empty($params['apellido'])) {
            $error['apellido'] = "El campo está vacío."; 
            
        } else if (strlen($params['apellido']) > 15) {
            $error['apellido'] = "El campo contiene más caracteres de los permitidos."; 
        }
    } 
    
    if (isset($params['documento'])) {
    
        if (empty($params['documento'])) {
            $error['documento'] = "El campo está vacío."; 
            
        } else if (strlen($params['documento']) > 25) {
            $error['documento'] = "El campo contiene más caracteres de los permitidos."; 
        }
    } 

    if (isset($params['email'])) {
    
        if (empty($params['email'])) {
            $error['email'] = "El campo  está vacío."; 
            
        } else if (strlen($params['email']) > 20) {
            $error['email'] = "El campo contiene más caracteres de los permitidos."; 
        }
    } 
    
    if (isset($params['activo'])) {
    
        if ($params['activo']==="") {
            $error['activo'] = "El campo está vacío.";  
        } 
    } 

    try{
    
        $connection = getConnection();
        $stmt = $connection->prepare("SELECT COUNT(*) FROM inquilinos WHERE documento = :documento");
        $stmt->bindParam(':documento',$params['documento'],PDO::PARAM_STR);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        
        if($count > 0){
            $error['documento'] = "Ya se encuentra en la base de datos";
        }
        
        if(!empty($error)){

            $payload = json_encode([
                'status' => 'Error',
                'code' => 400,
                'data' => $error
            ]);
        
            $response->getBody()->write($payload);   
            return $response->withHeader('Content-Type', 'application/json');
        }   

        $sql = "UPDATE inquilinos SET ";

        if(isset($params['nombre'])){
            $sql .= "nombre = :nombre, ";
        }

        if(isset($params['apellido'])){
            $sql .= "apellido = :apellido, ";
        }

        if(isset($params['documento'])){
            $sql .= "documento = :documento, ";
        }

        if(isset($params['email'])){
            $sql .= "email = :email, ";
        }	      

        if(isset($params['activo'])){
            $sql .= "activo = :activo, ";
        }	   

        $sql = rtrim($sql, ', ');

        $sql .= " WHERE id = :id";

        $stmt = $connection->prepare($sql);

        $stmt->bindParam(':id', $id);

        if(isset($params['nombre'])){
            $stmt->bindParam(":nombre", $params['nombre']);
        }

        if(isset($params['apellido'])){
            $stmt->bindParam(":apellido", $params['apellido']);
        }

        if(isset($params['documento'])){
            $stmt->bindParam(":documento", $params['documento']);
        }

        if(isset($params['email'])){
            $stmt->bindParam(":email", $params['email']);
        }	
        
        if(isset($params['activo'])){
            $stmt->bindParam(":activo", $params['activo']);
        }


        $stmt->execute();
      
        $payload = json_encode([
                'message' => 'El inquilino se actualizo correctamente.',
                'status' => 'success',
                'code' => 200
        ]);
        
        $response->getBody()->write($payload);
        return $response->withHeader('Content+Type', 'application/json');

    } catch (PDOException $e){

        $payload = json_encode([
            'message' => 'Error de base de datos: ' . $e->getMessage(),
            'status' => 'Error',
            'code' => 500,
        ]);

		$response->getBody()->write($payload);
		return $response->withHeader('Content-Type', 'application/json');
	 }

});

$app->delete('/inquilinos/{id}', function (Request $request, Response $response) {
    
    $id = $request->getAttribute('id');

    try {

        $connection = getConnection();
        $stmt = $connection->prepare('SELECT COUNT(*) FROM reservas WHERE inquilino_id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $refencias = $stmt->fetchColumn();

        if ($refencias > 0) {

            $payload = json_encode([
				'message' => 'El inquilino no puede eliminarse porque esta siendo utilizado por la tabla "reservas".',
				'status' => 'Error',
				'code' => 400,
		    ]);

            $response->getBody()->write($payload);   
            return $response->withHeader('Content-Type', 'application/json'); 
                            
        } else {

            $stmt = $connection->prepare('DELETE FROM inquilinos WHERE id =:id');
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $payload = json_encode([
				'message' => 'El inquilino fue eliminado',
				'status' => 'success',
				'code' => 200,
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content+Type', 'application/json');
        }

    } catch (PDOException $e) {

        $payload = json_encode([
            'message' => 'Error de base de datos: ' . $e->getMessage(),
            'status' => 'Error',
            'code' => 500,
        ]);

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

//propiedades

$app->get('/propiedades', function (Request $request, Response $response){

    try{

        $connection = getConnection();
        $params = $request->getQueryParams();
        
        $sql = 'SELECT p.*, l.nombre AS localidad, tp.nombre AS tipo_de_propiedad
                        FROM propiedades p
                        INNER JOIN localidades l ON p.localidad_id = l.id
                        INNER JOIN tipo_propiedades tp ON p.tipo_propiedad_id = tp.id
                        WHERE 1 = 1';
                        
        if (isset($params['disponible'])) {
            $sql .= ' AND p.disponible = :disponible';
        }
                        
        if(isset($params['localidad_id'])){
            $sql .= " AND p.localidad_id = :localidad_id";
        }

                        
        if(isset($params['cantidad_huespedes'])){
            $sql .= " AND p.cantidad_huespedes >= :cantidad_huespedes";
        }

                        
        if(isset($params['fecha_inicio_disponibilidad'])){
            $sql .= " AND p.fecha_inicio_disponibilidad <= :fecha_inicio_disponibilidad";
        }

        $stmt = $connection->prepare($sql);
        
        if(isset($params['localidad_id'])){
            $stmt->bindParam(":localidad_id", $params['localidad_id']);
        }

        if(isset($params['cantidad_huespedes'])){
            $stmt->bindParam(":cantidad_huespedes", $params['cantidad_huespedes']);
        }

        if(isset($params['disponible'])){
            $stmt->bindParam(":disponible", $params['disponible']);
        }

        if(isset($params['fecha_inicio_disponibilidad'])){
            $stmt->bindParam(":fecha_inicio_disponibilidad", $params['fecha_inicio_disponibilidad']);
        }
        
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $payload = json_encode([
            'status' => 'success',
            'code' => 200,
            'data' => $data
        ]);
                
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
                
    } catch (PDOException $e){
        
        $payload = json_encode([
            'message' => 'Error de base de datos: ' . $e->getMessage(),
            'status' => 'Error',
            'code' => 500,
        ]);

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    } 
});


$app->get('/propiedades/{id}',function (Request $request, Response $response){

    try{

        $connection = getConnection();
        $id = $request->getAttribute('id');
        $stmt = $connection->prepare("SELECT p.*, l.nombre AS localidad, tp.nombre AS tipo_de_propiedad
                FROM propiedades p
                INNER JOIN localidades l ON p.localidad_id = l.id
                INNER JOIN tipo_propiedades tp ON p.tipo_propiedad_id = tp.id
                WHERE p.id = :id");
                
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $data = $stmt -> fetchAll(PDO::FETCH_ASSOC);

        $payload = json_encode([
            'status' => 'success',
            'code' => 200,
            'data' => $data
        ]);
                
        $response->getBody()->write($payload);
        return $response->withHeader('Content+Type', 'application/json');
				
    } catch (PDOException $e){
        
        $payload = json_encode([
            'message' => 'Error de base de datos: ' . $e->getMessage(),
            'status' => 'Error',
            'code' => 500,
        ]);

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
	} 
});

$app->post('/propiedades',function(Request $request,Response $response){
    
    $params = $request->getParsedBody();
    
    $error = [];   

    //campos requeridos

    if (!isset($params['domicilio'])) {
        $error['domicilio'] = "El campo no está definido.";
    
    } else {
        if (empty($params['domicilio'])) {
            $error['nombre'] = "El campo está vacío."; 
        } 
    } 

    if (!isset($params['localidad_id'])) {
        $error['localidad_id'] = "El campo no está definido.";
    
    } else {
        if (empty($params['localidad_id'])) {
            $error['localidad_id'] = "El campo está vacío."; 
        } 
    } 

    if (!isset($params['cantidad_huespedes'])) {
        $error['cantidad_huespedes'] = "El campo no está definido.";
    
    } else {
        if (empty($params['cantidad_huespedes'])) {
            $error['cantidad_huespedes'] = "El campo esta vacío."; 
        } 
    }

    if (!isset($params['fecha_inicio_disponibilidad'])) {
        $error['fecha_inicio_disponibilidad'] = "El campo no está definido.";
    
    } else {
        if (empty($params['fecha_inicio_disponibilidad'])) {
            $error['fecha_inicio_disponibilidad'] = "El campo esta vacío."; 
        } 
    }

    if (!isset($params['disponible'])) {
        $error['disponible'] = "El campo no está definido.";
    
    } else {
        if ($params['disponible'] === "") {
            $error['disponible'] = "El campo esta vacío."; 
        } 
    }

    if (!isset($params['cantidad_dias'])) {
        $error['cantidad_dias'] = "El campo no está definido.";
    
    } else {
        if (empty($params['cantidad_dias'])) {
            $error['cantidad_dias'] = "El campo esta vacío."; 
        } 
    }

    if (!isset($params['valor_noche'])) {
        $error['valor_noche'] = "El campo no está definido.";
    
    } else {
        if (empty($params['valor_noche'])) {
            $error['valor_noche'] = "El campo esta vacío."; 
        } 
    }

    if (!isset($params['tipo_propiedad_id'])) {
        $error['tipo_propiedad_id'] = "El campo no está definido.";
    
    } else {
        if (empty($params['tipo_propiedad_id'])) {
            $error['tipo_propiedad_id'] = "El campo esta vacío."; 
        } 
    }

    //campos no requeridos

    if (isset($params['cantidad_habitaciones'])&& empty($params['cantidad_habitaciones'])) {
        $error['cantidad_habitaciones'] = "El campo esta vacío.";
    
    } 

    if (isset($params['cantidad_banios'])&& empty($params['cantidad_banios'])) {
        $error['cantidad_banios'] = "El campo esta vacío.";
    
    } 

    if (isset($params['cochera'])&& ($params['cochera'] === "")) {
        $error['cochera'] = "El campo esta vacío.";
    } 

    
    $imagenData = null;
    $tipoImagen = null;

    if (isset($params['imagen']) && !empty($params['imagen'])) {
        // Verificar si la cadena es una imagen Base64 válida
        if (preg_match('/^data:image\/(\w+);base64,/', $params['imagen'], $matches)) {
            $tipoImagen = $matches[1];
            $imagenData = base64_decode(substr($params['imagen'], strpos($params['imagen'], ',') + 1));

            if ($imagenData === false) {
                $error['imagen'] = 'Error al decodificar la imagen Base64';
            }
        } else {
            $error['imagen'] = 'Formato de imagen Base64 inválido';
        }
    }

    try{

        $connection = getConnection();

        if(array_key_exists('localidad_id', $params)&&(!empty($params['localidad_id']))){
    
            $stmt = $connection->prepare("SELECT COUNT(*) FROM localidades WHERE id = :localidad_id");
            $stmt->bindParam(':localidad_id',$params['localidad_id'],PDO::PARAM_STR);
            $stmt->execute();
            $count = $stmt->fetchColumn();
            
            if($count == 0){
                $error['localidad_id'] = "No existe esa localidad";
            }

        }

        if(array_key_exists('tipo_propiedad_id', $params)&&(!empty($params['tipo_propiedad_id']))){
        
            $stmt = $connection->prepare("SELECT COUNT(*) FROM tipo_propiedades WHERE id = :tipo_propiedad_id");
            $stmt->bindParam(':tipo_propiedad_id',$params['tipo_propiedad_id'],PDO::PARAM_STR);
            $stmt->execute();
            $count = $stmt->fetchColumn();
            
            if($count == 0){
                $error['tipo_propiedad_id'] = "No existe esa propiedad";
            }

        }
        
        $disponible = isset($params['disponible']) ? 1 : 0;
        $cochera = isset($params['cochera']) ? 1 : 0;
      
        $stmt = $connection->prepare("INSERT INTO propiedades(domicilio,localidad_id,cantidad_habitaciones,cantidad_banios,cochera,cantidad_huespedes,fecha_inicio_disponibilidad,cantidad_dias,disponible,valor_noche,tipo_propiedad_id,imagen,tipo_imagen)
                                    VALUES (:domicilio, :localidad_id, :cantidad_habitaciones, :cantidad_banios, :cochera, :cantidad_huespedes, :fecha_inicio_disponibilidad, :cantidad_dias, :disponible, :valor_noche, :tipo_propiedad_id, :imagen, :tipo_imagen)");

        $stmt->bindParam(':domicilio',$params['domicilio']);
        $stmt->bindParam(':localidad_id',$params['localidad_id']);
        $stmt->bindParam(':cantidad_habitaciones',$params['cantidad_habitaciones']);
        $stmt->bindParam(':cantidad_banios',$params['cantidad_banios']);
        $stmt->bindParam(':cochera', $cochera);
        $stmt->bindParam(':cantidad_huespedes',$params['cantidad_huespedes']);
        $stmt->bindParam(':fecha_inicio_disponibilidad',$params['fecha_inicio_disponibilidad']);
        $stmt->bindParam(':cantidad_dias',$params['cantidad_dias']);
        $stmt->bindParam(':disponible',$disponible);
        $stmt->bindParam(':valor_noche',$params['valor_noche']);
        $stmt->bindParam(':tipo_propiedad_id',$params['tipo_propiedad_id']);
        $stmt->bindParam(':imagen', $imagenData, PDO::PARAM_LOB);
        $stmt->bindParam(':tipo_imagen', $tipoImagen, PDO::PARAM_STR);
        $stmt->execute();
      
        $payload = json_encode([
                'message' => 'La propiedad se inserto correctamente.',
                'status' => 'success',
                'code' => 200
        ]);
        
        $response->getBody()->write($payload);
        return $response->withHeader('Content+Type', 'application/json');

    } catch (PDOException $e){

        $payload = json_encode([
            'message' => 'Error de base de datos: ' . $e->getMessage(),
            'status' => 'Error',
            'code' => 500,
        ]);

		$response->getBody()->write($payload);
		return $response->withHeader('Content-Type', 'application/json');
	 }

});

$app->put('/propiedades/{id}',function(Request $request,Response $response){
    
    $id = $request->getAttribute('id');
    $params = $request->getParsedBody();
    $error = [];  

    if (isset($params['domicilio'])&& empty($params['domicilio'])) {
        $error['domicilio'] = "El campo esta vacío.";
    } 

    if (isset($params['localidad_id'])&& empty($params['localidad_id'])) {
        $error['localidad_id'] = "El campo esta vacío.";
    } 

    if (isset($params['fecha_inicio_disponibilidad'])&& empty($params['fecha_inicio_disponibilidad'])) {
        $error['fecha_inicio_disponibilidad'] = "El campo esta vacío.";
    } 

    if (isset($params['cantidad_habitaciones']) && empty($params['cantidad_habitaciones'])) {
        $error['cantidad_habitaciones'] = "El campo esta vacío.";
    } 

    if (isset($params['cantidad_dias'])&& empty($params['cantidad_dias'])) {
        $error['cantidad_dias'] = "El campo esta vacío.";
    } 

    if (isset($params['disponible'])&& $params['disponible'] === "") {
        $error['disponible'] = "El campo esta vacío.";
    } 

    if (isset($params['valor_noche'])&& empty($params['valor_noche'])) {
        $error['valor_noche'] = "El campo esta vacío.";
    }

    if (isset($params['tipo_propiedad_id'])&& empty($params['tipo_propiedad_id'])) {
        $error['tipo_propiedad_id'] = "El campo esta vacío.";
    }

    if (isset($params['cantidad_habitaciones'])&& empty($params['cantidad_habitaciones'])) {
        $error['cantidad_habitaciones'] = "El campo esta vacío.";
    }

    if (isset($params['cantidad_banios'])&& empty($params['cantidad_banios'])) {
        $error['cantidad_banios'] = "El campo esta vacío.";
    } 

    if (isset($params['cochera'])&& ($params['cochera'] === "")) {
        $error['cochera'] = "El campo esta vacío.";
    } 

    
    if (isset($params['imagen'])&& empty($params['imagen'])) {
        $error['imagen'] = "El campo esta vacío.";
    }
    
    $imagenData = null;
    $tipoImagen = null;

    if (isset($params['imagen']) && !empty($params['imagen'])) {
        // Verificar si la cadena es una imagen Base64 válida
        if (preg_match('/^data:image\/(\w+);base64,/', $params['imagen'], $matches)) {
            $tipoImagen = $matches[1];
            $imagenData = base64_decode(substr($params['imagen'], strpos($params['imagen'], ',') + 1));

            if ($imagenData === false) {
                $error['imagen'] = 'Error al decodificar la imagen Base64';
            }
        } else {
            $error['imagen'] = 'Formato de imagen Base64 inválido';
        }
    }

    try{

        $connection = getConnection();

        if(array_key_exists('localidad_id', $params)&&(!empty($params['localidad_id']))){
    
            $stmt = $connection->prepare("SELECT COUNT(*) FROM localidades WHERE id = :localidad_id");
            $stmt->bindParam(':localidad_id',$params['localidad_id'],PDO::PARAM_STR);
            $stmt->execute();
            $count = $stmt->fetchColumn();
            
            if($count == 0){
                $error['localidad_id'] = "No existe esa localidad";
            }

        }

        if(array_key_exists('tipo_propiedad_id', $params)&&(!empty($params['tipo_propiedad_id']))){
        
            $stmt = $connection->prepare("SELECT COUNT(*) FROM tipo_propiedades WHERE id = :tipo_propiedad_id");
            $stmt->bindParam(':tipo_propiedad_id',$params['tipo_propiedad_id'],PDO::PARAM_STR);
            $stmt->execute();
            $count = $stmt->fetchColumn();
            
            if($count == 0){
                $error['tipo_propiedad_id'] = "No existe esa propiedad";
            }

        }

        if(!empty($error)){

            $payload = json_encode([
                'status' => 'Error',
                'code' => 400,
                'data' => $error
            ]);
        
            $response->getBody()->write($payload);   
            return $response->withHeader('Content-Type', 'application/json');
        }   
      
        $sql = "UPDATE propiedades SET ";

        if(isset($params['domicilio'])){
            $sql .= "domicilio = :domicilio, ";
        }

        if(isset($params['localidad_id'])){
            $sql .= "localidad_id = :localidad_id, ";
        }

        if(isset($params['cantidad_huespedes'])){
            $sql .= "cantidad_huespedes = :cantidad_huespedes, ";
        }

        if(isset($params['fecha_inicio_disponibilidad'])){
            $sql .= "fecha_inicio_disponibilidad = :fecha_inicio_disponibilidad, ";
        }	      

        if(isset($params['cantidad_dias'])){
            $sql .= "cantidad_dias = :cantidad_dias, ";
        }	   

        if(isset($params['disponible'])){
            if ($params['disponible'] === 'true') {
                $sql .= "disponible = 1,";
            } else if ($params['disponible'] === 'false'){
                $sql .= "disponible = 0,";
            }
        }

        if(isset($params['valor_noche'])){
            $sql .= "valor_noche = :valor_noche, ";
        }

        if(isset($params['tipo_propiedad_id'])){
            $sql .= "tipo_propiedad_id = :tipo_propiedad_id, ";
        }

        if(isset($params['cantidad_habitaciones'])){
            $sql .= "cantidad_habitaciones = :cantidad_habitaciones, ";
        }

        if(isset($params['cantidad_banios'])){
            $sql .= "cantidad_banios = :cantidad_banios, ";
        }

        if(isset($params['cochera'])){
            if ($params['cochera'] === 'true') {
                $sql .= "cochera = 1,";
            } else if ($params['cochera'] === 'false'){
                $sql .= "cochera = 0,";
            }
        }

        if(isset($params['imagen'])){
            $sql .= "imagen = :imagen, ";
        }

        if(isset($params['tipo_imagen'])){
            $sql .= "tipo_imagen = :tipo_imagen, ";
        }

        $sql = rtrim($sql, ', ');

        $sql .= " WHERE id = :id";

        $stmt = $connection->prepare($sql);

        $stmt->bindParam(':id', $id);

        if(isset($params['domicilio'])){
            $stmt->bindParam(":domicilio", $params['domicilio']);
        }

        if(isset($params['localidad_id'])){
            $stmt->bindParam(":localidad_id", $params['localidad_id']);
        }

        if(isset($params['cantidad_huespedes'])){
            $stmt->bindParam(":cantidad_huespedes", $params['cantidad_huespedes']);
        }

        if(isset($params['fecha_inicio_disponibilidad'])){
            $stmt->bindParam(":fecha_inicio_disponibilidad", $params['fecha_inicio_disponibilidad']);
        }	      

        if(isset($params['cantidad_dias'])){
            $stmt->bindParam(":cantidad_dias", $params['cantidad_dias']);
        }

        if(isset($params['valor_noche'])){
            $stmt->bindParam(":valor_noche", $params['valor_noche']);
        }

        if(isset($params['tipo_propiedad_id'])){
            $stmt->bindParam(":tipo_propiedad_id", $params['tipo_propiedad_id']);
        }

        if(isset($params['cantidad_habitaciones'])){
            $stmt->bindParam(":cantidad_habitaciones", $params['cantidad_habitaciones']);
        }

        if(isset($params['cantidad_banios'])){
            $stmt->bindParam(":cantidad_banios", $params['cantidad_banios']);
        }

        if(isset($params['imagen'])){
            $stmt->bindParam(':imagen', $imagenData, PDO::PARAM_LOB);
        }

        if(isset($params['tipo_imagen'])){
            $stmt->bindParam(':tipo_imagen', $tipoImagen, PDO::PARAM_STR);
        }

        $stmt->execute();
      
        $payload = json_encode([
                'message' => 'La propiedad se actualizo correctamente.',
                'status' => 'success',
                'code' => 200
        ]);
        
        $response->getBody()->write($payload);
        return $response->withHeader('Content+Type', 'application/json');

    } catch (PDOException $e){

        $payload = json_encode([
            'message' => 'Error de base de datos: ' . $e->getMessage(),
            'status' => 'Error',
            'code' => 500,
        ]);

		$response->getBody()->write($payload);
		return $response->withHeader('Content-Type', 'application/json');
	 }

});

$app->delete('/propiedades/{id}', function (Request $request, Response $response) {
    
    $id = $request->getAttribute('id');

    try {

        $connection = getConnection();
        $stmt = $connection->prepare('SELECT COUNT(*) FROM reservas WHERE propiedad_id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $refencias = $stmt->fetchColumn();

        if ($refencias > 0) {

            $payload = json_encode([
				'message' => 'La propiedad no puede eliminarse porque esta siendo utilizada por la tabla "reservas".',
				'status' => 'Error',
				'code' => 400,
		    ]);

            $response->getBody()->write($payload);   
            return $response->withHeader('Content-Type', 'application/json'); 
                            
        } else {

            $stmt = $connection->prepare('DELETE FROM propiedades WHERE id =:id');
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $payload = json_encode([
				'message' => 'La propiedad fue eliminada',
				'status' => 'success',
				'code' => 200,
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content+Type', 'application/json');
        }

    } catch (PDOException $e) {

        $payload = json_encode([
            'message' => 'Error de base de datos: ' . $e->getMessage(),
            'status' => 'Error',
            'code' => 500,
        ]);

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

//reservas

$app->get('/reservas',function (Request $request, Response $response){

    try{

        $connection = getConnection();
        $query = $connection->query('SELECT * FROM reservas res 
                                    INNER JOIN propiedades p ON res.propiedad_id = p.id
                                    INNER JOIN inquilinos inq ON res.inquilino_id = inq.id');
        $data = $query -> fetchAll(PDO::FETCH_ASSOC);

        $payload = json_encode([
            'status' => 'success',
            'code' => 200,
            'data' => $data
        ]);
        
        $response->getBody()->write($payload);
        return $response->withHeader('Content+Type', 'application/json');
				
    } catch (PDOException $e){
        
        $payload = json_encode([
            'message' => 'Error de base de datos: ' . $e->getMessage(),
            'status' => 'Error',
            'code' => 500,
        ]);
        
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
	} 
});

$app->post('/reservas',function(Request $request,Response $response){
    
    $params = $request->getParsedBody();
    
    $requiredKeys = ["propiedad_id","inquilino_id","fecha_desde","cantidad_noches"];
    $error = [];   

    foreach ($requiredKeys as $key) {
        if (!array_key_exists($key, $params)) {
            $error[$key] = "No está definido.";
        } else {
            if (empty($params[$key])){
                $error[$key] = "Está vacío.";
            }
        }
        
    }

    try{
    
        $connection = getConnection();
        
        if(array_key_exists('propiedad_id', $params)&&(!empty($params['propiedad_id']))){
		        
            $stmt = $connection->prepare("SELECT COUNT(*) FROM propiedades 
                                        WHERE id = :propiedad_id
                                        AND disponible = true");
            $stmt->bindParam(':propiedad_id',$params['propiedad_id'],PDO::PARAM_STR);
            $stmt->execute();
            $count = $stmt->fetchColumn();
            
            if($count == 0){
                $error['propiedad_id'] = "Esa propiedad no esta disponible";
            }
        
        }
        
        if(array_key_exists('inquilino_id', $params)&&(!empty($params['inquilino_id']))){
        
	        $stmt = $connection->prepare("SELECT COUNT(*) FROM inquilinos WHERE id = :inquilino_id AND activo = true");
	        $stmt->bindParam(':inquilino_id',$params['inquilino_id'],PDO::PARAM_STR);
	        $stmt->execute();
	        $count = $stmt->fetchColumn();
	        
	        if($count == 0){
	            $error['inquilino_id'] = "El inquilino no esta activo";
	        }
        
        }
        
        if(!empty($error)){

            $payload = json_encode([
                'status' => 'Error',
                'code' => 400,
                'data' => $error
            ]);
        
            $response->getBody()->write($payload);   
            return $response->withHeader('Content-Type', 'application/json');
        }   
    
        $stmt = $connection->prepare("SELECT valor_noche FROM propiedades WHERE id = :propiedad_id");
        $stmt->bindParam(':propiedad_id', $params['propiedad_id']);
        $stmt->execute();
        
        $propiedad = $stmt->fetch(PDO::FETCH_ASSOC);
        $params['valor_total'] = $params['cantidad_noches'] * $propiedad['valor_noche'];

        $stmt = $connection->prepare("INSERT INTO reservas(propiedad_id,inquilino_id,fecha_desde,cantidad_noches,valor_total)
                                      VALUES (:propiedad_id, :inquilino_id, :fecha_desde, :cantidad_noches, :valor_total)");

        $stmt->bindParam(':propiedad_id', $params['propiedad_id']);
        $stmt->bindParam(':inquilino_id', $params['inquilino_id']);
        $stmt->bindParam(':fecha_desde', $params['fecha_desde']);
        $stmt->bindParam(':cantidad_noches', $params['cantidad_noches']);
        $stmt->bindParam(':valor_total', $params['valor_total']);
        $stmt->execute();
      
        $payload = json_encode([
                'message' => 'La reserva inserto correctamente.',
                'status' => 'success',
                'code' => 200
        ]);
        
        $response->getBody()->write($payload);
        return $response->withHeader('Content+Type', 'application/json');

    } catch (PDOException $e){

        $payload = json_encode([
            'message' => 'Error de base de datos: ' . $e->getMessage(),
            'status' => 'Error',
            'code' => 500,
        ]);

		$response->getBody()->write($payload);
		return $response->withHeader('Content-Type', 'application/json');
	 }

});

$app->put('/reservas/{id}',function(Request $request,Response $response){
    

    try{

        $connection = getConnection();

        $params = $request->getParsedBody();
        $id = $request->getAttribute('id');
        $error = [];   

        $stmt = $connection->prepare("SELECT * FROM reservas WHERE id = :id");
        $stmt->bindParam(':id',$id);
        $stmt->execute();

        $reserva = $stmt->fetch();
        $fecha = $reserva['fecha_desde'];

        if($fecha <= date("Y-m-d")){

            $error['fecha_desde'] = "La reserva ya comenzo y no puede modificarse"; 

            $payload = json_encode([
                'status' => 'Error',
                'code' => 400,
                'data' => $error
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');

        }


        $keys = ["propiedad_id","inquilino_id","fecha_desde","cantidad_noches"];


        foreach ($keys as $key) {
            if (isset($params[$key]) && empty($params[$key])) {
                $error[$key] = "Está vacío.";
            }
        }  

        if(array_key_exists('propiedad_id', $params)&&(!empty($params['propiedad_id']))){
		        
            $stmt = $connection->prepare("SELECT COUNT(*) FROM propiedades 
                                        WHERE id = :propiedad_id
                                        AND disponible = true");
            $stmt->bindParam(':propiedad_id',$params['propiedad_id'],PDO::PARAM_STR);
            $stmt->execute();
            $count = $stmt->fetchColumn();
            
            if($count == 0){
                $error['propiedad_id'] = "Esa propiedad no esta disponible";
            }
        
        }
        
        if(array_key_exists('inquilino_id', $params)&&(!empty($params['inquilino_id']))){
        
	        $stmt = $connection->prepare("SELECT COUNT(*) FROM inquilinos WHERE id = :inquilino_id AND activo = true");
	        $stmt->bindParam(':inquilino_id',$params['inquilino_id'],PDO::PARAM_STR);
	        $stmt->execute();
	        $count = $stmt->fetchColumn();
	        
	        if($count == 0){
	            $error['inquilino_id'] = "El inquilino no esta activo";
	        }
        
        }
        
        if(!empty($error)){

            $payload = json_encode([
                'status' => 'Error',
                'code' => 400,
                'data' => $error
            ]);
        
            $response->getBody()->write($payload);   
            return $response->withHeader('Content-Type', 'application/json');
        }   
    
        if(isset($params['cantidad_noches'])){

            $valorPorNoche = $reserva['valor_total'] / $reserva['cantidad_noches'];

            $params['valor_total'] = $valorPorNoche * $params['cantidad_noches'];

        }

        $sql = "UPDATE reservas SET ";

        if(isset($params['propiedad_id'])){
            $sql .= "propiedad_id = :propiedad_id, ";
        }

        if(isset($params['inquilino_id'])){
            $sql .= "inquilino_id = :inquilino_id, ";
        }

        if(isset($params['fecha_desde'])){
            $sql .= "fecha_desde = :fecha_desde, ";
        }

        if(isset($params['cantidad_noches'])){
            $sql .= "cantidad_noches = :cantidad_noches, ";
        }	      

        $sql = rtrim($sql, ', ');

        $sql .= " WHERE id = :id";

        $stmt = $connection->prepare($sql);

        $stmt->bindParam(':id', $id);

        if(isset($params['propiedad_id'])){
            $stmt->bindParam(":propiedad_id", $params['propiedad_id']);
        }

        if(isset($params['inquilino_id'])){
            $stmt->bindParam(":inquilino_id", $params['inquilino_id']);
        }

        if(isset($params['fecha_desde'])){
            $stmt->bindParam(":fecha_desde", $params['fecha_desde']);
        }

        if(isset($params['cantidad_noches'])){
            $stmt->bindParam(":cantidad_noches", $params['cantidad_noches']);
        }	      

        $stmt->execute();


        $payload = json_encode([
                'message' => 'La reserva actualizo correctamente.',
                'status' => 'success',
                'code' => 200
        ]);
        
        $response->getBody()->write($payload);
        return $response->withHeader('Content+Type', 'application/json');

    } catch (PDOException $e){

        $payload = json_encode([
            'message' => 'Error de base de datos: ' . $e->getMessage(),
            'status' => 'Error',
            'code' => 500,
        ]);

		$response->getBody()->write($payload);
		return $response->withHeader('Content-Type', 'application/json');
	 }

});

$app->delete('/reservas/{id}',function(Request $request,Response $response){

    try{

        $connection = getConnection();

        $params = $request->getParsedBody();
        $id = $request->getAttribute('id');
        $error = [];   

        $stmt = $connection->prepare("SELECT * FROM reservas WHERE id = :id");
        $stmt->bindParam(':id',$id);
        $stmt->execute();

        $reserva = $stmt->fetch();
        $fecha = $reserva['fecha_desde'];

        if($fecha <= date("Y-m-d")){

            $payload = json_encode([
                'message' => "La reserva ya comenzo y no puede eliminarse",
                'status' => 'Error',
                'code' => 400,
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');

        }


        $stmt = $connection->prepare('DELETE FROM reservas WHERE id =:id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $payload = json_encode([
            'message' => 'La reserva fue eliminada',
            'status' => 'success',
            'code' => 200,
        ]);

        $response->getBody()->write($payload);
        return $response->withHeader('Content+Type', 'application/json');

    } catch (PDOException $e){

        $payload = json_encode([
            'message' => 'Error de base de datos: ' . $e->getMessage(),
            'status' => 'Error',
            'code' => 500,
        ]);

		$response->getBody()->write($payload);
		return $response->withHeader('Content-Type', 'application/json');
	 }

});

$app->run();
