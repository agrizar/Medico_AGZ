<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PacienteModel;
use App\Models\PacienteDatosConsultaModel;
use App\Models\PacienteTipoVisitaModel;
use App\Models\UsuarioModel;
use App\Models\RecetaEstatusModel;
use App\Models\RecetaModel;
use App\Models\UsuarioFirmaModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InicioController extends Controller
{
  public function index()
  {

    # Funcion para checar las recetas caducadas
    $this->caducarRecetas();

    # Si tiene el perfil de farmacia se manda a la vista del gestor de farmacia
    if (Auth::user()->usuario_perfil_id == 7) {
      $view_data['recetas']['vigentes'] = RecetaModel::where('borrado', 0)->where('receta_estatus_id', 1)->count();
      $view_data['recetas']['surtidas'] = RecetaModel::where('borrado', 0)->where('receta_estatus_id', 2)->count();
      $view_data['recetas']['caducadas'] = RecetaModel::where('borrado', 0)->where('receta_estatus_id', 3)->count();
      $view_data['recetas']['canceladas'] = RecetaModel::where('borrado', 0)->where('receta_estatus_id', 4)->count();

      $view_data['catalogos']['estatusReceta'] = RecetaEstatusModel::where('borrado', 0)->get()->toArray(); # Estatus de las recetas
      return view('content.pages.inicio_farmacia', ['datos_vista' => $view_data]);
    }

    if (Auth::user()->cedula_profesional) {
      $firma = UsuarioFirmaModel::where('usuario_id', Auth::user()->id)->where('borrado', 0)->first();
      if (!$firma) {
        return redirect()->route('usuario-firma');
      }
    }

    $view_data['estadisticas']['pacientesHombres'] = PacienteModel::where('genero', 'M')->where('paciente.borrado', 0)->join('paciente_datos_consulta', 'paciente.id', '=', 'paciente_datos_consulta.paciente_id')->where('paciente_datos_consulta.borrado', 0)->distinct('paciente.id')->count('paciente.id'); # Total de hombres atendidos
    $view_data['estadisticas']['pacientesMujeres'] = PacienteModel::where('genero', 'F')->where('paciente.borrado', 0)->join('paciente_datos_consulta', 'paciente.id', '=', 'paciente_datos_consulta.paciente_id')->where('paciente_datos_consulta.borrado', 0)->distinct('paciente.id')->count('paciente.id'); # Total de mujeres atendidas
    $view_data['estadisticas']['enfermedadGeneral'] = PacienteDatosConsultaModel::where('paciente_tipo_visita_id', 1)->where('borrado', 0)->count(); # Total de pacientes que acuden por enfermedad general
    $view_data['estadisticas']['riesgoTrabajo'] = PacienteDatosConsultaModel::where('paciente_tipo_visita_id', 2)->where('borrado', 0)->count(); # Total de pacientes que acuden por riesgo de trabajo
    $view_data['catalogos']['tipo_visita'] = PacienteTipoVisitaModel::where('borrado', 0)->get()->toArray(); # Tipos de la visita al servicio medico

    # Consulta para mostrar las consultas de los últimos 8 días
    $fechaInicio = now()->subDays(7)->format('Y-m-d');
    $fechaFin = now()->format('Y-m-d');
    $consultasPorDia = PacienteDatosConsultaModel::select(
      DB::raw("DATE(created_at) as fecha_consulta"),
      DB::raw("COUNT(*) as total")
    )
      ->where('borrado', 0)
      ->whereBetween(DB::raw("DATE(created_at)"), [$fechaInicio, $fechaFin])
      ->groupBy(DB::raw("DATE(created_at)"))
      ->get()
      ->keyBy('fecha_consulta');

    # Genera serie de días
    $dias = collect();
    for ($i = 7; $i >= 0; $i--) {
      $fecha = now()->subDays($i);
      $diaSemana = $fecha->locale('es')->isoFormat('ddd');
      $numeroDia = $fecha->day;
      $esHoy = $i === 0;
      $formatoDia = $diaSemana . ' ' . $numeroDia . ($esHoy ? ' (Hoy)' : '');
      $dias->push([
        'dia' => $formatoDia,
        'cantidad_consultas' => $consultasPorDia[$fecha->format('Y-m-d')]->total ?? 0
      ]);
    }

    $view_data['estadisticas']['consultasPorDia'] = $dias;

    return view('content.pages.inicio', ['datos_vista' => $view_data]);
  }

  public function login()
  {
    return view('content.authentications.login');
  }

  public function autenticar(Request $request)
  {
    $request->validate([
      'correo' => ['required', 'string'],
      'password' => ['required'],
    ]);

    $correoCompleto = $request->input('correo') . env('DOMINIO');

    $ldapAuth = $this->validarAD($correoCompleto, $request->input('password'));

    if (!$ldapAuth) {
      return back()->withErrors([
        'correo' => 'El usuario no tiene acceso al sistema (AD).',
      ])->withInput();
    }

    $usuario = UsuarioModel::where('correo', $request->input('correo'))->where('borrado', 0)->first();

    if (!$usuario) {
      return back()->withErrors([
        'correo' => 'El usuario no tiene acceso al sistema (SM).',
      ])->withInput();
    }

    Auth::login($usuario);
    $request->session()->regenerate();

    return redirect()->intended('/');
  }

  function validarAD($usuario, $password)
  {
    # Constantes
    $dominio = env('AD_DOMAIN', 'agrizar.com');
    $timeout = env('AD_TIMEOUT', 5);
    $version = env('AD_LDAP_VERSION', 3);
    $port = env('AD_PORT', 389);
    $dcs = explode(',', env('AD_CONTROLLERS', ''));

    if (empty($dcs) || empty($dcs[0])) {
      return false;
    }

    # Extraer usuario del correo
    if (strpos($usuario, '@') !== false) {
      $parts = explode('@', $usuario);
      $user = $parts[0];
      $domain = $parts[1];
    } else {
      $user = $usuario;
      $domain = $dominio;
    }

    # Intentar con cada DC para la conexion
    foreach ($dcs as $dc) {
      $dc = trim($dc);
      if (empty($dc)) continue;

      # Conectar con ldap
      $ldap = @ldap_connect($dc, $port);
      if (!$ldap) continue;

      # Configuramos opciones
      ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, $version);
      ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
      ldap_set_option($ldap, LDAP_OPT_NETWORK_TIMEOUT, $timeout);

      # Autenticar con el usuario con dominio
      $userDn = $user . '@' . $domain;

      if (@ldap_bind($ldap, $userDn, $password)) {
        ldap_close($ldap);
        return true;
      }
      ldap_close($ldap);
    }
    return false;
  }

  private function caducarRecetas()
  {
    $fechaLimite = now()->subHours(72);

    RecetaModel::where('receta_estatus_id', 1)->where('created_at', '<=', $fechaLimite)
      ->update([
        'receta_estatus_id' => 3,
        'updated_at' => now()
    ]);
  }
}
