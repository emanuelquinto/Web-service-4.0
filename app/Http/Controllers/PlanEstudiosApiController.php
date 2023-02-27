<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PlanEstudiosApiController extends Controller
{
   /*
***********************************************************
* Crea Un Nivel    (METHODO "POTS")
***********************************************************
*/

public function creaNivelEstudios()
{
    $resultado = array();
    //*SE TIENE LA INFORMACION DEL POSMANT
    $body = json_decode(file_get_contents('php://input'), true);
    $idNivel = $body['id'];
    $NombreNivel = $body['nombre'];
    try {
        //*Se lanza,la consulta de validar si existe la Categoria , con la funcion core_course_get_categories
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_course_get_categories&criteria[0][key]=idnumber&criteria[0][value]=' . $idNivel);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $data = curl_exec($ch);
        curl_close($ch);
        $idMoodle = '';
        $array = json_decode($data, true); //*Se guarda el areglo de la informacion
        foreach ($array as $category) {
            $idMoodle = $category['idnumber'];
        if ($idMoodle == $idNivel) {//*Validad si la categorya es igual a la categoria consultada , para verificar que existe o no
            $obj = new \stdClass;
            $obj->Nombre = $NombreNivel;
            $obj->Existente = "404 Not Found";
            $resultado[] = $obj;
            return $resultado;
        } #termina if
        else { //*Si la categoria padre no existe , se crea 
            //*uso de la clase MoodleRest que contiene las funciones para realizar la conexion , ubicada en el Controllers User
            //*Recibe como parametro, el dominio de la plataforma,parametros de entrada de web service y token de moodle que abre la conexion
            $MoodleRest = new MoodleRest('http://35.223.167.202/webservice/rest/server.php', '839856d93785e4cfcade03aed4a20e90');
            $new_group = array('categories' => array(//*se hace el modelo del array que pide cada api a la que nos conectamos para pasarles los parametros, ejemplo"[category][0][name]'nombre'
                array(
                    'name'         => $NombreNivel, //*parametro del nombre
                    'parent'       => 0,            //*parametro que indica en que nivel de posocion de moodle estara la categoria
                    'idnumber' => $idNivel,         //*parametro que le indicamos que sera nuestra ID para identificarlos de todas las categorias
                    'description' => 'Niveles Intitucionales', //*parametro que indica una descripcion de la categoria
                ),
            ));
            //*agregamos los methodos a la conexion y la funcion de moodle para crear una categoria, la funcion de moodle es:'core_course_create_categories' esta, es agregada en la api de moodle y podemos crear nuesvas categorias solo implementarla y ser uso de ella
            $return = $MoodleRest->request('core_course_create_categories', $new_group, MoodleRest::METHOD_GET);
            $payload = $MoodleRest->getUrl(); //*se realiza la conexion por methodo Get

            if ($payload != null) { //*si la conexion no tuvo exito , arrojara 0 que hace referencia aque no realizo tal peticion y es error de parametros , si no saldra este mensage con un nuemero de repuesta satisfactorio
                $obj = new \stdClass;
                $obj->Nivel = $NombreNivel;
                $obj->Creado = "201: Created.";
                $resultado[] = $obj;
                return $resultado;
            }
        } #termina else       
        }
    
} catch (\Exception $e) { //*caso de errores
    echo '',  $e->getMessage(), "\n";
}
} #terminaCrearNivelEstudios


/*
***********************************************************
*  Edicion de Niveles    (METHODO "POST")
***********************************************************
*/
public function ediciónNivelEstudios($idNivel)
{

    $resultado = array();
    $body = json_decode(file_get_contents('php://input'), true);
    $idNivelCliente = $body['id']; //*se inicializa la variable con el areglo que mandamos desde posmant 
    $NombreNivel = $body['nombre'];
    try {
        $ch = curl_init(); //*Se abre la conexion 
        //*Es proporcionado el dominio,parametros del servicio y token de moodle  asi como la funcion de core_course_get_categories que consulta una categoria y con esto valiamos que exista 
        curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_course_get_categories&criteria[0][key]=idnumber&criteria[0][value]='.$idNivel);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $data = curl_exec($ch);
        curl_close($ch);
        $idMoodle = '';
        $id='';
        $array = json_decode($data, true); //*guardamos y decodificamos en json el areglo de datos de la consulta 
        foreach ($array as $category) {
            $idMoodle = $category['idnumber']; //*uso del idnumber para validar si existe y es igual a el idnivel que le mandamos desde postmman
             $id = $category['id'];
        
        if ($idMoodle == null) { //*aqui validamos si es igual al que le mandamos por parametro en la consulta 
            $obj = new \stdClass; //*si es igual ya no lo crea , entra a esta condicion 
            $obj->Nombre = $NombreNivel;   
            $obj->Error = "404 Not Found";
            $resultado[] = $obj;
            return $resultado;
        } #termina if
        else { //*si no es igual , se crea 
            //*uso de la clase MoodleRest que contiene las funciomes y methodos necesarios para armar la comunicacion,le asignamos como parametros , el dominio , parametros de web services y el token de moodle
            $MoodleRest = new MoodleRest('http://35.223.167.202/webservice/rest/server.php', '839856d93785e4cfcade03aed4a20e90');
            $new_group = array('categories' => array(
                array(
                    'name'         =>$NombreNivel,
                    'id' =>        $id, //*Referencia al Id que le crea mmodle 
                    'idnumber' => $idNivelCliente,  //*idnumbre que hace referencia ala clave de la Categoria

                ),
            ));
            //*se pasa los parametros  de la funcion , el areglo , y el methodo get
            $return = $MoodleRest->request('core_course_update_categories', $new_group, MoodleRest::METHOD_GET);
            $payload = $MoodleRest->getUrl();

            if ($payload != null) { //* si payload es  difente de null , la actaluzacion de hizo correctamente
                $obj = new \stdClass;
                $obj->Nivel = $NombreNivel;
                $obj->Editado = "Satisfactoriamente";
                $resultado[] = $obj;
                return $resultado;
            }
        }
    }#termina else       
    } catch (\Exception $e) {
        echo '',  $e->getMessage(), "\n";
    }
}

/*
***********************************************************
*  Sincroniza Niveles    (METHODO "POST")
***********************************************************
*/
public function cincronizarNiveles()
{
    $resultado = array();
    $body = json_decode(file_get_contents('php://input'), true);
    try {
        for ($i = 0; $i <= count($body); $i++) { //*for para recorrer el areglo por posiciones
            $ch = curl_init();//*Se habre la conexion de la consulta
            //*Conforme al dominio , los parametros de web serivice y el token de acceso a moodle se habre la conexion atraves de la funcion core_course_get_categories que busca las categorias
            curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_course_get_categories&criteria[0][key]=idnumber&criteria[0][value]=' . $body[$i]['id']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            $data = curl_exec($ch);//*Se cierra la conexion
            curl_close($ch);
            $idMoodle = '';
            $array = json_decode($data, true); //*Se guarda el areglo ya consu descodificacion del json
            foreach ($array as $category) { //* se recorre el areglo con el alias
                $idMoodle = $category['idnumber'];
            }
            if ($idMoodle == $body[$i]['id']) { //*Se valida que sea igual al id de moodle y la id que sincronizas desde moodle
                $obj = new \stdClass;
                $obj->Niveles = $body[$i]['nombre'];
                $obj->Existentes = "406 Not Acceptable";
                $resultado = $obj;
                printf(json_encode($resultado));
            } #termina if
            else { //*Si no ,se crea las categorias
                $MoodleRest = new MoodleRest('http://35.223.167.202/webservice/rest/server.php', '839856d93785e4cfcade03aed4a20e90');
                $new_group = array('categories' => array( //*se aregla el areglo conforme ala documentacion de la funcion
                    array(
                        'name'         => $body[$i]['nombre'], //* sele asigna el nombre 
                        'parent'       => 0, //*en la posicion 0 de la rais de categorias padre
                        'idnumber' => $body[$i]['id'],//* hace referencia al id 
                        'description' => 'Niveles Intitucionales', //* descripcion de niveles
                    ),
                ));
                 //*se habre la conexion y se pasan las funciones y methodos ala coenxion
                $return = $MoodleRest->request('core_course_create_categories', $new_group, MoodleRest::METHOD_GET);
                $payload = $MoodleRest->getUrl();//*hace la conexion

                if ($payload) {
                    $obj = new \stdClass;
                    $obj->Niveles = "Sincronizados Correctamente";
                    $obj->Creado = "200: Everything OK";
                    $resultado = $obj;
                    printf(json_encode($resultado));
                }
            } #termina else 
        } #termina for      
    } catch (\Exception $e) {
        echo '',  $e->getMessage(), "\n";
    }
} #termina cincronizarNiveles
/*
***********************************************************
*  CreateCarreraSubNivel  -> Carrera  (METHODO "POST")
***********************************************************
*/
public function createCarreraSubNivel()
{
    $resultado = array();
    $body = json_decode(file_get_contents('php://input'), true);
    try {//*casos de errores 
        //*Se habre ña conexion
        $ch = curl_init();
        //*se busca la categoria atraves de la funcion core_course_get_categories
        curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_course_get_categories&criteria[0][key]=idnumber&criteria[0][value]=' . $body['nivel_id']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $data = curl_exec($ch);
        curl_close($ch);
        $idMoodle = '';
        $nameNivel = '';
        $idnumber = '';
        $array = json_decode($data, true);//*se recorre el areglo y se ubican estas posiciones 
        foreach ($array as $category) { //*se recorre el areglo atraves de un alias y una posicion
            $idMoodle = $category['id'];
            $idnumber = $category['idnumber'];
            $nameNivel = $category['name'];
        }
        if ($idnumber == $body['nivel_id']) { //*se iguala el id del nivel al id que se consulta por parametro
            $MoodleRest = new MoodleRest('http://35.223.167.202/webservice/rest/server.php', '839856d93785e4cfcade03aed4a20e90');
            $new_group = array('categories' => array(
                array(
                    'name'         => $body['nombre'],
                    'parent'       => $idMoodle,
                    'idnumber' => $body['id'],
                    'description' => 'Niveles Intitucionales',
                ),
            ));

            $return = $MoodleRest->request('core_course_create_categories', $new_group, MoodleRest::METHOD_GET);
            $payload = $MoodleRest->getUrl();

            if ($payload !== null) {
                $obj = new \stdClass;
                $obj->Nivel = $nameNivel;
                $obj->Carrera = $body['nombre'];
                $obj->Codigo = $body['nivel_id'];
                $obj->Create = "Create 200 Ok";
                $resultado[] = $obj;
                return $resultado;
            }
        } //*si no es igual , se lanza un error
        else {
            $obj = new \stdClass;
            $obj->Nivel = $nameNivel;
            $obj->Error = "404 Not Found";
            $resultado[] = $obj;
            return $resultado;
        }
    } catch (\Exception $e) {
        echo '',  $e->getMessage(), "\n";
    }
}

/*
***********************************************************
*  Edicion de Carrera    (METHODO "PUT")
***********************************************************
*/
public function ediciónCarreraSubNivel($idUpadte)
{
    $resultado = array();
    $body = json_decode(file_get_contents('php://input'), true);
    $idSubnivelCarrera = $body['id']; //*se recibe el valor de la posicion que recibe en moodle
    $NombreCarrera = $body['nombre'];
    try {
        $ch = curl_init();//* se habre la conexion
        curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_course_get_categories&criteria[0][key]=idnumber&criteria[0][value]=' . $idUpadte);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $data = curl_exec($ch);
        curl_close($ch);
        $idnumber = '';
        $array = json_decode($data, true);//*guarda el array
        foreach ($array as $category) {//* se recorre el areglo con el alias
            $idnumber = $category['idnumber'];
            $idMoodle = $category['id'];
        }


        if ($idnumber !== $idSubnivelCarrera) {
            //*Se les asigna el dominio y el token ala clase MoodleRest
            $MoodleRest = new MoodleRest('http://35.223.167.202/webservice/rest/server.php', '839856d93785e4cfcade03aed4a20e90');
            $new_group = array('categories' => array(
                array(
                    'name'         => $NombreCarrera,
                    'id' =>        $idMoodle, //Referencia al Id que le crea mmodle 
                    'idnumber' => $idSubnivelCarrera,  //idnumbre que hace referencia ala clave de la Categoria

                ),
            ));
            //*se asggna las funciones y methodos ala conexion
            $return = $MoodleRest->request('core_course_update_categories', $new_group, MoodleRest::METHOD_GET);
            $payload = $MoodleRest->getUrl();

            if ($payload) {
                $obj = new \stdClass;
                $obj->Nivel = $NombreCarrera;
                $obj->Editado = "Satisfactoriamente";
                $resultado[] = $obj;
                return $resultado;
            }
        } #termina if
        else {
            $obj = new \stdClass;
            $obj->Nombre = $NombreCarrera;
            $obj->Error = "404 Not Found";
            $resultado[] = $obj;
            return $resultado;
        } #termina else       
    } catch (\Exception $e) {
        echo '',  $e->getMessage(), "\n";
    }
}

/*
***********************************************************
*  SICRONIZACION DE CARRERAS    (METHODO "POS")
***********************************************************
*/
public function cincronizarCarreraSubnivel()
{
    $resultado = array();
    //*se obtiene los datos del cliente
    $body = json_decode(file_get_contents('php://input'), true);
    try {
        for ($i = 0; $i <= count($body); $i++) {
            ##buscamos la clave del nivel principal , para verificar que exista  y obtener el id , idnumber y parent que es donde estara el nivel 
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_course_get_categories&criteria[0][key]=idnumber&criteria[0][value]=' . $body[$i]['nivel_id']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            $data = curl_exec($ch);
            curl_close($ch);
            $nameNivel = '';
            $array = json_decode($data, true);
            foreach ($array as $category) {
                $nameNivel = $category['name'];
                $parent = $category['parent'];
            }
            ##buscamos la clave de la carrera , para verificar que exista , si no se crea
            if ($array) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_course_get_categories&criteria[0][key]=idnumber&criteria[0][value]=' . $body[$i]['id']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                $data = curl_exec($ch);
                curl_close($ch);
                $idnumberCarrrera = '';
                $arrayCarrera = json_decode($data, true);
                foreach ($arrayCarrera as $categoryCarrera) {
                    $idnumberCarrrera = $categoryCarrera['idnumber'];
                }
                if ($idnumberCarrrera  == $body[$i]['id']) {//*se evalua si el id de la carrera es igual a el id de la consulta generada
                    $obj = new \stdClass;
                    $obj->Nivel = $body[$i]['nombre'];
                    $obj->Exixtentes = "404 Not Found";
                    $resultado = $obj;
                    header('Content-Type: application/json; charset=utf-8');
                    printf(json_encode($resultado));
                } #termina if
                else {//*si no es igual se crea
                    //*se les asigna  el dominio y el token
                    $MoodleRest = new MoodleRest('http://35.223.167.202/webservice/rest/server.php', '839856d93785e4cfcade03aed4a20e90');
                    $new_group = array('categories' => array(
                        array(
                            'name'         => $body[$i]['nombre'],
                            'parent'       => $parent,
                            'idnumber'     => $body[$i]['id'],
                            'description'  => 'Niveles Intitucionales',
                        ),
                    ));
                    //*se les asigna las funciones y methodos ala conexion
                    $return = $MoodleRest->request('core_course_create_categories', $new_group, MoodleRest::METHOD_GET);
                    $payload = $MoodleRest->getUrl();

                    if ($payload) {
                        $obj = new \stdClass;
                        $obj->Nivel = $nameNivel;
                        $obj->Carrera = $body[$i]['nombre'];
                        $obj->Codigo = $body[$i]['nivel_id'];
                        $obj->Create = "Create 200 Ok";
                        $resultado = $obj;

                        printf(json_encode($resultado));
                    }
                }
            } else {//si no existe , se lanza una esta Excepcion 
                $obj = new \stdClass;
                $obj->Nivel = $body[$i]['nombre'];
                $obj->Null = "404 Not Found";
                $resultado = $obj;
                printf(json_encode($resultado));
            }
        } #for   

    } catch (\Exception $e) {
        echo '',  $e->getMessage(), "\n";
    }
} # termina Sincronizacion

/*
***********************************************************
*  Edicion de Semestre    (METHODO "POST")
***********************************************************
*/
public function crearPlanEstudiosSemestre()
{
    try {
        //*se recive los datos del cliente
        $body = json_decode(file_get_contents('php://input'), true);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_course_get_categories&criteria[0][key]=idnumber&criteria[0][value]=' . $body['carrera_id']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $data = curl_exec($ch);
        curl_close($ch);
        $nameNivel = '';
        $parent = '';
        $array = json_decode($data, true);//*Se guarda el areglo
        foreach ($array as $category) {
            $nameNivel = $category['name'];
            $parent = $category['id'];
            $idnumberCarrera = $category['idnumber'];
        }
        if ($idnumberCarrera == $body['carrera_id']) {//*Se valida el id de la carrera
            //*se les asigna  el dominio y el token
            $MoodleRest = new MoodleRest('http://35.223.167.202/webservice/rest/server.php', '839856d93785e4cfcade03aed4a20e90');
            $new_group = array('categories' => array(
                array(
                    'name'         => $body['tipo_grados'],
                    'parent'       => $parent,
                    'idnumber'     => $body['id'],
                    'description'  => $body['clave'] . '-' . $body['rvoe'],
                ),
            ));
            //*Sele asigna las funciones y methodos ala conexion
            $return = $MoodleRest->request('core_course_create_categories', $new_group, MoodleRest::METHOD_GET);
            $payload = $MoodleRest->getUrl();//*se lanza la conexion

            if ($payload) { //*si la payload lanza un 1 , se realizo la creacion de la categoria
                $obj = new \stdClass;
                $obj->Nivel = $nameNivel;
                $obj->Semestre = $body['tipo_grados'];
                $obj->Codigo = $body['id'];
                $obj->Create = "Create 200 Ok";
                $resultado[] = $obj;
                return $resultado;
            }
        } #termina if
        else {
            $obj = new \stdClass;
            $obj->Semestre = $body['carrera_id'];
            $obj->Error = "404 Not Found";
            $resultado[] = $obj;
            return $resultado;
        }
    } catch (\Exception $e) {
        echo '',  $e->getMessage(), "\n";
    }
} ## trmina crearPlanEstudiosSemestre

/*
***********************************************************
*  Edicion de Semestre    (METHODO "Put")
***********************************************************
*/

public function editarSemestreSubnivel($idUpadte)
{
    $resultado = array();
    $body = json_decode(file_get_contents('php://input'), true);
    $idSubnivelNivel = $body['id'];
    $NombreSemestre = $body['tipo_grados'];
    try {
        $ch = curl_init();//*se habre la conexion 
        //*se busca la categoria
        curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_course_get_categories&criteria[0][key]=idnumber&criteria[0][value]='.$idUpadte);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $data = curl_exec($ch);
        curl_close($ch);
        $idnumber = '';
        $array = json_decode($data, true);//*se guarda los datos de consulta 
        foreach ($array as $category) {
            $idnumber = $category['idnumber'];
            $idMoodle = $category['id'];
        }


        if ($idnumber !== $idSubnivelNivel) {//*se evalua si es diferente al idnumber , si es diferentes se actualiza 
             //*se pasan el dominio y el token 
            $MoodleRest = new MoodleRest('http://35.223.167.202/webservice/rest/server.php', '839856d93785e4cfcade03aed4a20e90');
            $new_group = array('categories' => array(
                array(
                    'name'         => $NombreSemestre,
                    'id' =>        $idMoodle, //Referencia al Id que le crea mmodle 
                    'idnumber' => $idSubnivelNivel,  //idnumbre que hace referencia ala clave de la Categoria
                    'description'  => $body['clave'] . '-' . $body['rvoe'],
                ),
            ));

            $return = $MoodleRest->request('core_course_update_categories', $new_group, MoodleRest::METHOD_GET);
            $payload = $MoodleRest->getUrl();

            if ($payload) {
                $obj = new \stdClass;
                $obj->Nivel = $NombreSemestre;
                $obj->Editado = "Satisfactoriamente";
                $resultado[] = $obj;
                return $resultado;
            }
        } #termina if
        else {
            $obj = new \stdClass;
            $obj->Nombre = $NombreSemestre;
            $obj->Error = "404 Not Found";
            $resultado[] = $obj;
            return $resultado;
        } #termina else       
    } catch (\Exception $e) {
        echo '',  $e->getMessage(), "\n";
    }
}

/*
***********************************************************
*  Sincronizar Semestres    (METHODO "Post")
***********************************************************
*/
public function cincronizarSemestres()
{
    $body = json_decode(file_get_contents('php://input'), true);
    try {
        for ($i = 0; $i <= count($body); $i++) {
            ##buscamos la clave del nivel principal , para verificar que exista  y obtener el id , idnumber y parent que es donde estara el nivel 
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_course_get_categories&criteria[0][key]=idnumber&criteria[0][value]=' . $body[$i]['carrera_id']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            $data = curl_exec($ch);
            curl_close($ch);
            $nameNivel = '';
            $array = json_decode($data, true);
            foreach ($array as $category) {
                $nameNivel = $category['name'];
                $parent = $category['id'];
            }
            ##buscamos la clave de la carrera , para verificar que exista , si no se crea   
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_course_get_categories&criteria[0][key]=idnumber&criteria[0][value]=' . $body[$i]['id']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            $data = curl_exec($ch);
            curl_close($ch);
            $idnumberCarrrera = '';
            $arrayCarrera = json_decode($data, true);
            foreach ($arrayCarrera as $categoryCarrera) {
                $idnumberCarrrera = $categoryCarrera['idnumber'];
            }
            if ($idnumberCarrrera == $body[$i]['carrera_id']) {

                $MoodleRest = new MoodleRest('http://35.223.167.202/webservice/rest/server.php', '839856d93785e4cfcade03aed4a20e90');
                $new_group = array('categories' => array(
                    array(
                        'name'         => $body[$i]['tipo_grados'],
                        'parent'       => $parent,
                        'idnumber'     => $body[$i]['id'],
                        'description'  => $body[$i]['clave'] . '-' . $body[$i]['rvoe'],
                    ),
                ));

                $return = $MoodleRest->request('core_course_create_categories', $new_group, MoodleRest::METHOD_GET);
                $payload = $MoodleRest->getUrl();

                if ($payload) {
                    $obj = new \stdClass;
                    $obj->Nivel = $nameNivel;
                    $obj->Carrera = $body[$i]['tipo_grados'];
                    $obj->Codigo = $body[$i]['id'];
                    $obj->Create = "Create 200 Ok";
                    $resultado = $obj;

                    printf(json_encode($resultado));
                }
            } #termina if
            else {
                $obj = new \stdClass;
                $obj->Nivel = $body[$i]['tipo_grados'];
                $obj->Exixtentes = "404 Not Found";
                $resultado = $obj;
                header('Content-Type: application/json; charset=utf-8');
                printf(json_encode($resultado));
            }
        } #for   
    } catch (\Exception $e) {
        echo ' ',  $e->getMessage(), "\n";
    }
}
/*
***********************************************************
*  Crear Materias    (METHODO "POST")
***********************************************************
*/
public function crearMateria()
{
    try {
        $body = json_decode(file_get_contents('php://input'), true);//*se obtiene los datos del cliente
        $ch = curl_init();//*se abre la conexion 
        //*se busca la categoria 
        curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_course_get_categories&criteria[0][key]=idnumber&criteria[0][value]=' . $body['plan_estudios_id']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $data = curl_exec($ch);//*se cierra la conexion y se guarda los datos del areglo
        curl_close($ch);
        $nameNivel = '';
        $idSemestre = '';
        $parent = '';
        $array = json_decode($data, true);//*se guarda los datos del areglo 
        foreach ($array as $curso) {//*se recorre el areglo con el alias
            $nameNivel = $curso['name'];
            $parent = $curso['id']; //*posicion de la subcategora superior 
            $idSemestre = $curso['idnumber'];
        }
        if ($body['plan_estudios_id'] == $idSemestre) { //*evalua si es igual 
            $ch = curl_init();//*se inicia la busqueda
            //*se busca el curso
            curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_course_get_courses_by_field&field=shortname&value=' . $body['id']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            $data = curl_exec($ch);
            curl_close($ch);
            $idMateria = '';
            $array = json_decode($data, true);
            foreach ($array['courses'] as $curso) {
                $idMateria = $curso['shortname']; //*clave del curso para identificar 
            }
            if ($idMateria == $body['id']) { //*valida que si existe 
                $obj = new \stdClass;
                $obj->Id_Materia = $body['id'];
                $obj->Existente = "404 Not Found";
                $resultado[] = $obj;
                return $resultado;
            } else {//*si no existe  , se crea el curso 
                $MoodleRest = new MoodleRest('http://35.223.167.202/webservice/rest/server.php', '839856d93785e4cfcade03aed4a20e90');
                $new_group = array('courses' => array(
                    array(
                        'fullname'         => $body['nombre'],
                        'shortname'       => $body['id'],//*id del curso , con este se identifica el curso
                        'categoryid'      => $parent,
                        'idnumber'      => $body['codigo']


                    ),
                ));
                $return = $MoodleRest->request('core_course_create_courses', $new_group, MoodleRest::METHOD_GET);
                $payload = $MoodleRest->getUrl();
                if ($payload) {
                    $obj = new \stdClass;
                    $obj->Nivel = $nameNivel;
                    $obj->Id_Nivel = $body['id'];
                    $obj->Materia = $body['nombre'];
                    $obj->Create = "Create 200 Ok";
                    $resultado[] = $obj;
                    return $resultado;
                }
            }
        } else {

            $obj = new \stdClass;
            $obj->Id_Semestre = $body['id'];
            $obj->Error = "404 Not Found";
            $resultado[] = $obj;
            return $resultado;
        }
    } catch (\Exception $e) {
        echo '',  $e->getMessage(), "\n";
    }
}

/*
***********************************************************
*  Crear varias materias   (METHODO "POST")
***********************************************************
*/ 
public function cincronizarMateria()
{
    try {
        $body = json_decode(file_get_contents('php://input'), true);//*se recibe los datos de las materias 
        for ($i = 0; $i <= count($body); $i++) { //* este for recorrera las numeros de materias en posiciones del areglo,conteo
            $ch = curl_init(); //*se habre la conexion
            //*busca el curso
            curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_course_get_categories&criteria[0][key]=idnumber&criteria[0][value]='.$body[$i]['plan_estudios_id']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            $data = curl_exec($ch);
            curl_close($ch);
            $nameNivel = '';
            $idSemestre = '';
            $parent = '';
            $array = json_decode($data, true);//*se guarda el areglo consultado
            foreach ($array as $curso) {//*recorre el areglo con un alias 
                $nameNivel = $curso['name'];
                $parent = $curso['id'];
                $idSemestre = $curso['idnumber'];
            }

            if ($body[$i]['plan_estudios_id'] == $idSemestre) {//*valida si el id de la categoria sea igual al id dela vategoria mandada del cliente
                $ch = curl_init();//*se habre la conexion
                //*se busca el curso
                curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_course_get_courses_by_field&field=shortname&value=' . $body[$i]['id']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                $data = curl_exec($ch); //*se cierra la conexion y se tiene los datos
                curl_close($ch);
                $idMateria = '';
                $array = json_decode($data, true);
                foreach ($array['courses'] as $curso) { //*se recorre el areglo en esta posicion "courses" con un alias 
                    $idMateria = $curso['shortname'];//*solo se identifica la clave
                }
                if ($idMateria == $body[$i]['id']) {//*se evalua si el id de la materia es igual ala materia consultada de moodle
                    $obj = new \stdClass;
                    $obj->Id_Materia = $body[$i]['id'];
                    $obj->Existente = "404 Not Found";
                    $resultado = $obj;
                    printf(json_encode($resultado));
                } else {//*si no existe , se crea el curso
                    //* se asigna los parametros ala clase MoodleRest, dominio y token
                    $MoodleRest = new MoodleRest('http://35.223.167.202/webservice/rest/server.php', '839856d93785e4cfcade03aed4a20e90');
                    $new_group = array('courses' => array( //*se crea el array conforme ala funcion se solicita
                        array(
                            'fullname'         => $body[$i]['nombre'],//*nombre complelto
                            'shortname'       => $body[$i]['id'],//*clave que se le asigna ala materia
                            'categoryid'      => $parent, //*nivel de donde estara la categoria
                            'idnumber'      => $body[$i]['codigo']//*segundo clave de la mataria para identificar


                        ),
                    ));
                    //*se asigna las funciones y methodos ala conexion
                    $return = $MoodleRest->request('core_course_create_courses', $new_group, MoodleRest::METHOD_GET);
                    $payload = $MoodleRest->getUrl();//*se lanza la conexion
                    if ($payload) {//*se realizo la conexion 
                        $obj = new \stdClass;
                        $obj->Nivel = $nameNivel;
                        $obj->Id_Nivel = $body[$i]['id'];
                        $obj->Materia = $body[$i]['nombre'];
                        $obj->Create = "Create 200 Ok";
                        $resultado = $obj;
                        printf(json_encode($resultado));
                    }
                }
            } else {
                 //*id mo reconocible
                $obj = new \stdClass;
                $obj->Id_Semestre = $body[$i]['id'];
                $obj->Error = "404 Not Found";
                $resultado = $obj;
                printf(json_encode($resultado));
            }
        } ##for
    } catch (\Exception $e) {
        echo '',  $e->getMessage(), "\n";
    }
}


/*
***********************************************************
*  Actualizar Materia    (METHODO "PUT")
***********************************************************
*/
public function editarMateria($idUpadte)
{
    $resultado = array();
    $body = json_decode(file_get_contents('php://input'), true);
    try {
        //*se inicia la consulta por el parametro de la clave del curso
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_course_get_courses_by_field&field=shortname&value=' . $idUpadte);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $data = curl_exec($ch);//* se cierra la conexion y se guarda los datos de la consulta
        curl_close($ch);
        $idnumber = '';
        $array = json_decode($data, true);
        foreach ($array['courses'] as $category) {//*se recorre  el areglo en la posicion "courses" con el alias
            $idnumber = $category['shortname'];
            $idMoodle = $category['id'];
        }

        if ($idnumber == $idUpadte) {//*se evalua , si el id del curso es igual al parametro recivido
           //*se le asigna el  dominio y el token al clase MoodleRest
            $MoodleRest = new MoodleRest('http://35.223.167.202/webservice/rest/server.php', '839856d93785e4cfcade03aed4a20e90');
            $new_group = array('courses' => array(
                array(
                    'fullname'    => $body['nombre'],
                    'id'           => $idnumber, //Referencia al Id que le crea mmodle 
                    'idnumber'     => $body['codigo'],  //idnumbre que hace referencia ala clave de la Categoria
                    'shortname'    => $body['id'],
                ),
            ));
            //*se  añaden las funciones y areglos y methodos ala conexion
            $return = $MoodleRest->request('core_course_update_courses', $new_group, MoodleRest::METHOD_GET);
            $payload = $MoodleRest->getUrl();//*se lanza la conexion

            if ($payload) {
                $obj = new \stdClass;
                $obj->Nombre = $body['nombre'];
                $obj->Id = $body['id'];
                $obj->Grado = $body['grado'];
                $obj->Editado = "Satisfactoriamente";
                $resultado[] = $obj;
                return $resultado;
            }
        } #termina if
        else {
            $obj = new \stdClass;
            $obj->Nombre = $body['nombre'];
            $obj->Error = "404 Not Found";
            $resultado[] = $obj;
            return $resultado;
        } #termina else       
    } catch (\Exception $e) {
        echo '',  $e->getMessage(), "\n";
    }
}

/*
***********************************************************
*  Crea grupos    (METHODO "POTS")
***********************************************************
*/

public function crearGrupo()
{
    try {
        //*obtiene los datos de detalle de los grupos 
        $body = json_decode(file_get_contents('php://input'), true);
        $ch = curl_init();//*se inicia la coenxion 
        //*busca si el curso , existe
        curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_course_get_courses_by_field&field=shortname&value=' . $body['idcurse']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $data = curl_exec($ch);//*se cierra la consulta
        curl_close($ch);
        $idCurso = '';
        $array = json_decode($data, true);//*se guarda los datos de la conexion
        foreach ($array['courses'] as $category) {//*se recorre el areglo en la posicion dada con el alias
            $idCurso = $category['id'];
            $clavecurso = $category['shortname'];
        }

        if ($clavecurso == $body['idcurse']) {//*se valida si la clave deda es igual ala idcurse de moodle

            $MoodleRest = new MoodleRest('http://35.223.167.202/webservice/rest/server.php', '839856d93785e4cfcade03aed4a20e90');
            $new_group = array('groups' => array(
                array(
                    'courseid'    => $idCurso,//* id del curso al cual se creara el grupo
                    'name'    => $body['clave'], //*nombre del grupo
                    'enrolmentkey'     => $body['clave'],  //*idnumbre que hace referencia ala clave de la Categoria
                    'idnumber'    => $body['id'],           //*id que sera la forma de identificar
                    'description' => $body['descripcion']
                ),
            ));
            //*Se asigna el areglo armado , funciones y methodos ala comexionn
            $return = $MoodleRest->request('core_group_create_groups', $new_group, MoodleRest::METHOD_GET);
            $payload = $MoodleRest->getUrl();

            if ($payload) {
                $obj = new \stdClass;
                $obj->clase = $body['clave'];
                $obj->Id = $body['id'];
                $obj->Grado = $body['no_grado'];
                $obj->Editado = "Create 200 Ok";
                $resultado[] = $obj;
                return $resultado;
            }
        } else {
            $obj = new \stdClass;
            $obj->Id_curso = $body['idcurse'];
            $obj->Error = "No existe";
            $resultado[] = $obj;
            return $resultado;
        }
    } catch (\Exception $e) {
        echo ' ',  $e->getMessage(), "\n";
    }
}

/*
***********************************************************
*  Editar grupos    (METHODO "PUT")
***********************************************************
*/
public function editarGrupo($idUpadte)
{
    $body = json_decode(file_get_contents('php://input'), true);
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_course_get_courses_by_field&field=shortname&value=' . $idUpadte);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $data = curl_exec($ch);
        curl_close($ch);
        $idCurso = '';
        $array = json_decode($data, true);
        foreach ($array['courses'] as $category) {
            $idCurso = $category['id'];
            $clavecurso = $category['shortname'];
        }

        if ($idUpadte == $clavecurso) {

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_group_get_course_groups&courseid=' . $idCurso);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            $data = curl_exec($ch);
            curl_close($ch);
            $idgrupo = '';
            $array = json_decode($data, true);
            for ($i = 0; $i <= count($array); $i++) {

                $idnumber =  $array[$i]['idnumber'];
                $idgrupo =  $array[$i]['id'];

                if ($idnumber == $body['id']) {

                    $MoodleRest = new MoodleRest('http://35.223.167.202/webservice/rest/server.php', '839856d93785e4cfcade03aed4a20e90');
                    $new_group = array('groups' => array(
                        array(
                            'id'    => $idgrupo,
                            'name'    => $body['clave'],
                            'enrolmentkey'     => $body['clave'],  //idnumbre que hace referencia ala clave de la Categoria
                            'idnumber'    => $body['id'],
                            'description' => $body['descripcion']
                        ),
                    ));

                    $return = $MoodleRest->request('core_group_update_groups', $new_group, MoodleRest::METHOD_GET);
                    $payload = $MoodleRest->getUrl();

                    if ($payload) {
                        $obj = new \stdClass;
                        $obj->clase = $body['clave'];
                        $obj->Id = $body['id'];
                        $obj->Grado = $body['no_grado'];
                        $obj->Editado = "207 Multi-Status";
                        $resultado[] = $obj;
                        return $resultado;
                    }

                    break;
                }
            }


        } else {
            $obj = new \stdClass;
            $obj->Materia = $body['idcurse'];
            $obj->Error = "404 Not Found";
            $resultado = $obj;
            printf(json_encode($resultado));
        }
    } catch (\Exception $e) {
        echo ' ',  $e->getMessage(), "\n";
    }
}
/*
***********************************************************
*  Sincronizar Grupos   (METHODO "POST")
***********************************************************
*/

public function sincronizarGrupos()
{
    $body = json_decode(file_get_contents('php://input'), true);
    for ($i=0; $i <=count($body); $i++) { 
        try {
            $body = json_decode(file_get_contents('php://input'), true);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_course_get_courses_by_field&field=shortname&value='.$body[$i]['idcurse']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            $data = curl_exec($ch);
            curl_close($ch);
            $idCurso = '';
            $clavecurso='';
            $array = json_decode($data, true);
            foreach ($array['courses'] as $category) {
                $idCurso = $category['id'];
                $clavecurso = $category['shortname'];
            }

            if ($clavecurso == $body[$i]['idcurse']) {
           
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_group_get_course_groups&courseid='.$idCurso);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            $data = curl_exec($ch);
            curl_close($ch);
            $idgrup = '';
            $arrayGrupo = json_decode($data, true);
            foreach ($arrayGrupo as $array) {
                $idgrup =  $array['idnumber'];
                //$idgrupo =  $array[$j]['id'];
            }
        
             if ($idgrup ==$body[$i]['id']) {
                $obj = new \stdClass;
                $obj->Id_grupo =$body[$i]['id'];
                $obj->Error ="Existente";
                $resultado = $obj;
                printf(json_encode($resultado));

            }else {
            
                $MoodleRest = new MoodleRest('http://35.223.167.202/webservice/rest/server.php', '839856d93785e4cfcade03aed4a20e90');
                $new_group = array('groups' => array(
                    array(
                        'courseid'    => $idCurso,
                        'name'    => $body[$i]['clave'],
                        'enrolmentkey'     => $body[$i]['clave'],  //idnumbre que hace referencia ala clave de la Categoria
                        'idnumber'    => $body[$i]['id'],
                        'description' => $body[$i]['descripcion']
                    ),
                ));

                $return = $MoodleRest->request('core_group_create_groups', $new_group, MoodleRest::METHOD_GET);
                $payload = $MoodleRest->getUrl();

                if ($payload) {
                    $obj = new \stdClass;
                    $obj->clase = $body[$i]['clave'];
                    $obj->Id = $body[$i]['id'];
                    $obj->Grado = $body[$i]['no_grado'];
                    $obj->Creado = "200 Ok";
                    $resultado = $obj;
                    printf(json_encode($resultado));
                }
            }
            #for
            } else {
                $obj = new \stdClass;
                $obj->Id_curso = $body[$i]['idcurse'];
                $obj->Error = "No existe";
                $resultado = $obj;
                printf(json_encode($resultado));
            }
        } catch (\Exception $e) {
            echo ' ',  $e->getMessage(), "\n";
        }
    }
}

/*    
***********************************************************
* Calificacion de la Materia    (METHODO "GET")
***********************************************************
*/
//*pendiente por agregar cambios 
public function CalificacionMateria($calificacionMateria)
{
    $resultado = array();
    $endpoint = "http://campusvirtual.cesba-queretaro.edu.mx/webservice/rest/server.php?wstoken=817c06ac6196681f2d8f7db8dc6401ab&moodlewsrestformat=json&wsfunction=core_course_get_courses_by_field&field=shortname&value=".$calificacionMateria;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    if (preg_match('~Location: (.*)~i', $result, $match)) {
        $location = trim($match[1]);
        $urlCompuesta = $location;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $urlCompuesta);
        $rest = curl_exec($ch);
        curl_close($ch);
    }
    $idcurse = '';
    $fulname = '';
    $shortname = '';
    $arraycurse = json_decode($rest, true);
    foreach ($arraycurse['courses'] as $curso) {
        $idcurse = $curso['id'];
        $fulname = $curso['fullname'];
        $shortname = $curso['shortname'];
    }
    if ($rest != null) {
        $endpoint = "http://campusvirtual.cesba-queretaro.edu.mx/webservice/rest/server.php?wstoken=817c06ac6196681f2d8f7db8dc6401ab&moodlewsrestformat=json&wsfunction=gradereport_user_get_grade_items&courseid=" . $idcurse;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        if (preg_match('~Location: (.*)~i', $result, $match)) {
            $location = trim($match[1]);
            $urlCompuesta = $location;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $urlCompuesta);
            $rest = curl_exec($ch);
            curl_close($ch);
            $array = json_decode($rest, true);
        }
        foreach ($array['usergrades'] as $curso) {
            $id = $curso['userid'];
            $shortname = $curso['userfullname'];

            $endpoint = "http://campusvirtual.cesba-queretaro.edu.mx/webservice/rest/server.php?wstoken=817c06ac6196681f2d8f7db8dc6401ab&moodlewsrestformat=json&wsfunction=core_user_get_users&criteria[0][key]=id&criteria[0][value]=" . $id;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            if (preg_match('~Location: (.*)~i', $result, $match)) {
                $location = trim($match[1]);
                header('Content-Type: application/json');
                $endpoint = $location;
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_URL, $endpoint);
                $result = curl_exec($ch);
                curl_close($ch);
            }
            $array = json_decode($result, true);
            $username = '';
            foreach ($array['users']  as $mPersona) {
                $username = $mPersona['username'];
            }
            foreach ($curso['gradeitems'] as $v2) {
                $micalificacion = $v2['percentageformatted'];
                $gradeformatted = $v2['gradeformatted'];
            }

            $obj = new \stdClass;
            $obj->matricula = $username;
            $obj->nombre = $shortname;
            $obj->Materia = $fulname;
            $obj->calificacion_final = $micalificacion;
            $resultado[] = $obj;
            echo json_encode($obj);
        }
    } else {
        $obj = new \stdClass;
        $obj->Materia = $calificacionMateria;
        $resultado[] = $obj;
        return $resultado;
    }
}
}
