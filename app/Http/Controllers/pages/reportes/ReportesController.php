<?php

namespace App\Http\Controllers\pages\reportes;

use App\Http\Controllers\Controller;
use App\Models\PacienteModel;
use App\Models\UsuarioModel;
use App\Models\PacienteEmpresaModel;
use App\Models\PacienteAreaModel;
use App\Models\PacienteSubareaModel;
use App\Models\PacienteUnidadNegocioModel;
use App\Models\PacienteTipoVisitaModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportesController extends Controller
{
  public function recetas(Request $request)
  {
    if(Auth::user()->usuario_perfil_id == 1 || Auth::user()->usuario_perfil_id == 2 || Auth::user()->usuario_perfil_id == 3 || Auth::user()->usuario_perfil_id == 4 || Auth::user()->usuario_perfil_id == 5 || Auth::user()->usuario_perfil_id == 7){

      $view_data = [
        'catalogos' => [
          'pacientes' => PacienteModel::whereHas('pacienteConReceta')->where('borrado', 0)->get()->toArray(),
          'medico_tratante' => UsuarioModel::whereHas('medicoTratanteReceta')->get()->toArray(),
        ]
      ];
      return view('content.pages.reportes.recetas', ['datos_vista' => $view_data]);

    } else {
      return view('content.pages.pages-misc-error');
    }
  }

  public function pacientes(Request $request)
  {
    if(Auth::user()->usuario_perfil_id == 1 || Auth::user()->usuario_perfil_id == 2 || Auth::user()->usuario_perfil_id == 3 || Auth::user()->usuario_perfil_id == 4 || Auth::user()->usuario_perfil_id == 5){

      $view_data = [
        'catalogos' => [
          'pacientes' => PacienteModel::select('paciente.id', 'paciente.nombre', 'paciente.apellido_paterno', 'paciente.apellido_materno')->where('paciente.borrado', 0)->join('paciente_datos_consulta', 'paciente.id', '=', 'paciente_datos_consulta.paciente_id')->where('paciente_datos_consulta.borrado', 0)->distinct('paciente.id')->get()->toArray(),
          'empresa' => PacienteEmpresaModel::where('borrado', 0)->get()->toArray(),
          'area' => PacienteAreaModel::where('borrado', 0)->get()->toArray(),
          'subarea' => PacienteSubareaModel::where('borrado', 0)->get()->toArray(),
          'unidad_negocio' => PacienteUnidadNegocioModel::where('borrado', 0)->get()->toArray(),
        ]
      ];
      return view('content.pages.reportes.pacientes', ['datos_vista' => $view_data]);

    } else {
      return view('content.pages.pages-misc-error');
    }
  }

  public function consultas(Request $request)
  {
    if(Auth::user()->usuario_perfil_id == 1 || Auth::user()->usuario_perfil_id == 2 || Auth::user()->usuario_perfil_id == 3 || Auth::user()->usuario_perfil_id == 4 || Auth::user()->usuario_perfil_id == 5){

      $view_data = [
        'catalogos' => [
          'pacientes' => PacienteModel::select('paciente.id', 'paciente.nombre', 'paciente.apellido_paterno', 'paciente.apellido_materno')->where('paciente.borrado', 0)->join('paciente_datos_consulta', 'paciente.id', '=', 'paciente_datos_consulta.paciente_id')->where('paciente_datos_consulta.borrado', 0)->distinct('paciente.id')->get()->toArray(),
          'empresa' => PacienteEmpresaModel::where('borrado', 0)->get()->toArray(),
          'area' => PacienteAreaModel::where('borrado', 0)->get()->toArray(),
          'subarea' => PacienteSubareaModel::where('borrado', 0)->get()->toArray(),
          'unidad_negocio' => PacienteUnidadNegocioModel::where('borrado', 0)->get()->toArray(),
          'tipo_visita' => PacienteTipoVisitaModel::where('borrado', 0)->get()->toArray(),
        ]
      ];
      return view('content.pages.reportes.consultas', ['datos_vista' => $view_data]);

    } else {
      return view('content.pages.pages-misc-error');
    }
  }

  public function estadisticas(Request $request)
  {
    if(Auth::user()->usuario_perfil_id == 1 || Auth::user()->usuario_perfil_id == 2 || Auth::user()->usuario_perfil_id == 3 || Auth::user()->usuario_perfil_id == 4 || Auth::user()->usuario_perfil_id == 5){
      $view_data = [];
      return view('content.pages.reportes.estadisticas', ['datos_vista' => $view_data]);
    } else {
      return view('content.pages.pages-misc-error');
    }
  }
}
