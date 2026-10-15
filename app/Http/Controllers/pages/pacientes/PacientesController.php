<?php

namespace App\Http\Controllers\pages\pacientes;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\PacienteEmpresaModel;
use App\Models\PacienteUnidadNegocioModel;
use App\Models\PacienteAreaModel;
use App\Models\PacienteSubareaModel;
use App\Models\PacienteModel;
use App\Models\PacienteCIEModel;
use App\Models\PacienteDatosConsultaModel;
use App\Models\PacienteDatosConsultaHistoricoModel;
use App\Models\PacienteHistoricoModel;
use App\Models\PacienteTipoVisitaModel;
use App\Models\PacienteDatosConsultaNotaModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class PacientesController extends Controller
{
  # Retorna la vista de registrar nuevo paciente
  public function nuevoPaciente()
  {
    if (Auth::user()->usuario_perfil_id == 1 || Auth::user()->usuario_perfil_id == 2 || Auth::user()->usuario_perfil_id == 3 || Auth::user()->usuario_perfil_id == 4 || Auth::user()->usuario_perfil_id == 5) {

      # Obtenemos las ocupaciones para mandarlas a la vista
      $view_data['catalogos']['empresas'] = PacienteEmpresaModel::where('borrado', 0)->get()->toArray();
      $view_data['catalogos']['unidad_negocio'] = PacienteUnidadNegocioModel::where('borrado', 0)->get()->toArray();
      $view_data['catalogos']['area'] = PacienteAreaModel::where('borrado', 0)->get()->toArray();
      $view_data['catalogos']['subarea'] = PacienteSubareaModel::where('borrado', 0)->get()->toArray();
      # Mandamos a la  vista
      return view('content.pages.paciente.nuevo-paciente', ['datos_vista' => $view_data]);
    } else {
      return view('content.pages.pages-misc-error');
    }
  }

  public function consultarPacienteFortia(Request $request)
  {
    # Obtiene el número de empleado desde el formulario enviado por POST
    $numeroEmpleado = $request->input('filtro.paciente_numero_gafete');

    try {
      # Buscar un paciente por el gafete para ver si esta ya registrado en el sistema
      $pacienteExistente = PacienteModel::where('gafete', $numeroEmpleado)->where('borrado', 0)->first();

      if ($pacienteExistente) {
        # Respuesta de la solicitud HTTP si ya existe el paciente registrado
        return response()->json([
          'bandera' => 201,
          'bandera_descripcion' => 'El paciente ya cuenta con registros en el sistema',
          'error' => false,
          'msg' => 'El paciente ya cuenta con expediente en el sistema',
          'body' => [
            'id' => Crypt::encryptString($pacienteExistente->id),
            'codigo' => $pacienteExistente->gafete,
            'nombre' => $pacienteExistente->nombre,
            'ap_paterno' => $pacienteExistente->apellido_paterno,
            'ap_materno' => $pacienteExistente->apellido_materno,
            'sexo' => $pacienteExistente->genero,
            'celular' => $pacienteExistente->celular,
            'edad' => $pacienteExistente->edad,
            'curp' => $pacienteExistente->curp,
            'unidad_negocio_id' => $pacienteExistente->paciente_unidad_negocio_id,
            'area_id' => $pacienteExistente->paciente_area_id,
            'subarea_id' => $pacienteExistente->paciente_subarea_id,
            'empresa_id' => $pacienteExistente->paciente_empresa_id,
          ]
        ]);
      } else {
        # Buscamos el numero de empleado en fortia
        $datosEmpleadoFortia = Helpers::obtenerDatosEmpleadoFortia(["employee_id" => $numeroEmpleado]);

        $bodyResponse = $datosEmpleadoFortia['data'][0] ?? [];

        $encontrado = $bodyResponse != [];

        if (!$encontrado) {
            $mensaje = 'Error. No encontró información del empleado en Fortia';
            $bandera = 404;
            $error = true;
        } elseif (($bodyResponse['employee_status'] ?? null) === 'B') {
            $mensaje = 'Error. El empleado se encuentra dado de baja en Fortia';
            $bandera = 404;
            $error = true;
        } elseif (($bodyResponse['employee_status'] ?? null) === 'A') {
            $mensaje = 'Éxito. Se encontró información del empleado activo en Fortia';
            $bandera = 200;
            $error = false;
        } else {
            $mensaje = 'Error. El empleado tiene un estado desconocido en Fortia';
            $bandera = 404;
            $error = true;
        }

        # Respuesta de la solicitud HTTP
        return response()->json([
            'bandera' => $bandera,
            'bandera_descripcion' => $mensaje,
            'error' => $error,
            'msg' => $mensaje,
            'body' => $datosEmpleadoFortia['data'] ?? []
        ]);

      }
    } catch (\Exception $e) {
      # Capturar cualquier excepción durante la solicitud HTTP
      return response()->json([
        'bandera' => 500,
        'bandera_descripcion' => 'Problema de conexion',
        'error' => true,
        'msg' => 'Ocurrió un error al intentar comunicarse con la API: ' . $e->getMessage(),
        'body' => null,
      ], 500);
    }
  }

  public function registrarPaciente(Request $request)
  {
    # Parametros iniciales para la respuesta
    $result = array("error" => false, "msg" => null, 'url' => null, 'paciente_id' => null);
    # Obtenemos el POST
    $post = $request->all();
    # Iniciamos transaccion de BD
    DB::connection('pgsql')->beginTransaction();
    try {
      $array_ids = array();
      # Iniciamos con el guardamos los datos obtenidos del formulario.
      foreach ($post as $key => $value) {
        switch ($key) {
          # Guardamos los datos del paciente
          case "paciente":
            # Asignamos la fecha de registro
            $value['created_at'] = 'now()';
            $value['usuario_id'] = Auth::user()->id;

            #insertamos los valores en la tabla del paciente
            $paciente = PacienteModel::insertGetId($value);

            if ($paciente !== null) {
              $array_ids["paciente_id"] = $paciente;
            } else {
              $result["error"] = true;
              $result["msg"] = "No fue posible registrar el paciente, intenta de nuevo.";
            }
            # Hacemos un ciclo para formar el array para el histórico de manera más dinámica
            $array_ids['historico']['paciente'] = $value;
            break;
        }
      }

      # Si no existe ningun error al insertar el paciente entra
      if (!$result["error"]) {
        # Formamos el historico de insertar paciente
        $value_historico = array(
          'paciente_id' => $array_ids["paciente_id"],
          'tipo_movimiento' => 'I',
          'data' => json_encode($array_ids['historico']),
          'created_at' => date("Y-m-d H:i:s"),
          'updated_at' => null
        );
        # Insertamos el historico del registro
        $paciente_historico = PacienteHistoricoModel::insert($value_historico);
        if (!$paciente_historico) {
          $result["error"] = true;
          $result["msg"] = "No fue posible generar el historico del registro del paciente.";
        }
      }
      # Si no existe error al guardar el registro del historico entra
      if (!$result["error"]) {
        # Si todo esta correcto hacemos el commit de la transaccion
        DB::connection('pgsql')->commit();
        $result['error'] = false;
        $result["paciente_id"] = Crypt::encryptString($array_ids["paciente_id"]);
        $result["msg"] = 'El paciente fue registrado correctamente';
        $result["url"] = '/pacientes/nuevo';
      } else {
        # Si existe un error no guardamos en la base de datos
        DB::connection('pgsql')->rollback();
        $result['error'] = true;
        $result["msg"] = '¡Lo sentimos! No fue posible registrar información del paciente.';
      }
    } catch (\Exception $e) {
      # Si existe un error no guardamos en la base de datos
      DB::connection('pgsql')->rollback();
      $result['error'] = true;
      $result["msg"] = "¡Lo sentimos! No fue posible registrar el paciente favor revisa que no existan campos vacíos en la captura. Inténtalo de nuevo y si el problema persiste contacta con el equipo de desarrollo.";
      $result['return'] = $e->getMessage();
    }
    return response()->json($result);
  }

  public function registrarValoracionPaciente(Request $request)
  {
    if (Auth::user()->usuario_perfil_id == 1 || Auth::user()->usuario_perfil_id == 2 || Auth::user()->usuario_perfil_id == 3 || Auth::user()->usuario_perfil_id == 4 || Auth::user()->usuario_perfil_id == 5) {

      # Obtiene el ID desde la URL
      $pacienteId = Crypt::decryptString($request->query('paciente_id'));

      # Verifica si el ID existe
      if (!$pacienteId) {
        return redirect()->back()->with('error', 'No se proporcionó un ID de paciente.');
      }

      $view_data['paciente_id'] = Crypt::encryptString($pacienteId);
      $view_data['paciente']['datos_paciente'] = PacienteModel::select('nombre', 'apellido_paterno', 'apellido_materno')->where('id', $pacienteId)->where('borrado', 0)->first();
      $view_data['paciente']['datos_ultima_consulta'] = PacienteDatosConsultaModel::where('paciente_id', $pacienteId)->where('borrado', 0)->latest('created_at')->first();
      $view_data['catalogos']['tipo_visita'] = PacienteTipoVisitaModel::where('borrado', 0)->get()->toArray();

      # Mandamos a la vista
      return view('content.pages.paciente.valoracion-paciente', ['datos_vista' => $view_data]);
    } else {
      return view('content.pages.pages-misc-error');
    }
  }

  public function guardarValoracionPaciente(Request $request)
  {
    # Parametros iniciales para la respuesta
    $result = array("error" => false, "msg" => null, 'url' => null);
    # Obtenemos el POST
    $post = $request->all();

    # Validación del CIE-10
    if (empty($post['paciente_datos_consulta']['cie_id'])) {
      $result["error"] = true;
      $result["msg"] = "Debe seleccionar un CIE-10 válido";
      return response()->json($result);
    }

    unset($post['paciente_datos_consulta']['cie_id_hidden']);

    # Iniciamos transaccion de BD
    DB::connection('pgsql')->beginTransaction();

    try {
      $array_ids = array();
      # Iniciamos con el guardamos los datos obtenidos del formulario.
      foreach ($post as $key => $value) {
        switch ($key) {
          # Guardamos los datos del paciente
          case "paciente_datos_consulta":
            # Asignamos la fecha de registro
            $value['created_at'] = 'now()';

            $value['paciente_id'] = Crypt::decryptString($value['paciente_id']);
            $value['usuario_id'] = Auth::user()->id;

            #insertamos los valores en la tabla del paciente
            $pacienteDatos = PacienteDatosConsultaModel::insertGetId($value);

            if ($pacienteDatos !== null) {
              $array_ids["paciente_datos_consulta_id"] = $pacienteDatos;
            } else {
              $result["error"] = true;
              $result["msg"] = "No fue posible registrar la valoracion del paciente, intenta de nuevo.";
            }
            # Hacemos un ciclo para formar el array para el histórico de manera más dinámica
            $array_ids['historico']['paciente_datos_consulta'] = $value;
            break;
        }
      }

      # Si no existe ningun error al insertar la valoracion del paciente entra
      if (!$result["error"]) {
        # Formamos el historico de insertar paciente_datos_consulta
        $value_historico = array(
          'paciente_datos_consulta_id' => $array_ids["paciente_datos_consulta_id"],
          'tipo_movimiento' => 'I',
          'data' => json_encode($array_ids['historico']),
          'created_at' => date("Y-m-d H:i:s"),
          'updated_at' => null
        );
        # Insertamos el historico del registro
        $paciente_datos_consulta_historico = PacienteDatosConsultaHistoricoModel::insert($value_historico);
        if (!$paciente_datos_consulta_historico) {
          $result["error"] = true;
          $result["msg"] = "No fue posible generar el historico del registro de la valoracion del paciente.";
        }
      }
      # Si no existe error al guardar el registro del historico entra
      if (!$result["error"]) {
        # Si todo esta correcto hacemos el commit de la transaccion
        DB::connection('pgsql')->commit();
        $result['error'] = false;
        $result["msg"] = 'La valoracion del paciente fue registrada correctamente';
        $result["url"] = '/';
      } else {
        # Si existe un error no guardamos en la base de datos
        DB::connection('pgsql')->rollback();
        $result['error'] = true;
        $result["msg"] = '¡Lo sentimos! No fue posible registrar la valoracion del paciente.';
      }
    } catch (\Exception $e) {
      # Si existe un error no guardamos en la base de datos
      DB::connection('pgsql')->rollback();
      $result['error'] = true;
      $result["msg"] = "¡Lo sentimos! No fue posible registrar la valoracion del paciente, favor revisa que no existan campos vacíos en la captura. Inténtalo de nuevo y si el problema persiste contacta con el equipo de desarrollo.";
      $result['return'] = $e->getMessage();
    }
    return response()->json($result);
  }

  public function registrarNota(Request $request)
  {
    # Parametros iniciales para la respuesta
    $result = array("error" => false, "msg" => null, 'url' => null);
    # Obtenemos el POST
    $post = $request->all();
    // dd($post);
    # Iniciamos transaccion de BD
    DB::connection('pgsql')->beginTransaction();
    try {
      # Iniciamos con el guardamos los datos obtenidos del formulario.
      foreach ($post as $key => $value) {
        switch ($key) {
          # Guardamos los datos de la nota
          case "paciente_datos_consulta_nota":
            # Asignamos la fecha de registro
            $value['created_at'] = 'now()';

            # Usuario id que registra la nota
            $value['usuario_id'] = Auth::user()->id;

            #insertamos los valores en la tabla del paciente
            $nota = PacienteDatosConsultaNotaModel::insertGetId($value);

            if ($nota !== null) {
            } else {
              $result["error"] = true;
              $result["msg"] = "No fue posible registrar la nota, intenta de nuevo.";
            }
            break;
        }
      }

      # Si no existe error al guardar el registro de la nota entra
      if (!$result["error"]) {
        # Si todo esta correcto hacemos el commit de la transaccion
        DB::connection('pgsql')->commit();
        $result['error'] = false;
        $result["msg"] = 'La nota fue registrada correctamente';
      } else {
        # Si existe un error no guardamos en la base de datos
        DB::connection('pgsql')->rollback();
        $result['error'] = true;
        $result["msg"] = '¡Lo sentimos! No fue posible registrar información del paciente.';
      }
    } catch (\Exception $e) {
      # Si existe un error no guardamos en la base de datos
      DB::connection('pgsql')->rollback();
      $result['error'] = true;
      $result["msg"] = "¡Lo sentimos! No fue posible registrar la nota favor revisa que no existan campos vacíos en la captura. Inténtalo de nuevo y si el problema persiste contacta con el equipo de desarrollo.";
      $result['return'] = $e->getMessage();
    }
    return response()->json($result);
  }

  public function buscarCie(Request $request)
  {
    try {
      $query = $request->input('nombre_cie');

      # Búsqueda con like y paginación
      $results = PacienteCIEModel::select('id', 'codigo', 'descripcion')
        ->where('borrado', 0)
        ->where(function ($q) use ($query) {
          $q->where('descripcion', 'iLIKE', "%{$query}%")
            ->orWhere('codigo', 'iLIKE', "%{$query}%");
        })
        ->orderBy('descripcion')->get()->toArray();

      return response()->json([
        'error' => false,
        'data' => $results
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'error' => true,
        'msg' => 'Error al realizar la búsqueda: ' . $e->getMessage()
      ], 500);
    }
  }
}
