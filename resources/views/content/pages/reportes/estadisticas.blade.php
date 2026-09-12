@php
    $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Reporte estadístico')

@section('page-style')
@endsection

@section('page-script')
  <script src="{{ asset('assets/js/reportes/estadistico.js') }}?v={{ date('YmdHis')}}"></script>
  <script>
    var datos_vista = @json($datos_vista);
  </script>
@endsection

@section('vendor-style')
  <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}" />
@endsection

@section('vendor-script')
  <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
  <script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
@endsection

@include('content.pages.pantalla-carga')

@section('content')
<div class="card p-5">

    {{-- Encabezado --}}
    <div class="divider">
        <div class="divider-text texto-titulo">Reporte estadístico</div>
    </div>
   
    <form id="form-reporte_pacientes" method="POST" enctype="multipart/form-data" onSubmit="return false">
        @csrf
        {{-- Filtros --}}
        <div class="divider text-start">
            <div class="divider-text"><span class="mdi mdi-filter-multiple me-2"></span>Filtros para generar reporte estadístico</div>
        </div>

        <div class="row mt-2 mb-2">
            <div class="col-lg-4 col-md-4 col-sm-12 mb-3">
                <div class="form-floating form-floating-outline">
                <input type="date" class="form-control" id="filtro-fecha_inicio" placeholder="Selecciona la fecha inicial." />
                <label for="filtro-fecha_inicio">Fecha inicio</label>
                </div>
            </div>
            <div class="col-lg-4 col-md-4 col-sm-12 mb-3">
                <div class="form-floating form-floating-outline">
                <input type="date" class="form-control" id="filtro-fecha_fin" placeholder="Selecciona la fecha final." />
                <label for="filtro-fecha_fin">Fecha fin</label>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-12 mb-3">
                <button type="button" class="btn btn-primary w-100" id="btn-generar-reporte">
                    <i class="bx bx-search me-1"></i>Consultar
                </button>
            </div>
        </div>
        <div class="row">
            <small class="text-muted">
                <span class="mdi mdi-information"></span>
                Selecciona el periodo del que deseas obtener la información. Todos los reportes utilizarán este rango de fechas.
            </small>
        </div>
    </form>

</div>

<div class="divider text-center">
    <div class="divider-text">
        <span class="mdi mdi-chart-line-variant"></span>
    </div>
</div>

@include('content.pages.reportes.estadisticos.consultas_por_colaborador_por_semana')

<div class="divider text-center">
    <div class="divider-text">
        <span class="mdi mdi-chart-line-variant"></span>
    </div>
</div>

@include('content.pages.reportes.estadisticos.consultas_por_colaborador')

<div class="divider text-center">
    <div class="divider-text">
        <span class="mdi mdi-chart-line-variant"></span>
    </div>
</div>

@include('content.pages.reportes.estadisticos.media_mediana_moda')

<div class="divider text-center">
    <div class="divider-text">
        <span class="mdi mdi-chart-line-variant"></span>
    </div>
</div>

@include('content.pages.reportes.estadisticos.medicamentos_mas_recetados')

<div class="divider text-center">
    <div class="divider-text">
        <span class="mdi mdi-chart-line-variant"></span>
    </div>
</div>

@include('content.pages.reportes.estadisticos.motivo_visita')

@endsection