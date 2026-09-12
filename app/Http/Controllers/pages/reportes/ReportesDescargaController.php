<?php

namespace App\Http\Controllers\pages\reportes;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
# uses para generar excel
use App\Exports\DynamicExportQuery;
use Maatwebsite\Excel\Facades\Excel;

class ReportesDescargaController extends Controller
{
  # Funcion auxiliar para determinar si se requieren ciertos JOINs
  private function requires($cols, $targetCols){ return count(array_intersect($cols, $targetCols)) > 0; }

  public function exportarRecetas(Request $request)
  {
    ini_set('max_execution_time', 1200);
    ini_set('memory_limit', '2048M');

    $columnas = $request->columnas ?? [];
    $columnas = array_unique(array_merge(['folio_receta'], $columnas));

    $mapa = [
      'folio_receta' => "CONCAT('#', r.id) AS folio_receta",
      'fecha_creacion' => "r.created_at AS fecha_creacion",
      'paciente_gafete' => "p.gafete AS paciente_gafete",
      'paciente_nombre' => "p.nombre AS paciente_nombre",
      'paciente_apellido_paterno' => "p.apellido_paterno AS paciente_apellido_paterno",
      'paciente_apellido_materno' => "p.apellido_materno AS paciente_apellido_materno",
      'paciente_genero' => "CASE WHEN p.genero='M' THEN 'Masculino' WHEN p.genero='F' THEN 'Femenino' END AS paciente_genero",
      'paciente_curp' => "p.curp AS paciente_curp",
      'medico_tratante_nombre' => "u.nombre AS medico_tratante_nombre",
      'medico_tratante_apellido_paterno' => "u.apellido_paterno AS medico_tratante_apellido_paterno",
      'medico_tratante_apellido_materno' => "u.apellido_materno AS medico_tratante_apellido_materno",
      'medico_tratante_correo' => "CONCAT(u.correo,'@agrizar.com') AS medico_tratante_correo",
      'indicaciones_medicamento' => "r.medicamento_indicaciones AS indicaciones_medicamento",
      'recomendaciones_receta' => "r.recomendaciones AS recomendaciones_receta",
      'medicamento_recetado' => "rm.medicamento_nombre AS medicamento_recetado",
      'medicamento_codigo' => "rm.medicamento_codigo AS medicamento_codigo",
      'medicamento_cantidad_solicitada' => "rm.cantidad_solicitada AS medicamento_cantidad_solicitada",
      'centro_costos_medicamento' => "rch.centro_costos AS centro_costos_medicamento",
      'vale_medicamento' => "rch.vale_id AS vale_medicamento",
    ];

    $select = [];
    foreach ($columnas as $col) {
      if (isset($mapa[$col])) $select[] = DB::raw($mapa[$col]);
    }

    $query = DB::table('receta AS r')->where('r.borrado', 0)->orderBy('r.id', 'ASC');

    # JOINS dinámicos
    if ($this->requires($columnas, ['paciente_gafete','paciente_nombre','paciente_apellido_paterno','paciente_apellido_materno','paciente_genero','paciente_curp'])) {
      $query->join('paciente AS p', 'p.id', '=', 'r.paciente_id')->where('p.borrado', 0);
    }
    if ($this->requires($columnas, ['medico_tratante_nombre','medico_tratante_apellido_paterno','medico_tratante_apellido_materno','medico_tratante_correo'])) {
      $query->join('usuario AS u', 'u.id', '=', 'r.usuario_id')->where('u.borrado', 0);
    }
    if ($this->requires($columnas, ['medicamento_recetado','medicamento_codigo','medicamento_cantidad_solicitada'])) {
      $query->leftJoin('receta_medicamento AS rm', 'rm.receta_id', '=', 'r.id')->where('rm.borrado', 0);
    }
    if ($this->requires($columnas, ['centro_costos_medicamento','vale_medicamento'])) {
      $query->join('receta_consumo_historico AS rch', 'rch.receta_id', '=', 'r.id')->where('rch.borrado', 0);
    }

    # Filtros
    $f = $request->filtros ?? [];
    if (!empty($f['fecha_inicio'])) $query->whereDate('r.created_at', '>=', $f['fecha_inicio']);
    if (!empty($f['fecha_fin'])) $query->whereDate('r.created_at', '<=', $f['fecha_fin']);
    if (!empty($f['paciente'])) $query->where('r.paciente_id', $f['paciente']);
    if (!empty($f['medico'])) $query->where('r.usuario_id', $f['medico']);

    $query->select($select)->orderBy('r.id', 'ASC');
    
    return Excel::download(new DynamicExportQuery($query, $columnas),"Reporte_Recetas_" . now()->format('Ymd_His') . ".xlsx");
  }

  public function exportarPacientes(Request $request)
  {
    ini_set('max_execution_time', 1200);
    ini_set('memory_limit', '2048M');

    $columnas = $request->columnas ?? [];
    $columnas = array_unique(array_merge(['fecha_registro_en_sistema'], $columnas));

    $mapa = [
        'fecha_registro_en_sistema' => "p.created_at AS fecha_registro_en_sistema",
        'paciente_gafete' => "CONCAT('#', p.gafete) AS paciente_gafete",
        'paciente_nombre' => "p.nombre",
        'paciente_apellido_paterno' => "p.apellido_paterno",
        'paciente_apellido_materno' => "p.apellido_materno",
        'paciente_genero' => "CASE WHEN p.genero='M' THEN 'Masculino' WHEN p.genero='F' THEN 'Femenino' END AS paciente_genero",
        'paciente_edad' => "p.edad",
        'paciente_telefono' => "p.celular",
        'paciente_curp' => "p.curp",
        'paciente_empresa' => "pe.nombre AS paciente_empresa",
        'paciente_unidad_negocio' => "pun.nombre AS paciente_unidad_negocio",
        'paciente_area' => "pa.nombre AS paciente_area",
        'paciente_subarea' => "psa.nombre AS paciente_subarea",
    ];

    $select = [];
    foreach ($columnas as $col) {
        if (isset($mapa[$col])) $select[] = DB::raw($mapa[$col]);
    }

    $query = DB::table('paciente AS p')->where('p.borrado', 0)->join('paciente_datos_consulta as pdc', 'p.id', '=', 'pdc.paciente_id')->where('pdc.borrado', 0)->distinct('p.id')->orderBy('p.id');

    # JOINS DINÁMICOS
    if ($this->requires($columnas, ['paciente_empresa','paciente_unidad_negocio','paciente_area','paciente_subarea'])) {
      $query->join('paciente_empresa AS pe', 'pe.id', '=', 'p.paciente_empresa_id')->where('pe.borrado', 0);
      $query->join('paciente_unidad_negocio AS pun', 'pun.id', '=', 'p.paciente_unidad_negocio_id')->where('pun.borrado', 0);
      $query->join('paciente_area AS pa', 'pa.id', '=', 'p.paciente_area_id')->where('pa.borrado', 0);
      $query->join('paciente_subarea AS psa', 'psa.id', '=', 'p.paciente_subarea_id')->where('psa.borrado', 0);
    }

    # Filtros
    $f = $request->filtros ?? [];

    if (!empty($f['fecha_inicio'])) $query->whereDate('p.created_at', '>=', $f['fecha_inicio']);
    if (!empty($f['fecha_fin'])) $query->whereDate('p.created_at', '<=', $f['fecha_fin']);
    if (!empty($f['paciente'])) $query->where('p.id', $f['paciente']);
    if (!empty($f['edad'])) $query->where('p.edad', $f['edad']);
    if (!empty($f['genero'])) $query->where('p.genero', $f['genero']);
    if (!empty($f['empresa_id'])) $query->where('p.paciente_empresa_id', $f['empresa_id']);
    if (!empty($f['unidad_negocio'])) $query->where('p.paciente_unidad_negocio_id', $f['unidad_negocio']);
    if (!empty($f['area'])) $query->where('p.paciente_area_id', $f['area']);
    if (!empty($f['subarea'])) $query->where('p.paciente_subarea_id', $f['subarea']);

    $query->select($select)->orderBy('p.nombre','ASC');
    return Excel::download(new DynamicExportQuery($query, $columnas),"Reporte_Pacientes_" . now()->format('Ymd_His') . ".xlsx");
  }

  public function exportarConsultas(Request $request)
  {
    ini_set('max_execution_time', 1200);
    ini_set('memory_limit', '2048M');

    $columnas = $request->columnas ?? [];
    $columnas = array_unique(array_merge(['fecha_consulta'], $columnas));

    $mapa = [
      'fecha_consulta' => "pdc.created_at AS fecha_consulta",
      'paciente_gafete' => "CONCAT('#', p.gafete) AS paciente_gafete",
      'paciente_nombre' => "p.nombre",
      'paciente_apellido_paterno' => "p.apellido_paterno",
      'paciente_apellido_materno' => "p.apellido_materno",
      'paciente_genero' => "p.genero",
      'paciente_edad' => "p.edad",
      'paciente_telefono' => "p.celular",
      'paciente_curp' => "p.curp",
      'paciente_empresa' => "pe.nombre AS paciente_empresa",
      'paciente_unidad_negocio' => "pun.nombre AS paciente_unidad_negocio",
      'paciente_area' => "pa.nombre AS paciente_area",
      'paciente_subarea' => "psa.nombre AS paciente_subarea",
      'motivo_consulta' => "pdc.motivo_consulta",
      'cie_nombre' => "pdc.cie_descripcion",
      'temperatura' => "pdc.temperatura",
      'peso' => "pdc.peso",
      'altura' => "pdc.altura",
      'imc' => "pdc.imc",
      'frecuencia_cardiaca' => "pdc.frecuencia_cardiaca",
      'saturacion_oxigeno' => "pdc.saturacion_oxigeno",
      'presion_arterial' => "pdc.presion_arterial",
      'observaciones' => "pdc.observaciones",
      'tipo_visita' => "ptv.nombre AS tipo_visita",
    ];

    $select = [];
    foreach ($columnas as $col) {
      if (isset($mapa[$col])) {
        $select[] = DB::raw($mapa[$col]);
      }
    }

    $query = DB::table('paciente_datos_consulta AS pdc')->where('pdc.borrado', 0)->orderBy('pdc.paciente_id','DESC');

    # Joins dinámicos
    if ($this->requires($columnas, ['paciente_gafete','paciente_nombre','paciente_apellido_paterno','paciente_apellido_materno','paciente_genero','paciente_edad','paciente_telefono','paciente_curp','paciente_empresa','paciente_unidad_negocio','paciente_area','paciente_subarea'])) {
      $query->join('paciente AS p', 'p.id', '=', 'pdc.paciente_id')->where('p.borrado', 0);
    }

    if (in_array('paciente_empresa', $columnas)) { $query->join('paciente_empresa AS pe', 'pe.id', '=', 'p.paciente_empresa_id'); }
    if (in_array('paciente_unidad_negocio', $columnas)) { $query->join('paciente_unidad_negocio AS pun', 'pun.id', '=', 'p.paciente_unidad_negocio_id'); }
    if (in_array('paciente_area', $columnas)) { $query->join('paciente_area AS pa', 'pa.id', '=', 'p.paciente_area_id'); }
    if (in_array('paciente_subarea', $columnas)) { $query->join('paciente_subarea AS psa', 'psa.id', '=', 'p.paciente_subarea_id'); }
    if (in_array('tipo_visita', $columnas)) { $query->join('paciente_tipo_visita AS ptv', 'ptv.id', '=', 'pdc.paciente_tipo_visita_id'); }

    # Filtros
    $f = $request->filtros ?? [];
    if (!empty($f['fecha_inicio'])) $query->whereDate('pdc.created_at', '>=', $f['fecha_inicio']);
    if (!empty($f['fecha_fin'])) $query->whereDate('pdc.created_at', '<=', $f['fecha_fin']);
    if (!empty($f['paciente'])) $query->where('p.id', $f['paciente']);
    if (!empty($f['edad'])) $query->where('p.edad', $f['edad']);
    if (!empty($f['genero'])) $query->where('p.genero', $f['genero']);
    if (!empty($f['empresa_id'])) $query->where('p.paciente_empresa_id', $f['empresa_id']);
    if (!empty($f['unidad_negocio'])) $query->where('p.paciente_unidad_negocio_id', $f['unidad_negocio']);
    if (!empty($f['area'])) $query->where('p.paciente_area_id', $f['area']);
    if (!empty($f['subarea'])) $query->where('p.paciente_subarea_id', $f['subarea']);
    if (!empty($f['tipo_visita'])) $query->where('pdc.paciente_tipo_visita_id', $f['tipo_visita']);

    $query->select($select)->orderBy('pdc.created_at','DESC');
    return Excel::download(new DynamicExportQuery($query, $columnas),"Reporte_Consultas_" . now()->format('Ymd_His') . ".xlsx");
  }

  public function obtenerConsultasPorColaborador(Request $request)
  {
    $request->validate([
        'fecha_inicio' => 'required|date',
        'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
    ]);

    $fechaInicio = $request->fecha_inicio;
    $fechaFin = $request->fecha_fin;

    $datos = DB::table('paciente_datos_consulta as pdc')
      ->join('paciente as p', function ($join) {
        $join->on('p.id', '=', 'pdc.paciente_id')->where('p.borrado', 0);
      })
      ->where('pdc.borrado', 0)
      ->whereBetween('pdc.created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
      ->select(
        'p.gafete as colaborador_clave',
        DB::raw("CONCAT(p.nombre, ' ', p.apellido_paterno, ' ', p.apellido_materno) AS colaborador_nombre"),
        DB::raw('EXTRACT(WEEK FROM pdc.created_at) AS numero_semana'),
        DB::raw('EXTRACT(YEAR FROM pdc.created_at) AS anio'),
        DB::raw('COUNT(pdc.id) AS numero_consultas')
      )
      ->groupBy(
        'p.gafete',
        'p.nombre',
        'p.apellido_paterno',
        'p.apellido_materno',
        DB::raw('EXTRACT(WEEK FROM pdc.created_at)'),
        DB::raw('EXTRACT(YEAR FROM pdc.created_at)')
      )
      ->orderBy('anio')
      ->orderBy('numero_semana')
      ->get();

    return response()->json([
      'error' => false,
      'total_colaboradores' => $datos->unique('colaborador_clave')->count(),
      'total_consultas' => $datos->sum('numero_consultas'),
      'datos' => $datos
    ]);
  }

  public function exportarConsultasPorColaborador(Request $request)
  {
    ini_set('max_execution_time', 1200);
    ini_set('memory_limit', '2048M');

    $fechaInicio = $request->fecha_inicio;
    $fechaFin = $request->fecha_fin;

    if (!$fechaInicio || !$fechaFin) {
      return response()->json(['error' => true, 'mensaje' => 'Debes seleccionar un rango de fechas.'], 422);
    }

    $query = DB::table('paciente_datos_consulta as pdc')
    ->join('paciente as p', function ($join) {
      $join->on('p.id', '=', 'pdc.paciente_id')->where('p.borrado', 0);
    })
    ->where('pdc.borrado', 0)
    ->whereBetween('pdc.created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
    ->select(
      'p.gafete as colaborador_clave',
      DB::raw("CONCAT( p.nombre, ' ', p.apellido_paterno, ' ', p.apellido_materno ) AS colaborador_nombre"),
      DB::raw("EXTRACT(WEEK FROM pdc.created_at) AS numero_semana"),
      DB::raw("COUNT(pdc.id) AS numero_consultas"),
      DB::raw("EXTRACT(YEAR FROM pdc.created_at) AS anio")
    )
    ->groupBy(
      'p.gafete',
      'p.nombre',
      'p.apellido_paterno',
      'p.apellido_materno',
      DB::raw('EXTRACT(WEEK FROM pdc.created_at)'),
      DB::raw('EXTRACT(YEAR FROM pdc.created_at)')
    )
    ->orderBy('anio')
    ->orderBy('numero_semana')
    ->orderBy('numero_consultas', 'DESC');

    $columnas = [
      'colaborador_clave',
      'colaborador_nombre',
      'numero_semana',
      'numero_consultas',
      'anio'
    ];

    return Excel::download(new DynamicExportQuery($query, $columnas), 'Reporte_Consultas_Semanales_Colaborador_' . now()->format('Ymd_His') . '.xlsx');
  }

  public function obtenerConsultasTotalesPorColaborador(Request $request)
  {
    $request->validate([
        'fecha_inicio' => 'required|date',
        'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
    ]);

    $fechaInicio = $request->fecha_inicio;
    $fechaFin = $request->fecha_fin;

    $datos = DB::table('paciente_datos_consulta as pdc')
      ->join('paciente as p', function ($join) {
        $join->on('p.id', '=', 'pdc.paciente_id')->where('p.borrado', 0);
      })
      ->where('pdc.borrado', 0)
      ->whereBetween('pdc.created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
      ->select(
        'p.gafete as colaborador_clave',
        DB::raw("CONCAT(p.nombre, ' ', p.apellido_paterno, ' ', p.apellido_materno) AS colaborador_nombre"),
        DB::raw('COUNT(pdc.id) AS numero_consultas')
      )
      ->groupBy(
        'p.gafete',
        'p.nombre',
        'p.apellido_paterno',
        'p.apellido_materno'
      )
      ->orderBy('numero_consultas', 'DESC')
      ->get();

    return response()->json([
      'error' => false,
      'total_colaboradores' => $datos->unique('colaborador_clave')->count(),
      'total_consultas' => $datos->sum('numero_consultas'),
      'datos' => $datos
    ]);
  }

  public function exportarConsultasTotalesPorColaborador(Request $request)
  {
    ini_set('max_execution_time', 1200);
    ini_set('memory_limit', '2048M');

    $fechaInicio = $request->fecha_inicio;
    $fechaFin = $request->fecha_fin;

    if (!$fechaInicio || !$fechaFin) {
      return response()->json(['error' => true, 'mensaje' => 'Debes seleccionar un rango de fechas.'], 422);
    }

    $query = DB::table('paciente_datos_consulta as pdc')
    ->join('paciente as p', function ($join) {
      $join->on('p.id', '=', 'pdc.paciente_id')->where('p.borrado', 0);
    })
    ->where('pdc.borrado', 0)
    ->whereBetween('pdc.created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
    ->select(
      'p.gafete as colaborador_clave',
      DB::raw("CONCAT( p.nombre, ' ', p.apellido_paterno, ' ', p.apellido_materno ) AS colaborador_nombre"),
      DB::raw("COUNT(pdc.id) AS numero_consultas")
    )
    ->groupBy(
      'p.gafete',
      'p.nombre',
      'p.apellido_paterno',
      'p.apellido_materno',
    )
    ->orderBy('numero_consultas', 'DESC');

    $columnas = [
      'colaborador_clave',
      'colaborador_nombre',
      'numero_consultas',
    ];

    return Excel::download(new DynamicExportQuery($query, $columnas), 'Reporte_Consultas_Totales_Colaborador_' . now()->format('Ymd_His') . '.xlsx');
  }

  public function obtenerMediaMedianaModa(Request $request)
  {
    $request->validate([
        'fecha_inicio' => 'required|date',
        'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
    ]);

    $fechaInicio = $request->fecha_inicio . ' 00:00:00';
    $fechaFin = $request->fecha_fin . ' 23:59:59';

    $datos = DB::select("
      WITH consultas_semana AS (
        SELECT
          p.gafete AS colaborador_clave,
          CONCAT(p.nombre, ' ', p.apellido_paterno, ' ', p.apellido_materno) AS colaborador_nombre,
          DATE_TRUNC('week', pdc.created_at) AS semana,
          COUNT(pdc.id) AS consultas_semana
        FROM paciente_datos_consulta pdc
        JOIN paciente p ON p.id = pdc.paciente_id AND p.borrado = 0
        WHERE pdc.borrado = 0 AND pdc.created_at BETWEEN ? AND ?
        GROUP BY
          p.gafete,
          p.nombre,
          p.apellido_paterno,
          p.apellido_materno,
          DATE_TRUNC('week', pdc.created_at)
      )
      SELECT
        colaborador_clave,
        colaborador_nombre,
        ROUND(AVG(consultas_semana), 2) AS media,
        PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY consultas_semana) AS mediana,
        MODE() WITHIN GROUP (ORDER BY consultas_semana) AS moda,
        SUM(consultas_semana) AS total_consultas
      FROM consultas_semana
      GROUP BY
        colaborador_clave,
        colaborador_nombre
      ORDER BY total_consultas DESC
    ", [
      $fechaInicio,
      $fechaFin
    ]);

    return response()->json([
      'error' => false,
      'total_colaboradores' => count($datos),
      'total_consultas' => collect($datos)->sum('total_consultas'),
      'datos' => $datos
    ]);
  }

  public function exportarMediaMedianaModa(Request $request)
  {
    ini_set('max_execution_time', 1200);
    ini_set('memory_limit', '2048M');

    $fechaInicio = $request->fecha_inicio;
    $fechaFin = $request->fecha_fin;

    if (!$fechaInicio || !$fechaFin) {
      return response()->json(['error' => true, 'mensaje' => 'Debes seleccionar un rango de fechas.'], 422);
    }

    $fechaInicio = $fechaInicio . ' 00:00:00';
    $fechaFin = $fechaFin . ' 23:59:59';

    $query = DB::table(DB::raw('(
      SELECT
        colaborador_clave,
        colaborador_nombre,
        ROUND(AVG(consultas_semana), 2) AS media,
        PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY consultas_semana) AS mediana,
        MODE() WITHIN GROUP (ORDER BY consultas_semana) AS moda,
        SUM(consultas_semana) AS total_consultas
      FROM (
        SELECT
          p.gafete AS colaborador_clave,
          CONCAT(p.nombre, \' \', p.apellido_paterno, \' \', p.apellido_materno) AS colaborador_nombre,
          DATE_TRUNC(\'week\', pdc.created_at) AS semana,
          COUNT(pdc.id) AS consultas_semana
        FROM paciente_datos_consulta pdc
          JOIN paciente p ON p.id = pdc.paciente_id AND p.borrado = 0
        WHERE pdc.borrado = 0 AND pdc.created_at BETWEEN ? AND ?
        GROUP BY
          p.gafete,
          p.nombre,
          p.apellido_paterno,
          p.apellido_materno,
          DATE_TRUNC(\'week\', pdc.created_at)
        ) AS consultas_semana
      GROUP BY
        colaborador_clave,
        colaborador_nombre
    ) AS estadisticas'))
    ->setBindings([
        $fechaInicio,
        $fechaFin
    ])
    ->orderByDesc('total_consultas');

    $columnas = [
      'colaborador_clave',
      'colaborador_nombre',
      'media',
      'mediana',
      'moda',
      'total_consultas',
    ];
    
    return Excel::download(new DynamicExportQuery($query, $columnas),'Reporte_Media_Mediana_Moda_' . now()->format('Ymd_His') . '.xlsx');
  }

  public function obtenerMedicamentoMasRecetado(Request $request)
  {
    $request->validate([
      'fecha_inicio' => 'required|date',
      'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
    ]);

    $fechaInicio = $request->fecha_inicio . ' 00:00:00';
    $fechaFin = $request->fecha_fin . ' 23:59:59';

    $datos = DB::select("
      SELECT
        rm.medicamento_codigo,
        rm.medicamento_nombre AS medicamento,
        COUNT(*) AS total_recetas,
        SUM(
          CASE
            WHEN TRIM(rm.cantidad_solicitada) ~ '^[0-9]+(\\.[0-9]+)?$'
            THEN TRIM(rm.cantidad_solicitada)::numeric
          ELSE 0
          END
        ) AS total_cantidad_solicitada
      FROM receta r
      JOIN receta_medicamento rm ON rm.receta_id = r.id AND rm.borrado = 0 
      WHERE r.borrado = 0 AND r.created_at BETWEEN ? AND ?
      GROUP BY
        rm.medicamento_codigo,
        rm.medicamento_nombre
      ORDER BY total_recetas DESC
    ", [
      $fechaInicio,
      $fechaFin
    ]);

    $totalRecetas = collect($datos)->sum('total_recetas');

    $datos = collect($datos)->map(function ($item) use ($totalRecetas) {
        $item->porcentaje = $totalRecetas > 0 ? round(($item->total_recetas / $totalRecetas) * 100, 2) : 0;
        return $item;
    })->values();

    return response()->json([
      'error' => false,
      'total_medicamentos' => $datos->count(),
      'total_recetas' => $totalRecetas,
      'medicamento_mas_recetado' => $datos->first(),
      'datos' => $datos
    ]);
  }

  public function exportarMedicamentoMasRecetado(Request $request)
  {
    ini_set('max_execution_time', 1200);
    ini_set('memory_limit', '2048M');

    $request->validate([
        'fecha_inicio' => 'required|date',
        'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
    ]);

    $fechaInicio = $request->fecha_inicio . ' 00:00:00';
    $fechaFin = $request->fecha_fin . ' 23:59:59';

    $query = DB::table('receta as r')
    ->join('receta_medicamento as rm', function ($join) {
      $join->on('rm.receta_id', '=', 'r.id')
      ->where('rm.borrado', 0);
    })
    ->where('r.borrado', 0)
    ->whereBetween('r.created_at', [$fechaInicio, $fechaFin])
    ->select(
      'rm.medicamento_codigo',
      'rm.medicamento_nombre as medicamento',
      DB::raw('COUNT(*) AS total_recetas'),
      DB::raw("
        SUM(
          CASE
          WHEN TRIM(rm.cantidad_solicitada) ~ '^[0-9]+(\\.[0-9]+)?$'
          THEN TRIM(rm.cantidad_solicitada)::numeric
          ELSE 0
          END
        ) AS total_cantidad_solicitada
      ")
    )
    ->groupBy(
      'rm.medicamento_codigo',
      'rm.medicamento_nombre'
    )
    ->orderByDesc('total_recetas');

    $columnas = [
      'Código medicamento',
      'Medicamento',
      'Total recetas',
      'Total cantidad solicitada',
    ];

    return Excel::download(new DynamicExportQuery($query, $columnas), 'Reporte_Medicamentos_Mas_Recetados_' . now()->format('Ymd_His') . '.xlsx');
  }

  public function obtenerMotivosVisitaMasFrecuentes(Request $request)
  {
    $request->validate([
        'fecha_inicio' => 'required|date',
        'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
    ]);

    $fechaInicio = $request->fecha_inicio . ' 00:00:00';
    $fechaFin = $request->fecha_fin . ' 23:59:59';

    $datos = DB::table('paciente as p')
    ->join('paciente_datos_consulta as pdc', function ($join) {
      $join->on('pdc.paciente_id', '=', 'p.id')
      ->where('pdc.borrado', 0);
    })
    ->join('paciente_tipo_visita as ptv', function ($join) {
      $join->on('pdc.paciente_tipo_visita_id', '=', 'ptv.id')
      ->where('ptv.borrado', 0);
    })
    ->where('p.borrado', 0)
    ->whereBetween('pdc.created_at', [$fechaInicio, $fechaFin])
    ->select(
      'pdc.cie_descripcion as motivo_visita',
      DB::raw('COUNT(pdc.id) AS total_consultas')
    )
    ->groupBy(
      'pdc.cie_descripcion'
    )
    ->orderByDesc('total_consultas')
    ->get();

    $totalConsultas = $datos->sum('total_consultas');

    $datos = $datos->map(function ($item) use ($totalConsultas) {
      $item->porcentaje = $totalConsultas > 0 ? round(($item->total_consultas / $totalConsultas) * 100, 2) : 0;
      return $item;
    })->values();

    return response()->json([
      'error' => false,
      'total_motivos' => $datos->count(),
      'total_consultas' => $totalConsultas,
      'motivo_mas_frecuente' => $datos->first(),
      'datos' => $datos
    ]);
  }

  public function exportarMotivosVisitaMasFrecuentes(Request $request)
{
    ini_set('max_execution_time', 1200);
    ini_set('memory_limit', '2048M');

    $request->validate([
        'fecha_inicio' => 'required|date',
        'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
    ]);

    $fechaInicio = $request->fecha_inicio . ' 00:00:00';
    $fechaFin = $request->fecha_fin . ' 23:59:59';

    $query = DB::table('paciente as p')
    ->join('paciente_datos_consulta as pdc', function ($join) {
      $join->on('pdc.paciente_id', '=', 'p.id')
      ->where('pdc.borrado', 0);
    })
    ->join('paciente_tipo_visita as ptv', function ($join) {
      $join->on('pdc.paciente_tipo_visita_id', '=', 'ptv.id')
      ->where('ptv.borrado', 0);
    })
    ->where('p.borrado', 0)
    ->whereBetween('pdc.created_at', [$fechaInicio, $fechaFin])
    ->select(
      'pdc.cie_descripcion as motivo_visita',
      DB::raw('COUNT(pdc.id) AS total_consultas')
    )
    ->groupBy(
      'pdc.cie_descripcion'
    )
    ->orderByDesc('total_consultas');

    $columnas = [
      'Motivo de visita',
      'Total consultas',
    ];

    return Excel::download(new DynamicExportQuery($query, $columnas), 'Reporte_Motivos_Visita_' . now()->format('Ymd_His') . '.xlsx');
  }
}
