<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/login', 'App\Http\Controllers\pages\InicioController@login')->name('inicio-sesion');
Route::post('/login', 'App\Http\Controllers\pages\InicioController@autenticar')->name('procesar-login');

# Ruta de logout
Route::post('/logout', function () {
  Auth::logout(); # Cierra la sesión del usuario
  return redirect('/login'); # Redirige a la página de login
})->name('logout');

# Proteccion de rutas si no esta autenticado
Route::middleware(['auth'])->group(function () {

  Route::get('/', 'App\Http\Controllers\pages\InicioController@index')->name('pantalla-inicio');

  Route::prefix('usuario')->group(function () {
    Route::get('/firma', 'App\Http\Controllers\pages\usuario\UsuarioController@firma')->name('usuario-firma');

    # ****************************************************************************************
    # ****************************************************************************************

    Route::prefix('api')->group(function () {
      Route::post('/guardar-firma', 'App\Http\Controllers\pages\usuario\UsuarioController@guardarFirma')->name('guardar-firma-medico');
    });
  });

  Route::prefix('pacientes')->group(function () {
    Route::get('/nuevo', 'App\Http\Controllers\pages\pacientes\PacientesController@nuevoPaciente')->name('nuevo-paciente');
    Route::get('/registrar-valoracion', 'App\Http\Controllers\pages\pacientes\PacientesController@registrarValoracionPaciente')->name('registrar-valoracion-paciente');
    Route::get('/consultar', 'App\Http\Controllers\pages\pacientes\PacientesSeguimientoController@listadoPacientes')->name('listado-paciente');
    Route::get('/expediente', 'App\Http\Controllers\pages\pacientes\PacientesSeguimientoController@expedientePacientes')->name('listado-expediente-paciente');
    Route::get('/detalle-consulta', 'App\Http\Controllers\pages\pacientes\PacientesSeguimientoController@detalleConsultaPaciente')->name('detalle-consulta-paciente');
    Route::get('/buscar-consulta', 'App\Http\Controllers\pages\pacientes\PacientesSeguimientoController@todasLasConsultas')->name('listado-consultas');

    # ****************************************************************************************
    # ****************************************************************************************

    Route::prefix('api')->group(function () {
      Route::post('/consultar', 'App\Http\Controllers\pages\pacientes\PacientesController@consultarPacienteFortia')->name('consultar-paciente');
      Route::post('/registrar', 'App\Http\Controllers\pages\pacientes\PacientesController@registrarPaciente')->name('registrar-paciente');
      Route::post('/guardar-valoracion', 'App\Http\Controllers\pages\pacientes\PacientesController@guardarValoracionPaciente')->name('guardar-valoracion-paciente');
      Route::post('/obtener-lista-pacientes', 'App\Http\Controllers\pages\pacientes\PacientesSeguimientoController@obtenerListadoPacientes')->name('obtener-lista-pacientes');
      Route::post('/obtener-lista-consultas-paciente', 'App\Http\Controllers\pages\pacientes\PacientesSeguimientoController@obtenerListadoConsultasPaciente')->name('obtener-lista-consultas-paciente');
      Route::post('/registrar-nota', 'App\Http\Controllers\pages\pacientes\PacientesController@registrarNota')->name('registrar-nota');
      Route::post('/obtener-lista-recetas-paciente', 'App\Http\Controllers\pages\receta\RecetaController@obtenerListadoRecetasPaciente')->name('obtener-lista-recetas-paciente');
      Route::post('/buscar-cie', 'App\Http\Controllers\pages\pacientes\PacientesController@buscarCie')->name('buscar-cie');
      Route::post('/obtener-lista-todas-consultas', 'App\Http\Controllers\pages\pacientes\PacientesSeguimientoController@obtenerListadoTodasConsultas')->name('obtener-lista-pacientes');
    });
  });

  Route::prefix('receta')->group(function () {
    Route::get('/nueva', 'App\Http\Controllers\pages\receta\RecetaController@nuevaReceta')->name('receta-nueva');
    Route::get('/listado', 'App\Http\Controllers\pages\receta\RecetaController@recetasPaciente')->name('listado-recetas');
    Route::get('/surtir', 'App\Http\Controllers\pages\receta\RecetaController@recetaSurtir')->name('receta-surtir');

    # ****************************************************************************************
    # ****************************************************************************************

    Route::prefix('api')->group(function () {
      Route::post('/registrar-receta', 'App\Http\Controllers\pages\receta\RecetaController@registrarReceta')->name('registrar-receta');
      Route::post('/obtener-catalogo-medicamentos-hispatec', 'App\Http\Controllers\pages\receta\RecetaController@obtenerMedicamentosHispatec')->name('obtener-catalogo-medicamentos-hispatec');
      Route::get('/obtener-detalle-receta', 'App\Http\Controllers\pages\receta\RecetaController@obtenerDetalleReceta')->name('obtener-detalle-receta');
      Route::get('/surtir-receta-completa', 'App\Http\Controllers\pages\receta\RecetaController@surtirRecetaCompleta')->name('surtir-receta-completa');
      Route::post('/guardar-firma-paciente', 'App\Http\Controllers\pages\receta\RecetaController@guardarFirma')->name('guardar-firma-paciente');
      Route::post('/buscar-empleado-fortia', 'App\Http\Controllers\pages\receta\RecetaController@buscarEmpleadoFortia')->name('buscar-empleado-fortia');
    });
  });

  Route::prefix('historia_clinica')->group(function () {
    Route::get('/crear', 'App\Http\Controllers\pages\historicoClinico\HistoricoClinicoController@crearHistorico')->name('historia-clinica-crear');
    Route::get('/listado', 'App\Http\Controllers\pages\historicoClinico\HistoricoClinicoSeguimientoController@listadoHistoricos')->name('historia-clinica-listado');
    Route::get('/consultar', 'App\Http\Controllers\pages\historicoClinico\HistoricoClinicoSeguimientoController@consultarHistoricoClinico')->name('historico-clinico-consultar');

    # ****************************************************************************************
    # ****************************************************************************************

    Route::prefix('api')->group(function () {
      Route::post('/registrar-historico', 'App\Http\Controllers\pages\historicoClinico\HistoricoClinicoController@registrarHistoricoClinico')->name('registrar-historico');
      Route::post('/obtener-lista-historicos-clinicos', 'App\Http\Controllers\pages\historicoClinico\HistoricoClinicoSeguimientoController@obtenerListadoHistoricosClinicos')->name('obtener-lista-historicos-clinicos');
      Route::post('/modificar-calificacion', 'App\Http\Controllers\pages\historicoClinico\HistoricoClinicoSeguimientoController@modificarCalificacion')->name('modificar-calificacion');
      Route::post('/obtener-movimientos-calificacion', 'App\Http\Controllers\pages\historicoClinico\HistoricoClinicoSeguimientoController@obtenerMovimientosCalificacion')->name('obtener-movimientos-calificacion');
    });
  });

  Route::prefix('reportes')->group(function () {
    Route::get('/recetas', 'App\Http\Controllers\pages\reportes\ReportesController@recetas')->name('reporte-recetas');
    Route::get('/pacientes', 'App\Http\Controllers\pages\reportes\ReportesController@pacientes')->name('reporte-pacientes');
    Route::get('/consultas', 'App\Http\Controllers\pages\reportes\ReportesController@consultas')->name('reporte-consultas');
    Route::get('/estadisticas', 'App\Http\Controllers\pages\reportes\ReportesController@estadisticas')->name('reporte-estadistico');

    # ****************************************************************************************
    # ****************************************************************************************

    Route::prefix('api')->group(function () {
      Route::post('/recetas/exportar', 'App\Http\Controllers\pages\reportes\ReportesDescargaController@exportarRecetas');
      Route::post('/pacientes/exportar', 'App\Http\Controllers\pages\reportes\ReportesDescargaController@exportarPacientes');
      Route::post('/consultas/exportar', 'App\Http\Controllers\pages\reportes\ReportesDescargaController@exportarConsultas');

      Route::post('/estadistico-consulta-por-colaborador/obtener','App\Http\Controllers\pages\reportes\ReportesDescargaController@obtenerConsultasPorColaborador');
      Route::post('/estadistico-consulta-por-colaborador/exportar','App\Http\Controllers\pages\reportes\ReportesDescargaController@exportarConsultasPorColaborador');
      Route::post('/estadistico-consulta-total-por-colaborador/obtener','App\Http\Controllers\pages\reportes\ReportesDescargaController@obtenerConsultasTotalesPorColaborador');
      Route::post('/estadistico-consulta-total-por-colaborador/exportar','App\Http\Controllers\pages\reportes\ReportesDescargaController@exportarConsultasTotalesPorColaborador');
      Route::post('/estadistico-consulta-media-mediana-moda/obtener','App\Http\Controllers\pages\reportes\ReportesDescargaController@obtenerMediaMedianaModa');
      Route::post('/estadistico-consulta-media-mediana-moda/exportar','App\Http\Controllers\pages\reportes\ReportesDescargaController@exportarMediaMedianaModa');
      Route::post('/estadistico-medicamento-mas-recetado/obtener','App\Http\Controllers\pages\reportes\ReportesDescargaController@obtenerMedicamentoMasRecetado');
      Route::post('/estadistico-medicamento-mas-recetado/exportar','App\Http\Controllers\pages\reportes\ReportesDescargaController@exportarMedicamentoMasRecetado');
      Route::post('/estadistico-motivos-visita-frecuentes/obtener','App\Http\Controllers\pages\reportes\ReportesDescargaController@obtenerMotivosVisitaMasFrecuentes');
      Route::post('/estadistico-motivos-visita-frecuentes/exportar','App\Http\Controllers\pages\reportes\ReportesDescargaController@exportarMotivosVisitaMasFrecuentes');
    });
  });

  Route::prefix('medicamentos')->group(function () {
    Route::get('/existencias', 'App\Http\Controllers\pages\medicamentos\MedicamentosController@existencias')->name('medicamentos-existencias');

    # ****************************************************************************************
    # ****************************************************************************************

    Route::prefix('api')->group(function () {});
  });
});

Route::middleware([
  'auth:sanctum',
  config('jetstream.auth_session'),
  'verified',
])->group(function () {
  Route::get('/dashboard', function () {
    return view('dashboard');
  })->name('dashboard');
});
