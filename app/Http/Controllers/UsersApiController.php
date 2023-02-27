<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UsersApiController extends Controller
{
    /*
***********************************************************************************************************************
*Autor:Emanuel Quinto Zagal                                                                                           *
*Descripcion:Servicio para crear usuarios en la plataforma moodle , consumo de api de moodle (core_user_create_users).*
*Contenido:Codigo documentado para fines de cambios en versiones de la plataforma de cesba virtual version 4.0.       *
*fecha:02/10/2022                                                                                                     *
************************************************************************************************************************
*/
    public function CrearAlumnos()
    {
        
        $body = json_decode(file_get_contents('php://input'), true); #Obtenemos el request en un areglo
        for ($i = 0; $i <=count($body); $i++) {  #Recoremos el areglo del request
            $resultado = array(); 
            try { #Control de fallos en la operacion 
            header('Content-Type: application/json');#Especificamos la cavecera
            /*pasamos la Url del dominio con argumentos necesarios para que se realice la consulta al endpoint , es necesario agregar estos datos:
              1:dominio = 'http://campusvirtual.cesba-queretaro.edu.mx/'
              2:ruta del webservice origen= 'webservice/rest/server.php?'
              3:Token que tiene que generarte moodle para acceso = 'wstoken=817c06ac6196681f2d8f7db8dc6401ab&'
              4:Formato = 'moodlewsrestformat=json&'
              5:Funcion generada en moodle: = 'wsfunction=core_user_get_users'
              6: consumos de parametros de la funcion de moodle de consulta = 'criteria[0][key]=&criteria[0][value]='
            */

           //*Consultamos si el Gmail Consumida ya se encuentra en Moodle
           $ch = curl_init();
           curl_setopt($ch, CURLOPT_URL,'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_user_get_users&criteria[0][key]=username&criteria[0][value]='.$body[$i]['Matricula']);
           curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
           curl_setopt($ch, CURLOPT_HEADER, 0);
           $data = curl_exec($ch);
           curl_close($ch);
           $matricula= '';
           $array = json_decode($data, true);
           foreach ($array['users'] as $mPersona) {
               $matricula = $mPersona['username'];
           }              
                if ($matricula == $body[$i]['Matricula']) { #Validamos si existe 
                    $obj = new \stdClass;
                    $obj->Matricula =$body[$i]['Matricula']; 
                    $obj->Existente= "404 Not Found";
                    $resultado[] = $obj;
                    return $resultado; # Muestra las los usuarios existetes
                }else { 
                    /***Abrimos comunicacion con moodle para  crear los nuevos usuarios en plataforma***/
                    #En el controlador principal esta creado una clase que es la contenedora de methodos y conexiones de moodle
                    #Se crea un Objecto de la clase MoodleRest y se pasa la Url como parametro , con dominio , token
                    $MoodleRest = new MoodleRest('http://35.223.167.202/webservice/rest/server.php', '839856d93785e4cfcade03aed4a20e90');
                    $new_group = array('users' => array( #Se crea un areglo con los campos que moodle exige para su conexion.
                        array( 
                            'username'       =>$body[$i]['Matricula'], #se inserta el usuario para loguearse obtenido del request 
                            'createpassword' =>1, #1 = Si para que moodle cree aleatoriamente contraseñas y mande la informacion por correo
                            'firstname' => $body[$i]['nombre'], #Se inserta el nombre 
                            'lastname' => $body[$i]['apellidos'], #Se inserta apellidos
                            'department' =>$body[$i]['carrera'], #opcional la carrera
                            'email' =>$body[$i]['Correo'], #Correo
                            'city' => 'Santiago de Queretaro', #Opcional la ciudad
                            'country' =>'MX' #Opcional el estado
                        ),
                    ));
                   
                   $return = $MoodleRest->request('core_user_create_users', $new_group, MoodleRest::METHOD_GET); #se concatenan los datos a enviar
                    $payload= $MoodleRest->getUrl(); #Se abre la comunicacion 
                    if ($payload != null) { #Evalua si hubo comunicacion
                        $obj = new \stdClass;
                        $obj->Matricula = $body[$i]['Matricula']; // muestra 8;
                        $obj->Create = "Create 200 Ok";
                        $resultado = $obj;
                        printf(json_encode($resultado));
                    }
                }           
        }
       catch (\Exception $e) {   
        echo   $e->getMessage(), "\n";  # valida si hubo error
    }
   }

 }
    /*
***********************************************************
* Consulta Por Matricula   (METHODO "GET")
***********************************************************
*/
    public  function getAlumnos($matricula){
    
        #methodo para de consulta
        $resultado = array(); 
        header('Content-Type: application/json');
        try { #Control de fallos en la operacion 
        $ch = curl_init();             #provamos en la url la matricula mandad por el cliente para validar si existe 
        curl_setopt($ch, CURLOPT_URL,'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_user_get_users&criteria[0][key]=username&criteria[0][value]='.$matricula);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $data = curl_exec($ch);
        curl_close($ch);
        $fullname='';
        $matricula='';    #inicializamos las variables para llenarlos con datos de la consulta
        $email='';
        $department='';
        $array = json_decode($data, true);
            foreach ($array['users'] as $mPersona) {
                $fullname = $mPersona['fullname'];
                $matricula = $mPersona['username'];   #recorremos el array y estraemos los datos en las variables inicalizadas
                $email = $mPersona['email'];
                $department = $mPersona['department'];
            }
            if ($fullname==null) {       #validamos si el nombre es null , para no seguir con la operacion
                $obj = new \stdClass;
                $obj->Matricula= $matricula;
                $obj->Error ="La matricula no Existe";
                $resultado[] = $obj;
                return $resultado;
            } else {                      #si no , ejecuta la consulta y se cargan los datos obtenidos de la consulta a moodle
                $obj = new \stdClass;
                $obj->User = $fullname;
                $obj->matricula = $matricula;          
                $obj->Email = $email;
                $obj->Carrera = $department;
                $resultado[] = $obj;
                return $resultado;
            }
        }
        catch (\Exception $e) {   
            echo   $e->getMessage(), "\n";  # valida si hubo error
        }
    }

    /*
***********************************************************
* Actualiza Usuario   (METHODO "PUT")
***********************************************************
*/
    public function UpdateUsers($matricula)   #obtenemos la matricula y realizar la actualizacion 
    { 
         $resultado = array(); 
         $body = json_decode(file_get_contents('php://input'), true); #descodificamos el json del cliente
         try { #Control de fallos en la operacion 
            $ch = curl_init();  #iniciamos la consulta y pasamos la url del cliente a consultar para obtener su id .
            curl_setopt($ch, CURLOPT_URL,'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_user_get_users&criteria[0][key]=username&criteria[0][value]='.$matricula);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            $data = curl_exec($ch);
            curl_close($ch);
            $id ='';            #inicializamos la variable id
            $array = json_decode($data, true);    
                    foreach ($array['users'] as $mPersona) {    #recoremoos el array con el dato del id 
                        $id = $mPersona['id'];
                    }
                     
                    if ($id ==true){ //*si la consulta por id  es true-->ejecuta el cambio
                        $MoodleRest = new MoodleRest('http://35.223.167.202/webservice/rest/server.php', '839856d93785e4cfcade03aed4a20e90'); # forma en la que pasamos ala clase el dominio y el token
                        $new_group = array('users' => array(  #array que llena los datos en la url para ser cargado
                            array(
                                'id'=>$id,
                                'username'=>$body['Matricula'],     #llenamos el arrar con los datos del areglo que manda el cliente a modificar
                                'firstname'=>$body['nombre'],       
                                'lastname'=>$body['apellidos'],
                                'email' =>$body['Correo'],
                                'department'=>strtolower($body['carrera']),
                            ),
                        ));
                        $return = $MoodleRest->request('core_user_update_users', $new_group, MoodleRest::METHOD_GET); #añadimos los methodos y inicializamos la peticion
                        $payload = $MoodleRest->getUrl();         #ejecutamos             
                            if ($payload==true) {    #valida si la ejecucion se hizo 
                                $obj = new \stdClass;
                            $obj->Matricula =$body['Matricula'];
                            $obj->Nombre= $body['nombre'];
                            $obj->Editado= "Editado Correctamente";
                            $resultado[] = $obj;
                            return $resultado;       
                            }else {       #caso contrario , saldra este error de parametros
                                $obj = new \stdClass;
                        $obj->Usuario = $body['Matricula'];
                        $obj->Error= "Error de parametros";
                        $resultado[] = $obj;
                        return $resultado;
                            }                                    
                    }else {      #caso contrario , la matricula no existe y muestra ese mensage
                        $obj = new \stdClass;
                        $obj->Usuario = $body['Matricula'];
                        $obj->Error= "La matricula no Existe";
                        $resultado[] = $obj;
                        return $resultado;
                    }
                }
            
            catch (\Exception $e) {   
             echo   $e->getMessage(), "\n";  # valida si hubo error
         }
        }
}
