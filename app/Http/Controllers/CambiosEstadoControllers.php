<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CambiosEstadoControllers extends Controller
{
    /*
***********************************************************
* Enrolar Users a Materia    (METHODO "POST")
***********************************************************
*/
public function EnrolarUsersCurse()
{
    $resultado = array();
    $body = json_decode(file_get_contents('php://input'), true);

    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_course_get_courses_by_field&field=shortname&value=' . $body['asignatura_id']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $data = curl_exec($ch);
        curl_close($ch);
        $idcurse = '';
        $fulname = '';
        $shortname = '';
        $array = json_decode($data, true);
        foreach ($array['courses'] as $curso) {
            $idcurse = $curso['id'];
            $fulname = $curso['fullname'];
            $shortname = $curso['shortname'];
        }
        if ($shortname == $body['asignatura_id']) { //si la asignatura existe 
            ## validamos si existe la matricula 
            $ch = curl_init(); ##abremos unas conexion de consulta
            curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_user_get_users&criteria[0][key]=username&criteria[0][value]=' . $body['matricula']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            $data = curl_exec($ch);
            curl_close($ch);
            $userId = '';
            $firstname = '';
            $username = '';
            $array = json_decode($data, true);
            foreach ($array['users'] as $user) {
                $userId = $user['id'];
                $firstname = $user['firstname'];
                $username = $user['username'];
                $Nombre = $user['fullname'];
            }
            if ($username == $body['matricula']) {
                ##buscamos si en el curso hay enroldos a un grupo
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_enrol_get_enrolled_users&courseid=' . $idcurse);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                $data = curl_exec($ch);
                curl_close($ch);
                $RolUsers = '';
                $name = '';
                $name2 = '';
                $arrayEnrolados = json_decode($data, true);
                ##recorremos el array para ubicar posiciones de datos                 
                foreach ($arrayEnrolados as $user) {
                    $RolUsers = $user['username'];
                    foreach ($user['groups'] as $user2) {
                        $name = $user2['id'];
                    }
                    foreach ($user['enrolledcourses'] as $user3) {
                        $name2 = $user3['shortname'];
                    }
                }
                if ($body['matricula'] != $RolUsers) {
                    if ($body['rol'] == 'e') {
                        $MoodleRest = new MoodleRest('http://35.223.167.202/webservice/rest/server.php', '839856d93785e4cfcade03aed4a20e90');
                        $new_group = array('enrolments' => array(
                            array(
                                'roleid'         => 5, ##rol de estudiante 
                                'userid'       => $userId, ##id de usuario 
                                'courseid'      => $idcurse ##id de curso
                            ),
                        ));
                        $return = $MoodleRest->request('enrol_manual_enrol_users', $new_group, MoodleRest::METHOD_GET);
                        $payload = $MoodleRest->getUrl();
                        if ($payload) {
                            $ch = curl_init();
                            ##buscamos el grupo si existe
                            curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_group_get_course_groups&courseid=' . $idcurse);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_HEADER, 0);
                            $data = curl_exec($ch);
                            curl_close($ch);
                            $idgrup = '';
                            $idnumber = '';
                            $arrayGrupo = json_decode($data, true);
                            foreach ($arrayGrupo as $array) {
                                $idgrup =  $array['id'];
                                $idnumber =  $array['idnumber'];
                                $courseid =  $array['courseid']; //variable para validar que exista el grupo
                            }
                            if ($idcurse == $courseid) {
                                $MoodleRest = new MoodleRest('http://35.223.167.202/webservice/rest/server.php', '839856d93785e4cfcade03aed4a20e90');
                                $new_group = array('members' => array(
                                    array(

                                        'userid'       => $userId, //id del user
                                        'groupid'      => $idgrup // id del grupo
                                    ),
                                ));
                                $return = $MoodleRest->request('core_group_add_group_members', $new_group, MoodleRest::METHOD_GET);
                                $payload = $MoodleRest->getUrl();

                                if ($payload) {
                                    $obj = new \stdClass;
                                    $obj->Id_grupo = $idnumber;
                                    $obj->Materia = $fulname;
                                    $obj->Usuario = $Nombre;
                                    $obj->Enrolado = 'Enrolado';
                                    $resultado[] = $obj;
                                    return  $resultado;
                                }
                            } else {
                                $obj = new \stdClass;
                                $obj->Id_grupo = "No existe";
                                $obj->Error = "404 Not Found";
                                $resultado[] = $obj;
                                return $resultado;
                            }
                        } else {
                            $obj = new \stdClass;
                            $obj->UserRolado = $firstname;
                            $obj->Error = "No se pudo Enrolar";
                            $resultado[] = $obj;
                            return $resultado;
                        }
                    } else { //si no es Estudiante , es profesor
                        $MoodleRest = new MoodleRest('http://35.223.167.202/webservice/rest/server.php', '839856d93785e4cfcade03aed4a20e90');
                        $new_group = array('enrolments' => array(
                            array(
                                'roleid'         => 3,
                                'userid'       => $userId,
                                'courseid'      => $idcurse
                            ),
                        ));
                        $return = $MoodleRest->request('enrol_manual_enrol_users', $new_group, MoodleRest::METHOD_GET);
                        $payload = $MoodleRest->getUrl();
                        if ($payload) {
                            ##buscamos el grupo
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_group_get_course_groups&courseid=' . $idcurse);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_HEADER, 0);
                            $data = curl_exec($ch);
                            curl_close($ch);
                            $idgrup = '';
                            $arrayGrupo = json_decode($data, true);
                            foreach ($arrayGrupo as $array) {
                                $idgrup =  $array['id'];
                                $idnumber =  $array['idnumber'];
                            }
                            if ($idnumber == $body['id_grupo']) {
                                $MoodleRest = new MoodleRest('http://35.223.167.202/webservice/rest/server.php', '839856d93785e4cfcade03aed4a20e90');
                                $new_group = array('members' => array(
                                    array(

                                        'userid'       => $userId, //id del user
                                        'groupid'      => $idgrup // id del grupo
                                    ),
                                ));
                                $return = $MoodleRest->request('core_group_add_group_members', $new_group, MoodleRest::METHOD_GET);
                                $payload = $MoodleRest->getUrl();

                                if ($payload) {
                                    $obj = new \stdClass;
                                    $obj->Id_grupo = $idnumber;
                                    $obj->Materia = $fulname;
                                    $obj->Usuario = $Nombre;
                                    $obj->Enrolado = 'Enrolado';
                                    $resultado[] = $obj;
                                    return  $resultado;
                                }
                            } ##else de enrolar fuera deñ curso
                            else {
                                $obj = new \stdClass;
                                $obj->Id_Grupo = $body['id_grupo'];
                                $obj->Error = "no existe";
                                $resultado[] = $obj;
                                return  $resultado;
                            }
                        }
                    } ##si no esta en el grupo         
                } else {
                    $obj = new \stdClass;
                    $obj->Matricula = $body['matricula'];
                    $obj->Id_grupo = $body['id_grupo'];
                    $obj->Error = "Usuario Existente";
                    $resultado[] = $obj;
                    return $resultado;
                }
            } else {
                $obj = new \stdClass;
                $obj->Matricula = $body['matricula'];
                $obj->Error = "No Existente";
                $resultado[] = $obj;
                return $resultado;
            }
        } else {
            $obj = new \stdClass;
            $obj->Materia = $body['asignatura_id'];
            $obj->Error = "No Existente";
            $resultado[] = $obj;
            return $resultado;
        }
    }  ## try   
    catch (\Exception $e) {
        echo '',  $e->getMessage(), "\n";
    }
}

/*
***********************************************************
* Enrolar Users de Curse    (METHODO "POST")
***********************************************************
*/
public function sincronizarUsersCurse()
{
    $resultado = array();
    try {
        $body = json_decode(file_get_contents('php://input'), true);
        for ($i = 0; $i <= count($body); $i++) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_course_get_courses_by_field&field=shortname&value=' . $body[$i]['asignatura_id']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            $data = curl_exec($ch);
            curl_close($ch);
            $idcurse = '';
            $fulname = '';
            $shortname = '';
            $array = json_decode($data, true);
            foreach ($array['courses'] as $curso) {
                $idcurse = $curso['id'];
                $fulname = $curso['fullname'];
                $shortname = $curso['shortname'];
            }
            if ($shortname == $body[$i]['asignatura_id']) { //si la asignatura existe 
                ## validamos si existe la matricula 
                $ch = curl_init(); ##abremos unas conexion de consulta
                curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_user_get_users&criteria[0][key]=username&criteria[0][value]=' . $body[$i]['matricula']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                $data = curl_exec($ch);
                curl_close($ch);
                $userId = '';
                $firstname = '';
                $username = '';
                $array = json_decode($data, true);
                foreach ($array['users'] as $user) {
                    $userId = $user['id'];
                    $firstname = $user['firstname'];
                    $username = $user['username'];
                    $Nombre = $user['fullname'];
                }
                if ($username == $body[$i]['matricula']) {
                    ##buscamos si en el curso hay enroldos a un grupo
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_enrol_get_enrolled_users&courseid=' . $idcurse);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    $data = curl_exec($ch);
                    curl_close($ch);
                    $RolUsers = '';
                    $name = '';
                    $name2 = '';
                    $arrayEnrolados = json_decode($data, true);
                    ##recorremos el array para ubicar posiciones de datos                 
                    foreach ($arrayEnrolados as $user) {
                        $RolUsers = $user['username'];
                        foreach ($user['groups'] as $user2) {
                            $name = $user2['id'];
                        }
                        foreach ($user['enrolledcourses'] as $user3) {
                            $name2 = $user3['shortname'];
                        }
                    }
                    if ($body[$i]['matricula'] != $RolUsers) {
                        if ($body[$i]['rol'] == 'e') {
                            $MoodleRest = new MoodleRest('http://35.223.167.202/webservice/rest/server.php', '839856d93785e4cfcade03aed4a20e90');
                            $new_group = array('enrolments' => array(
                                array(
                                    'roleid'         => 5, ##rol de estudiante 
                                    'userid'       => $userId, ##id de usuario 
                                    'courseid'      => $idcurse ##id de curso
                                ),
                            ));
                            $return = $MoodleRest->request('enrol_manual_enrol_users', $new_group, MoodleRest::METHOD_GET);
                            $payload = $MoodleRest->getUrl();
                            if ($payload) {
                                $ch = curl_init();
                                ##buscamos el grupo si existe
                                curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_group_get_course_groups&courseid=' . $idcurse);
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($ch, CURLOPT_HEADER, 0);
                                $data = curl_exec($ch);
                                curl_close($ch);
                                $idgrup = '';
                                $idnumber = '';
                                $arrayGrupo = json_decode($data, true);
                                foreach ($arrayGrupo as $array) {
                                    $idgrup =  $array['id'];
                                    $idnumber =  $array['idnumber'];
                                    $courseid =  $array['courseid']; //variable para validar que exista el grupo
                                }
                                if ($idcurse == $courseid) {
                                    $MoodleRest = new MoodleRest('http://35.223.167.202/webservice/rest/server.php', '839856d93785e4cfcade03aed4a20e90');
                                    $new_group = array('members' => array(
                                        array(

                                            'userid'       => $userId, //id del user
                                            'groupid'      => $idgrup // id del grupo
                                        ),
                                    ));
                                    $return = $MoodleRest->request('core_group_add_group_members', $new_group, MoodleRest::METHOD_GET);
                                    $payload = $MoodleRest->getUrl();

                                    if ($payload) {
                                        $obj = new \stdClass;
                                        $obj->Id_grupo = $idnumber;
                                        $obj->Materia = $fulname;
                                        $obj->Usuario = $Nombre;
                                        $obj->Enrolado = 'Enrolado';
                                        $resultado = $obj;
                                        printf(json_encode($resultado));
                                    }
                                } else {
                                    $obj = new \stdClass;
                                    $obj->Id_grupo = "No existe";
                                    $obj->Error = "404 Not Found";
                                    $resultado[] = $obj;
                                    return $resultado;
                                }
                            } else {
                                $obj = new \stdClass;
                                $obj->UserRolado = $firstname;
                                $obj->Error = "No se pudo Enrolar";
                                $resultado[] = $obj;
                                return $resultado;
                            }
                        } else { //si no es Estudiante , es profesor
                            $MoodleRest = new MoodleRest('http://35.223.167.202/webservice/rest/server.php', '839856d93785e4cfcade03aed4a20e90');
                            $new_group = array('enrolments' => array(
                                array(
                                    'roleid'         => 3,
                                    'userid'       => $userId,
                                    'courseid'      => $idcurse
                                ),
                            ));
                            $return = $MoodleRest->request('enrol_manual_enrol_users', $new_group, MoodleRest::METHOD_GET);
                            $payload = $MoodleRest->getUrl();
                            if ($payload) {
                                ##buscamos el grupo
                                $ch = curl_init();
                                curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_group_get_course_groups&courseid=' . $idcurse);
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($ch, CURLOPT_HEADER, 0);
                                $data = curl_exec($ch);
                                curl_close($ch);
                                $idgrup = '';
                                $arrayGrupo = json_decode($data, true);
                                foreach ($arrayGrupo as $array) {
                                    $idgrup =  $array['id'];
                                    $idnumber =  $array['idnumber'];
                                }
                                if ($idnumber == $body[$i]['id_grupo']) {
                                    $MoodleRest = new MoodleRest('http://35.223.167.202/webservice/rest/server.php', '839856d93785e4cfcade03aed4a20e90');
                                    $new_group = array('members' => array(
                                        array(

                                            'userid'       => $userId, //id del user
                                            'groupid'      => $idgrup // id del grupo
                                        ),
                                    ));
                                    $return = $MoodleRest->request('core_group_add_group_members', $new_group, MoodleRest::METHOD_GET);
                                    $payload = $MoodleRest->getUrl();

                                    if ($payload) {
                                        $obj = new \stdClass;
                                        $obj->Id_grupo = $idnumber;
                                        $obj->Materia = $fulname;
                                        $obj->Usuario = $Nombre;
                                        $obj->Enrolado = 'Enrolado';
                                        $resultado = $obj;
                                        printf(json_encode($resultado));
                                    }
                                } ##else de enrolar fuera deñ curso
                                else {
                                    $obj = new \stdClass;
                                    $obj->Id_Grupo = $body[$i]['id_grupo'];
                                    $obj->Error = "no existe";
                                    $resultado[] = $obj;
                                    return  $resultado;
                                }
                            }
                        } ##si no esta en el grupo         
                    } else {
                        $obj = new \stdClass;
                        $obj->Matricula = $body[$i]['matricula'];
                        $obj->Id_grupo = $body[$i]['id_grupo'];
                        $obj->Error = "Usuario Existente";
                        $resultado = $obj;
                        printf(json_encode($resultado));
                    }
                } else {
                    $obj = new \stdClass;
                    $obj->Matricula = $body[$i]['matricula'];
                    $obj->Error = "No Existente";
                    $resultado = $obj;
                    printf(json_encode($resultado));
                }
            } else {
                $obj = new \stdClass;
                $obj->Materia = $body[$i]['asignatura_id'];
                $obj->Error = "No Existente";
                $resultado = $obj;
                printf(json_encode($resultado));
            }
        }
    }  ## try   
    catch (\Exception $e) {
        echo '',  $e->getMessage(), "\n";
    }
}

/*
***********************************************************
* Desenrolar Users de Curse    (METHODO "POST")
***********************************************************
*/
public function DesenrolarUsersCurse()
{

    $resultado = array();
    $body = json_decode(file_get_contents('php://input'), true);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_course_get_courses_by_field&field=shortname&value='.$body['asignatura_id']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    $data = curl_exec($ch);
    curl_close($ch);
    $idcurse = '';
    $fulname = '';
    $shortname = '';
    $array = json_decode($data, true);
    foreach ($array['courses'] as $curso) {
        $idcurse = $curso['id'];
        $fulname = $curso['fullname'];
        $shortname = $curso['shortname'];
    }
    if ($body['asignatura_id'] == $shortname) {
        $ch = curl_init(); ##abremos unas conexion de consulta
        curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_user_get_users&criteria[0][key]=username&criteria[0][value]='.$body['matricula']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $data = curl_exec($ch);
        curl_close($ch);
        $userId = '';
        $firstname = '';
        $username = '';
        $array = json_decode($data, true);
        foreach ($array['users'] as $user) {
            $userId = $user['id'];
            $firstname = $user['firstname'];
            $username = $user['username'];
            $Nombre = $user['fullname'];
        }
        if ($username == $body['matricula']) {
            $ch = curl_init();
            ##buscamos el grupo si existe
            curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_group_get_course_groups&courseid='.$idcurse);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            $data = curl_exec($ch);
            curl_close($ch);
            $idgrup = '';
            $idnumber = '';
            $arrayGrupo = json_decode($data, true);
            foreach ($arrayGrupo as $array) {
                $idgrup =  $array['id'];
                $idnumber =  $array['idnumber'];
                $grupo =  $array['name'];
            }
            if ($idnumber==$body['id_grupo']) {

            $MoodleRest = new MoodleRest('http://35.223.167.202/webservice/rest/server.php', '839856d93785e4cfcade03aed4a20e90');
            $new_group = array('enrolments' => array(
                array(
                    'userid'       => $userId,
                    'courseid'      => $idcurse
                ),
            ));
            $return = $MoodleRest->request('enrol_manual_unenrol_users', $new_group, MoodleRest::METHOD_POST);
            $payload = $MoodleRest->getUrl();

            if ($payload != null) {
                $obj = new \stdClass;
                $obj->grupo = $grupo;
                $obj->Materia = $fulname;
                $obj->Usuario = $Nombre;
                $obj->Desenrolado ='Usuario desenrolado';
                $resultado []= $obj;
                return  $resultado;
            }
        }else {
            $obj = new \stdClass;
            $obj->Grupo= $grupo;
            $obj->Materia = $fulname;
            $obj->Error = "No Existente";
            $resultado[] = $obj;
            return  $resultado;
        }
        } else {
            $obj = new \stdClass;
            $obj->Matricula = $body['matricula'];
            $obj->Error = "No Existente";
            $resultado[] = $obj;
            return  $resultado;
        }
    } else {
        $obj = new \stdClass;
        $obj->Materia = $body['asignatura_id'];
        $obj->Error = "No Existente";
        $resultado[] = $obj;
        return  $resultado;
    }
}


/*
***********************************************************
* Desenrolar muchos usuarios  (METHODO "POST")
***********************************************************
*/


public function DesenrolarMuchosUsersCurse(){

try{
    $resultado = array();
    $body = json_decode(file_get_contents('php://input'), true);
    for ($i=0; $i<=count($body) ; $i++) { 
        ##buscamos el curso , si existe
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_course_get_courses_by_field&field=shortname&value='.$body[$i]['asignatura_id']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $data = curl_exec($ch);
        curl_close($ch);
        $idcurse = '';
        $fulname = '';
        $shortname = '';
        $array = json_decode($data, true);
        foreach ($array['courses'] as $curso) {
            $idcurse = $curso['id'];
            $fulname = $curso['fullname'];
            $shortname = $curso['shortname'];
        }
        if ($body[$i]['asignatura_id'] == $shortname) {
            $ch = curl_init(); ##abremos unas conexion de consulta
            curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_user_get_users&criteria[0][key]=username&criteria[0][value]='.$body[$i]['matricula']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            $data = curl_exec($ch);
            curl_close($ch);
            $userId = '';
            $firstname = '';
            $username = '';
            $array = json_decode($data, true);
            foreach ($array['users'] as $user) {
                $userId = $user['id'];
                $firstname = $user['firstname'];
                $username = $user['username'];
                $Nombre = $user['fullname'];
            }
            if ($username == $body[$i]['matricula']) {
                $ch = curl_init();
                ##buscamos el grupo si existe
                curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_group_get_course_groups&courseid='.$idcurse);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                $data = curl_exec($ch);
                curl_close($ch);
                $idgrup = '';
                $idnumber = '';
                $arrayGrupo = json_decode($data, true);
                foreach ($arrayGrupo as $array) {
                    $idgrup =  $array['id'];
                    $idnumber =  $array['idnumber'];
                    $grupo =  $array['name'];
                }
                if ($idnumber==$body[$i]['id_grupo']) {

                $MoodleRest = new MoodleRest('http://35.223.167.202/webservice/rest/server.php', '839856d93785e4cfcade03aed4a20e90');
                $new_group = array('enrolments' => array(
                    array(
                        'userid'       => $userId,
                        'courseid'      => $idcurse
                    ),
                ));
                $return = $MoodleRest->request('enrol_manual_unenrol_users', $new_group, MoodleRest::METHOD_POST);
                $payload = $MoodleRest->getUrl();
    
                if ($payload != null) {
                    $obj = new \stdClass;
                    $obj->grupo = $grupo;
                    $obj->Materia = $fulname;
                    $obj->Usuario = $Nombre;
                    $obj->Desenrolado ='Usuario desenrolado';
                    $resultado= $obj;
                    printf(json_encode($resultado));
                }
            }else {
                $obj = new \stdClass;
                $obj->Grupo= $grupo;
                $obj->Materia = $fulname;
                $obj->Error = "No Existente";
                $resultado[] = $obj;
                printf(json_encode($resultado));
            }
            } else {
                $obj = new \stdClass;
                $obj->Matricula = $body[$i]['matricula'];
                $obj->Error = "No Existente";
                $resultado[] = $obj;
                printf(json_encode($resultado));
            }
        } else {
            $obj = new \stdClass;
            $obj->Materia = $body[$i]['asignatura_id'];
            $obj->Error = "No Existente";
            $resultado[] = $obj;
            printf(json_encode($resultado));
        }
    }##for


}catch (\Exception $e) {
        echo '',  $e->getMessage(), "\n";
    }

}

/*
***********************************************************
* Suspender Usuarios  (METHODO "POST")
***********************************************************
*/
public function SuspenderUsers()
{
$resultado = array();
$body = json_decode(file_get_contents('php://input'), true);

$ch = curl_init(); //*habremos unas conexion de consulta
//*se busca el usuario 
curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_user_get_users&criteria[0][key]=username&criteria[0][value]='.$body['matricula']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, 0);
$data = curl_exec($ch);
curl_close($ch);
$userId = '';
$firstname = '';
$username = '';
$array = json_decode($data, true);
foreach ($array['users'] as $user) {//* se recorre el areglo en la posicion dada con el alias
    $userId = $user['id'];
    $firstname = $user['firstname'];
    $username = $user['username'];
    $Nombre = $user['fullname'];
}
if ($body['matricula']==$username) {//*verifica si el usuario es igual para aplocar la suspencion
    //*se añaden el dominio y el token ala clase MoodleRest
    $MoodleRest = new MoodleRest('http://35.223.167.202/webservice/rest/server.php', '839856d93785e4cfcade03aed4a20e90');
    $new_group = array('users' => array(
        array(

            'id'         => $userId,//*id del usuario que se consulto para aplicar la suspencion
            'suspended'       =>1,//*suspendemos con 1
        ),
    ));
    //*se pasa las funciones y methodos ala conexion
    $return = $MoodleRest->request('core_user_update_users', $new_group, MoodleRest::METHOD_GET);
    $payload = $MoodleRest->getUrl();
    if ($payload) {
        $obj = new \stdClass;
        $obj->Usuario = $Nombre;
        $obj->Matricula =$username;
        $obj->Suspendido ='Usuario Suspendido Temporalmente';
        $resultado[]= $obj;
        return $resultado;
    }
  
}else{
$obj = new \stdClass;
$obj->Matricula = $body['matricula'];
$obj->Error = "No Existente";
$resultado[] = $obj;
return  $resultado;


}
}

/*
***********************************************************
* Habilitar Usuarios  (METHODO "POST")
***********************************************************
*/
//*los mismos pasos pero ahora cambia el 1 para 0 para desabilitar
public function AbilitarUser()
{
$resultado = array();
$body = json_decode(file_get_contents('php://input'), true);

$ch = curl_init();//*se habre la conexion
curl_setopt($ch, CURLOPT_URL, 'http://35.223.167.202/webservice/rest/server.php?wstoken=839856d93785e4cfcade03aed4a20e90&moodlewsrestformat=json&wsfunction=core_user_get_users&criteria[0][key]=username&criteria[0][value]='.$body['matricula']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, 0);
$data = curl_exec($ch);
curl_close($ch);
$userId = '';
$firstname = '';
$username = '';
$array = json_decode($data, true);
foreach ($array['users'] as $user) {
    $userId = $user['id'];
    $firstname = $user['firstname'];
    $username = $user['username'];
    $Nombre = $user['fullname'];
}
if ($body['matricula']==$username) {

    $MoodleRest = new MoodleRest('http://35.223.167.202/webservice/rest/server.php', '839856d93785e4cfcade03aed4a20e90');
    $new_group = array('users' => array(
        array(

            'id'         => $userId,
            'suspended'       =>0,
        ),
    ));
    $return = $MoodleRest->request('core_user_update_users', $new_group, MoodleRest::METHOD_GET);
    $payload = $MoodleRest->getUrl();
    if ($payload) {
        $obj = new \stdClass;
        $obj->Usuario = $Nombre;
        $obj->Matricula =$username;
        $obj->Activado ='Usuario Activado';
        $resultado[]= $obj;
        return $resultado;
    }
  
}else{
$obj = new \stdClass;
$obj->Matricula = $body['matricula'];
$obj->Error = "No Existente";
$resultado[] = $obj;
return  $resultado;


}
}


public function kkk()
{
    $resultado = array();
    $body = json_decode(file_get_contents('php://input'), true);
    $ciclo = $body[0]['ciclo'];
    $clave = $body[0]['clave'];


    for ($i = 1; $i < count($body); $i++) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://34.133.150.120/webservice/rest/server.php?wstoken=70ac31662a4bc9e66d66bb2526cafa2b&moodlewsrestformat=json&wsfunction=core_user_get_users&criteria[0][key]=username&criteria[0][value]=' . $name = $body[$i]['matriculas_alumnos']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $buscar = curl_exec($ch);

        $firstname = '';
        $lastname = '';
        $valida = '';
        $array = json_decode($buscar, true);
        foreach ($array['users'] as $user) {
            $valida = $user['id'];
            $firstname = $user['firstname'];
            $lastname = $user['lastname'];
        }

        if ($buscar) {

            if ($lastname = null) {
                echo json_encode("El Usuario : " . $body[$i]['matriculas_alumnos'] . "'- Nose encuentra En Moodle'");
                $obj = new \stdClass;
                $obj->User = $body[$i]['matriculas_alumnos'];
                $obj->Error = "Nose encuentra En Moodle";
                $resultado[] = $obj;
                return $resultado;
            } else {
                $MoodleRest = new MoodleRest('http://34.133.150.120/webservice/rest/server.php', '70ac31662a4bc9e66d66bb2526cafa2b');
                $new_group = array('users' => array(
                    array(

                        'id'         => $valida,
                        'suspended'       => 0,
                    ),
                ));

                $return = $MoodleRest->request('core_user_update_users', $new_group, MoodleRest::METHOD_POST);
                $payload = $MoodleRest->getUrl();
                if ($payload) {

                    $obj = new \stdClass;
                    $obj->User = $firstname;
                    $obj->Activado = "Activado En la Plataforma";
                    $resultado[] = $obj;
                    return $resultado;
                } else {


                    $obj = new \stdClass;
                    $obj->User = $firstname;
                    $obj->Activado = "Sigue Suspendido";
                    $resultado[] = $obj;
                    return $resultado;
                }
            }
        }
    }
}
}
