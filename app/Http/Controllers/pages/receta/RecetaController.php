<?php

namespace App\Http\Controllers\pages\receta;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Http;
use App\Models\PacienteModel;
use App\Models\RecetaModel;
use App\Models\RecetaMedicamentoModel;
use App\Models\UsuarioAlmacenModel;
use App\Models\RecetaValeHistoricoModel;
use App\Models\RecetaConsumoHistoricoModel;
use App\Models\CatalogoRanchosAgrizarModel;
use App\Models\UsuarioPerfilModel;
use App\Models\UsuarioFirmaModel;
use App\Models\RecetaFirmaPacienteModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class RecetaController extends Controller
{
  # Retorna la vista de crear una receta nueva
  public function nuevaReceta(Request $request)
  {
    if (Auth::user()->usuario_perfil_id == 1 || Auth::user()->usuario_perfil_id == 2 || Auth::user()->usuario_perfil_id == 3 || Auth::user()->usuario_perfil_id == 4 || Auth::user()->usuario_perfil_id == 5 || Auth::user()->usuario_perfil_id == 7) {
      $post = $request->all();

      if (count($post) == 1) {
        # Obtiene el ID desde la URL
        $detalle_receta_id = Crypt::decryptString($request->query('detalle_receta_id'));

        # Datos generales de la receta
        $detalle_receta = RecetaModel::select(
          'receta.id as receta_id',
          'usuario.nombre as usuario_creador_nombre',
          'usuario.apellido_paterno as usuario_creador_apellido_p',
          'usuario.apellido_materno as usuario_creador_apellido_m',
          'usuario.registro_ssa',
          'usuario.cedula_profesional',
          'usuario.usuario_perfil_id',
          'usuario.universidad_egreso',
          'usuario_perfil.nombre as usuario_perfil',
          'paciente.nombre as paciente_nombre',
          'paciente.apellido_paterno as paciente_apellido_p',
          'paciente.apellido_materno as paciente_apellido_m',
          'paciente.edad as paciente_edad',
          'receta.medicamento_indicaciones as medicamento',
          'receta.recomendaciones',
          'receta.created_at as fecha_creacion',
          'usuario_firma.firma as firma_usuario',
          'receta_firma_paciente.firma as firma_paciente'
        )
          ->join('usuario', 'usuario.id', '=', 'receta.usuario_id')
          ->leftJoin('usuario_firma', function ($join) {
            $join->on('usuario_firma.usuario_id', '=', 'usuario.id')
              ->where('usuario_firma.borrado', 0);
          })
          ->leftJoin('receta_firma_paciente', function ($join) {
            $join->on('receta_firma_paciente.receta_id', '=', 'receta.id');
          })
          ->join('paciente', 'paciente.id', '=', 'receta.paciente_id')
          ->join('usuario_perfil', 'usuario_perfil.id', '=', 'usuario.usuario_perfil_id')
          ->where('receta.id', $detalle_receta_id)
          ->where('usuario.borrado', 0)
          ->where('receta.borrado', 0)
          ->where('usuario_perfil.borrado', 0)
          ->first();

        # Medicamentos de la receta
        $medicamentos = RecetaMedicamentoModel::select(
          'medicamento_nombre',
          'abreviatura',
          'cantidad_solicitada'
        )
          ->where('receta_id', $detalle_receta_id)
          ->where('borrado', 0)
          ->get()
          ->toArray();

        $view_data['detalles_receta'] = $detalle_receta ? [$detalle_receta->toArray()] : [];
        $view_data['medicamentos'] = $medicamentos;
        $view_data['perfil_nombre'] = [];
      } else {
        $view_data['perfil_nombre'] = UsuarioPerfilModel::select('nombre')->where('id', Auth::user()->usuario_perfil_id)->first();
        $view_data['firma_usuario_logueado'] = UsuarioFirmaModel::where('usuario_id', Auth::user()->id)->where('borrado', 0)->value('firma');
      }

      $usuario_almacenes = UsuarioAlmacenModel::select('empresa_id', 'empresa_nombre', 'almacen_id', 'almacen_nombre', 'almacen_codigo')->where('usuario_id', Auth::user()->id)->where('borrado', 0)->get()->toArray();
      $view_data['usuario_almacenes'] = $usuario_almacenes;

      # Validamos si el usuario tiene cedula profesional para tener permiso para acceder a esta seccion
      if (Auth::user()->cedula_profesional) {
        $view_data['pacientes'] = PacienteModel::where('borrado', 0)->select('id', 'nombre', 'apellido_paterno', 'apellido_materno', 'edad')->get()->toArray();

        $lastFolio = RecetaModel::max('id');
        $view_data['folio'] = $lastFolio ? $lastFolio + 1 : 1;

        # Mandamos a la  vista
        return view('content.pages.receta.nueva-receta', ['datos_vista' => $view_data]);
      } else {
        return view('content.pages.pages-misc-error');
      }
    } else {
      return view('content.pages.pages-misc-error');
    }
  }

  public function buscarEmpleadoFortia(Request $request)
  {
    $gafete = trim($request->input('gafete', ''));

    if ($gafete === '') {
      return response()->json(['error' => true, 'msg'   => 'Debes ingresar un número de gafete.']);
    }

    try {      
      $empleados = Helpers::obtenerDatosEmpleadoFortia(['employee_id' => $gafete]);

      if (empty($empleados) || ($empleados['status'] ?? null) != 200) {
        return response()->json(['error' => true, 'msg' => 'Ocurrió un error con la consulta a Fortia.']);
      }

      $datos = $empleados['data'] ?? [];

      if (empty($datos)) {
        return response()->json(['error' => true, 'msg' => 'No se encontró información del empleado con ese gafete en Fortia.']);
      }

      // Buscar el empleado por su número de gafete exacto
      $empleado = collect($datos)->first(function ($item) use ($gafete) {
        return (string) ($item['employee_id'] ?? '') === (string) $gafete;
      });

      if (empty($empleado)) {
        return response()->json(['error' => true, 'msg' => 'No se encontró información del empleado con ese gafete en Fortia.']);
      }

      // Validar el estado del empleado en Fortia
      $estado = $empleado['employee_status'] ?? null;

      if ($estado === 'B') {
        return response()->json(['error' => true, 'msg' => 'El empleado se encuentra dado de baja en Fortia.']);
      }

      if ($estado !== 'A') {
        return response()->json(['error' => true, 'msg' => 'No fue posible validar el estado del empleado en Fortia.']);
      }

      // Empleado activo: conservar la respuesta que consume tu sistema
      return response()->json([
        'error' => false,
        'empleado' => [
          'codigo' => $empleado['employee_id'] ?? $gafete,
          'nombre' => $empleado['employee_name'] ?? '',
          'ap_paterno' => $empleado['employee_last_name'] ?? '',
          'ap_materno' => $empleado['employee_second_last_name'] ?? '',
          'curp' => $empleado['curp'] ?? '',
          'genero' => $empleado['gender'] ?? '',
        ],
      ]);

    } catch (\Throwable $e) {
      return response()->json(['error' => true, 'msg'   => 'No fue posible consultar Fortia. Intenta de nuevo.']);
    }
  }

  # Funcion para guardar los datos de la receta
  public function registrarReceta(Request $request)
  {
    $token = Helpers::obtenerToken();
    if (!$token) {
      return response()->json(['error' => true, 'msg' => 'No se pudo obtener token'], 500);
    }

    $result = ["error" => false, "msg" => null, 'url' => null, 'receta_id' => null];
    $post = $request->all();
    $post['medicamentos'] = json_decode($request->input('medicamentos'), true);

    $validaEmpleadoRegistradoEnSistemaMedico = PacienteModel::where('gafete', $post['empleado']['gafete'])->where('borrado', 0)->first();

    $post['empleado']['created_at'] = now();
    if (!$validaEmpleadoRegistradoEnSistemaMedico) {
      $registrarEmpleadoAlServicioMedico = PacienteModel::insertGetId($post['empleado']);
      if (!$registrarEmpleadoAlServicioMedico) {
        return response()->json(['error' => true, 'msg' => 'No fue posible registrar al empleado en el sistema médico automaticamente'], 500);
      } else {
        $datosPaciente = $this->obtenerDatosPaciente($registrarEmpleadoAlServicioMedico);
      }
    } else {
      $datosPaciente = $this->obtenerDatosPaciente($validaEmpleadoRegistradoEnSistemaMedico->id);
    }
    $post['receta']['paciente_id'] = $validaEmpleadoRegistradoEnSistemaMedico ? $validaEmpleadoRegistradoEnSistemaMedico->id : $registrarEmpleadoAlServicioMedico;

    # Agrupamos medicamentos por empresa y almacén
    $grupos = $this->agruparMedicamentos($post['medicamentos']);

    # Procesamos cada grupo (vale y consumo)
    foreach ($grupos as $grupo) {
      $respuesta = $this->procesarGrupo($grupo, $token, $datosPaciente);

      if ($respuesta['error']) {
        return response()->json($respuesta);
      }
    }

    # Guardamos receta en BD
    $respuestaBD = $this->guardarRecetaBD($post, $result);
    return response()->json($respuestaBD);
  }

  private function obtenerDatosPaciente($pacienteId)
  {
    return PacienteModel::select('nombre', 'apellido_paterno', 'apellido_materno', 'gafete')->where('id', $pacienteId)->first();
  }

  private function agruparMedicamentos($medicamentos)
  {
    $grupos = [];
    foreach ($medicamentos as $item) {
      $clave = $item['empresa_id'] . '-' . $item['almacen_id'];

      $grupos[$clave]['empresa_id'] = $item['empresa_id'];
      $grupos[$clave]['almacen_id'] = $item['almacen_id'];

      $grupos[$clave]['items'][] = [
        "id" => $item["medicamento_id"],
        "codigo" => $item["medicamento_codigo"],
        "nombre" => $item["medicamento_nombre"],
        "uso" => "De uso médico",
        "unidad" => $item["abreviatura"],
        "cantidad" => $item["cantidad_solicitada"]
      ];
    }
    return $grupos;
  }

  private function crearJsonVale($grupo, $paciente)
  {
    #centro de costos fijo por el momento por usuario logueado
    $centroCostosCatalogo = CatalogoRanchosAgrizarModel::where('id', Auth::user()->catalogo_ranchos_agrizar_id)->where('borrado', 0)->first()->centro_costo;


    return [
      "fecha" => date('Y-m-d'),
      "solicitanteid" => 159,
      "autorizadorid" => 159,
      "estadoid" => 3,
      "empresaid" => $grupo['empresa_id'],
      "almacenid" => $grupo['almacen_id'],
      "prioridadid" => 1,
      "tipoid" => 1,
      "almacenaltaid" => 1,
      "centrocostoid" => '',
      "centrocostocodigo" => $centroCostosCatalogo,
      "ordencompraid" => "",
      "nombreentregado" => $this->nombreCompleto($paciente),
      "fechaaplicacion" => date('Y-m-d'),
      "items" => $grupo['items']
    ];
  }

  private function crearJsonConsumo($valeId, $paciente, $centroCostos)
  {
    return [
      "moveuserid" => 445,
      "passhispatec" => "S3rvMedic@",
      "valeid" => $valeId,
      "aplicationdate" => date('Y-m-d'),
      "receptionname" => $this->nombreCompleto($paciente),
      "signature" => "",
      "observations" => "Consumo generado desde: Sistema del Servicio Medico -> Centro de costos: " . $centroCostos . " -> Vale id: " . $valeId
    ];
  }

  private function nombreCompleto($paciente)
  {
    return "Dr. " . Auth::user()->nombre . " " . Auth::user()->apellido_paterno . " " . Auth::user()->apellido_materno . " > Paciente: " . $paciente->nombre . " " . $paciente->apellido_paterno . " " . $paciente->apellido_materno;
  }

  private function postAPI($url, $json, $token)
  {
    $response = Http::withHeaders([
      'Authorization' => 'Bearer ' . $token,
      'Accept' => 'application/json',
      'Content-Type'  => 'application/json'
    ])->withoutVerifying()->send('POST', $url, ['json' => $json]);

    if (!$response->successful()) {
      return [
        'error' => true,
        'msg' => "No fue posible conectar con la API",
      ];
    }

    $data = $response->json();

    if (isset($data['error']) && $data['error'] == true) {
      return [
        'error' => true,
        'msg' => "La API devolvió un error",
        'data' => $data
      ];
    }

    return ['error' => false, 'data' => $data];
  }

  private $historicoConsumoId = null;
  private $historicoValeId = null;

  private function guardarRecetaBD($post, $result)
  {
    DB::connection('pgsql')->beginTransaction();
    try {
      foreach ($post as $key => $value) {
        if ($key === "receta") {
          $value['created_at'] = now();
          $value['usuario_id'] = Auth::user()->id;

          $result["receta_id"] = RecetaModel::insertGetId($value);

          if (!$result["receta_id"]) {
            return ['error' => true, 'msg' => "No fue posible registrar la receta"];
          }
        }
        if ($key === "medicamentos") {
          foreach ($value as $medicamento) {
            $medicamento['receta_id'] = $result["receta_id"];
            $medicamento['created_at'] = now();
            RecetaMedicamentoModel::insert($medicamento);
          }
        }
      }

      # Se quito el consumo de Hispatec para que se haga al surtir la receta desde la farmacia
      $BanderaHacerConsumo = 'no';

      if ($BanderaHacerConsumo == 'si') {
        if ($this->historicoConsumoId) {
          RecetaConsumoHistoricoModel::where('id', $this->historicoConsumoId)->update(['receta_id' => $result["receta_id"]]);
        } else {
          DB::connection('pgsql')->rollback();
          return ['error' => true, 'msg' => "Error al registrar receta en BD. No se pudo asociar la receta con el historico del consumo"];
        }
      }

      if ($this->historicoValeId) {
        RecetaValeHistoricoModel::where('id', $this->historicoValeId)->update(['receta_id' => $result["receta_id"]]);
      } else {
        DB::connection('pgsql')->rollback();
        return ['error' => true, 'msg' => "Error al registrar receta en BD. No se pudo asociar la receta con el historico del vale"];
      }

      DB::connection('pgsql')->commit();
      return [
        "error" => false,
        "msg" => "La receta fue registrada correctamente",
        "receta_id" => $result["receta_id"]
      ];
    } catch (\Exception $e) {
      DB::connection('pgsql')->rollback();
      return ['error' => true, 'msg' => "Error al registrar receta en BD", 'return' => $e->getMessage()];
    }
  }

  private function procesarGrupo($grupo, $token, $datosPaciente)
  {
    DB::connection('pgsql')->beginTransaction();

    try {
      # Generar vale
      $jsonVale = $this->crearJsonVale($grupo, $datosPaciente);
      $respuestaVale = $this->postAPI(env('GENERA_VALE_RD'), $jsonVale, $token);

      if ($respuestaVale['error']) {
        return $respuestaVale;
      }

      # Guardar histórico del vale
      $this->historicoValeId = RecetaValeHistoricoModel::insertGetId([
        'json_data' => json_encode($jsonVale),
        'respuesta_api' => json_encode($respuestaVale['data']),
        'vale_id' => $respuestaVale['data']['valeid'],
        'centro_costos' => $jsonVale['centrocostocodigo'],
        'created_at' => now()
      ]);

      # Se quito el consumo de Hispatec para que se haga al surtir la receta desde la farmacia
      $BanderaHacerConsumo = 'no';

      if ($BanderaHacerConsumo == 'si') {
        # Generar consumo en Hispatec
        $jsonConsumo = $this->crearJsonConsumo($respuestaVale['data']['valeid'], $datosPaciente, $jsonVale['centrocostocodigo']);
        $respuestaConsumo = $this->postAPI(env('GENERA_CONSUMO_HISPATEC'), $jsonConsumo, $token);

        if ($respuestaConsumo['error']) {
          return $respuestaConsumo;
        }

        # Guardar histórico consumo
        $this->historicoConsumoId = RecetaConsumoHistoricoModel::insertGetId([
          'json_data' => json_encode($jsonConsumo),
          'respuesta_api' => json_encode($respuestaConsumo['data']),
          'created_at' => now(),
          'centro_costos' => $jsonVale['centrocostocodigo'],
          'vale_id' => $respuestaVale['data']['valeid']
        ]);
      }

      DB::connection('pgsql')->commit();
      return ['error' => false];
    } catch (\Exception $e) {
      DB::connection('pgsql')->rollback();
      return ['error' => true, 'msg' => "Error al comunicarse con las APIs", 'return' => $e->getMessage()];
    }
  }


  public function recetasPaciente(Request $request)
  {
    if (Auth::user()->usuario_perfil_id == 1 || Auth::user()->usuario_perfil_id == 2 || Auth::user()->usuario_perfil_id == 3 || Auth::user()->usuario_perfil_id == 4 || Auth::user()->usuario_perfil_id == 5 || Auth::user()->usuario_perfil_id == 7) {

      if (count($request->all()) == 0) {
        $view_data['paciente']['recetas'] = RecetaModel::where('borrado', 0)->get()->toArray();
      } else {
        $paciente_id = Crypt::decryptString($request->query('paciente_id'));
        # Verifica si el ID existe
        if (!$paciente_id) {
          return redirect()->back()->with('error', 'No se proporcionó un ID de paciente.');
        }
        $view_data['paciente_id'] = $paciente_id;
        $view_data['paciente']['recetas'] = RecetaModel::where('id', $paciente_id)->where('borrado', 0)->get()->toArray();
        $view_data['paciente']['datos_generales'] = PacienteModel::where('id', $paciente_id)->where('borrado', 0)->get()->toArray();
      }

      # Mandamos a la  vista
      return view('content.pages.receta.listado-receta-paciente', ['datos_vista' => $view_data]);
    } else {
      return view('content.pages.pages-misc-error');
    }
  }

  public function obtenerListadoRecetasPaciente(Request $request)
  {
    $detalle_receta = RecetaModel::select(
      'receta.id as  receta_id',
      'receta.paciente_id as paciente_id',
      'usuario.nombre as medico_nombre',
      'usuario.apellido_paterno as medico_apellido_p',
      'usuario.apellido_materno as medico_apellido_m',
      'usuario.registro_ssa as registro_ssa',
      'usuario.cedula_profesional as cedula_profesional',
      'usuario.usuario_perfil_id as usuario_perfil_id',
      'paciente.gafete as paciente_gafete',
      'paciente.nombre as paciente_nombre',
      'paciente.apellido_paterno as paciente_apellido_p',
      'paciente.apellido_materno as paciente_apellido_m',
      'paciente.edad as paciente_edad',
      'receta.medicamento_indicaciones as indicaciones_medicamento',
      'receta.recomendaciones as recomendaciones',
      'receta.created_at as fecha_creacion',
      'receta_estatus.nombre as estatus_nombre',
      'receta_estatus.icono as estatus_icono',
      'receta_estatus.clase as estatus_clase',
      'receta.surtida as se_entrego_medicamento'
    )
      ->join('usuario', 'usuario.id', '=', 'receta.usuario_id')
      ->leftJoin('receta_estatus', function ($join) {
        $join->on('receta_estatus.id', '=', 'receta.receta_estatus_id')
          ->where('receta_estatus.borrado', 0);
      })
      ->join('paciente', 'paciente.id', '=', 'receta.paciente_id');


    # Filtros de fechas en la query
    if ($request->filled('fecha_inicio') && !$request->filled('fecha_fin')) {
      $detalle_receta->whereDate('receta.created_at', '=', $request->fecha_inicio);
    } elseif (!$request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
      $detalle_receta->whereDate('receta.created_at', '=', $request->fecha_fin);
    } elseif ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
      $detalle_receta->whereBetween('receta.created_at', [
        $request->fecha_inicio . ' 00:00:00',
        $request->fecha_fin . ' 23:59:59'
      ]);
    }

    # Filtro por numero de empleado
    if ($request->filled('numero_empleado')) {
      $detalle_receta->where('paciente.gafete', 'iLIKE', "%$request->numero_empleado%");
    }

    # Filtro por folio
    if ($request->filled('folio')) {
      $detalle_receta->where('receta.id', '=', $request->folio);
    }

    # Filtro por nombre
    if ($request->filled('empleado_nombre')) {
      $nombre = $request->empleado_nombre;
      $detalle_receta->where(function ($q) use ($nombre) {
        $q->where('paciente.nombre', 'iLIKE', "%$nombre%")
          ->orWhere('paciente.apellido_paterno', 'iLIKE', "%$nombre%")
          ->orWhere('paciente.apellido_materno', 'iLIKE', "%$nombre%")
          ->orWhereRaw("CONCAT(paciente.nombre, ' ', paciente.apellido_paterno, ' ', paciente.apellido_materno) iLIKE ?", ["%$nombre%"]);
      });
    }

    if ($request->filled('paciente_id')) {
      $detalle_receta->where('receta.paciente_id', $request->paciente_id);
    }

    $detalle_receta = $detalle_receta->where('usuario.borrado', 0)
      ->where('receta.borrado', 0)
      ->orderBy('receta.id', 'desc');

    return DataTables::eloquent($detalle_receta)
      # filtrar por nombre sin importar mayusculas y minusculas
      ->filter(function ($query) use ($request) {
        $search = $request->input('search.value');
        if (!empty($search)) {
          $search = strtolower($search);
          $query->where(function ($q) use ($search) {
            $q->whereRaw('CAST(paciente.gafete AS TEXT) LIKE ?', ["%{$search}%"])
              ->orWhereRaw('LOWER(paciente.nombre) LIKE ?', ["%{$search}%"])
              ->orWhereRaw('LOWER(paciente.apellido_paterno) LIKE ?', ["%{$search}%"])
              ->orWhereRaw('LOWER(paciente.apellido_materno) LIKE ?', ["%{$search}%"]);
          });
        }
      })
      ->addColumn('acciones', function ($detalle_receta) {
        $detalle_receta_id_encriptado = Crypt::encryptString($detalle_receta->receta_id);

        $botones = '';

        // Enlace en la tabla
        $botones .= '<a href="' . route('receta-nueva', ['detalle_receta_id' => $detalle_receta_id_encriptado]) . '" class="btn btn-icon rounded-pill btn-info waves-effect waves-light m-1" title="Ver detalle de la receta">' .
          '<i class="mdi mdi-text-box-check-outline mdi-20px"></i>' .
          '</a>';

        return $botones;
      })
      ->rawColumns(['acciones'])
      ->make(true);
  }

  public function obtenerMedicamentosHispatec(Request $request)
  {
    $validado = $request->validate([
      'empresaid' => 'required|integer',
      'almacenid' => 'required|integer',
      'almacencodigo' => 'required|string',
      'busqueda' => 'sometimes|string'
    ]);

    try {
      $url_obtener_medicamento =  env('API_URL_KUDE') . '/obtenerCatalogoMedicamentosHispatec.php';

      $response = Http::timeout(30)->asForm()->post($url_obtener_medicamento, [
        'empresaid' => $validado['empresaid'],
        'almacenid' => $validado['almacenid'],
        'almacencodigo' => $validado['almacencodigo'],
        'busqueda' => $validado['busqueda'] ?? ''
      ]);

      # Limpia BOM
      $body = $response->body();
      $cleanBody = preg_replace('/^\xEF\xBB\xBF/', '', $body);
      $data = json_decode($cleanBody, true);

      # Si la API de Hispatec retorna error de validación entra
      if (isset($data['success']) && !$data['success'] && isset($data['error']) && strpos($data['error'], 'obligatorio') !== false) {
        return response()->json([
          'error' => true,
          'message' => 'Error de validación en Hispatec',
          'errores' => $data['errores'] ?? [],
          'hispatec_response' => $data
        ], 400);
      }

      # Si hay otro error de Hispatec
      if (isset($data['success']) && !$data['success']) {
        return response()->json([
          'error' => true,
          'message' => 'Error en Hispatec: ' . ($data['error'] ?? 'Error desconocido')
        ], 500);
      }

      return response()->json([
        'error' => false,
        'data' => $data['data'] ?? [],
        'count' => $data['count'] ?? 0,
        'parametros' => $data['parametros'] ?? []
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'error' => true,
        'message' => 'Excepción: ' . $e->getMessage()
      ], 500);
    }
  }

  public function recetaSurtir(Request $request)
  {
    if (Auth::user()->usuario_perfil_id == 7) {
      # Mandamos a la  vista
      return view('content.pages.receta.surtir-receta');
    } else {
      return view('content.pages.pages-misc-error');
    }
  }

  public function obtenerDetalleReceta(Request $request)
  {
    $result = array("error" => false, "msg" => null, 'receta' => null);

    $get = $request->all();

    # Datos generales de la receta
    $detalle_receta = RecetaModel::select(
      'receta.id as receta_id',
      'usuario.nombre as usuario_creador_nombre',
      'usuario.apellido_paterno as usuario_creador_apellido_p',
      'usuario.apellido_materno as usuario_creador_apellido_m',
      'usuario.cedula_profesional',
      'paciente.id as paciente_id',
      'paciente.nombre as paciente_nombre',
      'paciente.apellido_paterno as paciente_apellido_p',
      'paciente.apellido_materno as paciente_apellido_m',
      'paciente.edad as paciente_edad',
      'paciente.gafete as paciente_gafete',
      'paciente.curp as paciente_curp',
      'paciente.genero as paciente_genero',
      'receta.created_at as fecha_creacion',
      'receta_estatus.nombre as estatus',
      'receta_estatus.clase as estatus_clase',
      'receta_estatus.id as estatus_id'
    )
      ->join('usuario', 'usuario.id', '=', 'receta.usuario_id')
      ->join('paciente', 'paciente.id', '=', 'receta.paciente_id')
      ->join('receta_estatus', 'receta_estatus.id', '=', 'receta.receta_estatus_id')
      ->where('receta.id', $get['folioReceta'])
      ->where('usuario.borrado', 0)
      ->where('receta.borrado', 0)
      ->first();

    if (!$detalle_receta) {
      $result['error'] = true;
      $result["msg"] = 'No se encontraron detalles de la receta. Verifique que el folio sea válido. Si el problema persiste, contacte al equipo de desarrollo.';
      return response()->json($result);
    }

    $detalle_receta->toArray();

    # Medicamentos de la receta
    $medicamentos = RecetaMedicamentoModel::select(
      'medicamento_nombre',
      'abreviatura',
      'cantidad_solicitada'
    )
      ->where('receta_id', $get['folioReceta'])
      ->where('borrado', 0)
      ->get()->toArray();

    if (!$medicamentos) {
      $result['error'] = true;
      $result["msg"] = 'No se encontraron detalles de los medicamentos. Verifique que el folio sea válido. Si el problema persiste, contacte al equipo de desarrollo.';
      return response()->json($result);
    }

    $result['error'] = false;
    $result["msg"] = 'Receta encontrada correctamente.';
    $result["receta"]['detalle_receta'] = $detalle_receta;
    $result["receta"]['medicamentos'] = $medicamentos;

    return response()->json($result);
  }
  
  public function surtirRecetaCompleta(Request $request)
  {
    $result = ["error" => false, "msg" => null, "receta" => null];
    $get = $request->all();

    // Validar que se haya recibido el ID de la receta
    if (empty($get['recetaid'])) {
      return response()->json(['error' => true, 'msg' => 'No se recibió el identificador de la receta.']);
    }

    // Obtener la receta
    $receta = RecetaModel::where('id', $get['recetaid'])->first();

    if (!$receta) {
      return response()->json(['error' => true, 'msg' => 'No se encontró la receta solicitada.']);
    }

    $datosReceta = $receta->toArray();

    // Validar vigencia de 72 horas a partir de la emisión
    $fechaEmision = \Carbon\Carbon::parse($datosReceta['created_at']);
    $fechaVencimiento = $fechaEmision->copy()->addHours(72);
    $fechaActual = now();
    
    // Verificar si la receta ya venció
    if ($fechaActual->greaterThanOrEqualTo($fechaVencimiento)) {
      // Cambiar a vencida únicamente si continúa pendiente
      if ((int) $datosReceta['receta_estatus_id'] === 1) {
        RecetaModel::where('id', $get['recetaid'])->where('receta_estatus_id', 1)->update(['receta_estatus_id' => 3]);
      }

      $result['error'] = true;
      $result['msg'] = 'La vigencia de la receta ha expirado. Fue expedida el ' . $fechaEmision->format('d/m/Y H:i') . ' y su vigencia terminó el ' . $fechaVencimiento->format('d/m/Y H:i') . '. No es posible surtirla.';
      return response()->json($result);
    }

    // Validar el estado de la receta
    switch ((int) $datosReceta['receta_estatus_id']) {
      // Receta pendiente de surtir
      case 1:
        DB::connection('pgsql')->beginTransaction();
        try {
          $datosHistoricoVale = RecetaValeHistoricoModel::where('receta_id', $get['recetaid'])->first();

          if (!$datosHistoricoVale) {
            throw new \Exception('No se encontró el histórico del vale asociado a la receta.');
          }

          $datosHistoricoVale = $datosHistoricoVale->toArray();
          $valeId = $datosHistoricoVale['vale_id'];
          $centroCostos = $datosHistoricoVale['centro_costos'];
          $datosPaciente = $this->obtenerDatosPaciente($datosReceta['paciente_id']);

          $jsonConsumo = $this->crearJsonConsumo($valeId, $datosPaciente, $centroCostos);

          $token = Helpers::obtenerToken();

          if (!$token) {
            DB::connection('pgsql')->rollBack();
            return response()->json(['error' => true, 'msg' => 'No se pudo obtener token.'], 500);
          }

          $respuestaConsumo = $this->postAPI(env('GENERA_CONSUMO_HISPATEC'), $jsonConsumo, $token);

          if ($respuestaConsumo['error']) {
            DB::connection('pgsql')->rollBack();
            return response()->json($respuestaConsumo);
          }

          // Guardar histórico del consumo
          $this->historicoConsumoId = RecetaConsumoHistoricoModel::insertGetId([
            'json_data' => json_encode($jsonConsumo),
            'respuesta_api' => json_encode($respuestaConsumo['data']),
            'created_at' => now(),
            'receta_id' => $get['recetaid'],
            'centro_costos' => $centroCostos,
            'vale_id' => $valeId
          ]);

          $result['error'] = false;
          $result['msg'] = 'La receta fue surtida correctamente y el consumo de los medicamentos se registró con éxito.';

          DB::connection('pgsql')->commit();
        } catch (\Exception $e) {
          DB::connection('pgsql')->rollBack();
          return response()->json(['error' => true,'msg' => 'No fue posible surtir la receta en el sistema. Inténtalo de nuevo y, si el problema persiste, contacta con el equipo de desarrollo.','return' => $e->getMessage()], 500);
        }
        break;

      // Receta ya surtida
      case 2:
        $result['error'] = true;
        $result['msg'] = 'La receta ya fue surtida anteriormente. No es posible volver a surtirla.';
        break;

      // Receta vencida
      case 3:
        $result['error'] = true;
        $result['msg'] = 'La vigencia de la receta ha expirado. No es posible surtirla.';
        break;

      // Receta cancelada
      case 4:
        $result['error'] = true;
        $result['msg'] = 'La receta se encuentra cancelada. No es posible surtirla.';
        break;

      default:
        $result['error'] = true;
        $result['msg'] = 'La receta tiene un estatus desconocido. No es posible surtirla.';
        break;
    }
    return response()->json($result);
  }


  public function guardarFirma(Request $request)
  {
    $result = ['error' => false, 'msg' => null];

    $get = $request->all();

    try {
      if (empty($get['firma'])) {
        return response()->json(['error' => true, 'msg' => 'No se recibió la firma del paciente.']);
      }

      if (empty($get['recetaid'])) {
        return response()->json(['error' => true, 'msg' => 'No se recibió la receta.']);
      }

      DB::connection('pgsql')->beginTransaction();

      // Obtener la receta
      $receta = RecetaModel::find($get['recetaid']);

      if (!$receta) {
        DB::connection('pgsql')->rollBack();
        return response()->json(['error' => true, 'msg' => 'La receta no existe.']);
      }

      // Limpiar Base64
      $firma = str_replace('data:image/png;base64,', '', $get['firma']);
      $firma = str_replace(' ', '+', $firma);

      // Crear carpeta si no existe
      $directorio = public_path('firmas_pacientes');

      if (!file_exists($directorio)) {
        mkdir($directorio, 0755, true);
      }

      // Nombre del archivo
      $nombreArchivo = 'receta_' . $receta->id . '_' . time() . '.png';

      // Guardar archivo
      $guardado = file_put_contents($directorio . '/' . $nombreArchivo, base64_decode($firma));

      if ($guardado === false) {
        DB::connection('pgsql')->rollBack();
        return response()->json(['error' => true, 'msg' => 'No fue posible guardar la firma del paciente.']);
      }

      $ruta = 'firmas_pacientes/' . $nombreArchivo;

      // Guardar registro
      $firmaPacienteId = RecetaFirmaPacienteModel::insertGetId([
        'receta_id'  => $receta->id,
        'paciente_id' => $receta->paciente_id,
        'usuario_id' => Auth::user()->id,
        'firma'      => $ruta,
        'created_at' => now()
      ]);

      if (!$firmaPacienteId) {
        DB::connection('pgsql')->rollBack();
        return response()->json([
          'error' => true,
          'msg' => 'Ocurrió un error al intentar guardar la firma del paciente.'
        ]);
      }

      // Actualizar estatus de la receta
      $updateEstatus = RecetaModel::where('id', $receta->id)->update(['surtida' => 1, 'receta_estatus_id' => 2, 'updated_at' => now()]);

      if (!$updateEstatus) {
        DB::connection('pgsql')->rollBack();
        return response()->json(['error' => true, 'msg' => 'Ocurrió un error al intentar actualizar el estatus de la receta.']);
      }

      DB::connection('pgsql')->commit();
      $result['error'] = false;
      $result['msg'] = 'La firma del paciente se guardó correctamente y la receta fue surtida.';

      return response()->json($result);
    } catch (\Exception $e) {
      DB::connection('pgsql')->rollBack();
      return response()->json(['error' => true, 'msg' => 'Ocurrió un error al guardar la firma del paciente.', 'detalle' => $e->getMessage()]);
    }
  }
}
