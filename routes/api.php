<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

//*******************  Rutas de Usuarios **********************************
Route::get('consultaUsers/{matricula}','UsersApiController@getAlumnos'); {   
}

Route::post('CreaUsuarios','UsersApiController@CrearAlumnos');{   
}

Route::put('updateUsers/{matricula}','UsersApiController@UpdateUsers'); {   
}
//************** */ Rutas De Plan de Estudios ***************************
Route::post('CreaNivelEstudios','PlanEstudiosApiController@creaNivelEstudios');{   
}

Route::put('EdiciónNivelEstudios/{idNivel}','PlanEstudiosApiController@ediciónNivelEstudios');{   
}

Route::post('CincronizarNiveles','PlanEstudiosApiController@cincronizarNiveles');{   
}

Route::post('CreateCarreraSubNivel','PlanEstudiosApiController@createCarreraSubNivel');{   
}

Route::put('EdiciónCarreraSubNivel/{idUpadte}','PlanEstudiosApiController@ediciónCarreraSubNivel');{   
}

Route::post('CincronizarCarreraSubnivel','PlanEstudiosApiController@cincronizarCarreraSubnivel');{   
}

Route::post('CrearPlanEstudiosSemestre','PlanEstudiosApiController@crearPlanEstudiosSemestre');{   
}

Route::put('EditarSemestreSubnivel/{idUpadte}','PlanEstudiosApiController@editarSemestreSubnivel');{   
}

Route::post('CincronizarSemestres','PlanEstudiosApiController@cincronizarSemestres');{   
}

Route::post('CrearMateria','PlanEstudiosApiController@crearMateria');{   
}

Route::post('CincronizarMateria','PlanEstudiosApiController@cincronizarMateria');{   
}

Route::post('CrearGrupo','PlanEstudiosApiController@crearGrupo');{   
}

Route::put('EditarGrupo/{idUpadte}','PlanEstudiosApiController@editarGrupo');{   
}

Route::post('SincronizarGrupos','PlanEstudiosApiController@sincronizarGrupos');{   
}

Route::put('EditarMateria/{idUpadte}','PlanEstudiosApiController@editarMateria');{   
}

Route::get('consultaMateria/{ConsultaMaterias}','PlanEstudiosApiController@ConsultaMateria');{   
}
//pendiente
Route::get('calificacionMateria/{calificacionMateria}','PlanEstudiosApiController@CalificacionMateria');{   
}

//* Rutas De Proceso de Cambios del Users una ves Creado (enrolar,desenrolar,Suspender, Activar)

Route::post('EnrolarUsersCurse','CambiosEstadoControllers@EnrolarUsersCurse');{   
}

Route::post('SincronizarUsersCurse','CambiosEstadoControllers@sincronizarUsersCurse');{   
}

Route::post('DesenrolarUsersCurse','CambiosEstadoControllers@DesenrolarUsersCurse');{   
}

Route::post('DesenrolarMuchosUsersCurse','CambiosEstadoControllers@DesenrolarMuchosUsersCurse');{   
}

Route::post('SuspenderUsers','CambiosEstadoControllers@SuspenderUsers');{   
}

Route::post('AbilitarUser','CambiosEstadoControllers@AbilitarUser');{   
}
