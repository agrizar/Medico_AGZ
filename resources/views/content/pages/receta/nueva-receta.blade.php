@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Nueva Receta')

@section('page-style')
<link rel="stylesheet" href="{{ asset('assets/css/recetas/recetas.css') }}?v={{ date('YmdHis') }}">
<link rel="stylesheet" href="{{ asset('assets/css/recetas/impresionReceta.css') }}?v={{ date('YmdHis') }}">
@endsection

@section('page-script')
<script src="{{ asset('assets/js/recetas/receta.js') }}?v={{ date('YmdHis') }}"></script>

<script>
    var datos_vista = @json($datos_vista);

</script>
@endsection

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-misc.css') }}">
<!-- Form Validation -->
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/formvalidation/dist/css/formValidation.min.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/quill/typography.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/quill/katex.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/quill/editor.css') }}" />

@endsection

@section('vendor-script')
<!-- Form Validation -->
<script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/FormValidation.min.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/plugins/Bootstrap5.min.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/plugins/AutoFocus.min.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/quill/katex.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/quill/quill.js') }}"></script>
@endsection

@include('content.pages.pantalla-carga')
@section('content')

@php
$receta = $datos_vista['detalles_receta'][0] ?? null;
$firma = $receta['firma_usuario'] ?? $datos_vista['firma_usuario_logueado'] ?? null;
$firmaPaciente = $receta['firma_paciente'] ?? null;
$nombreDoctor = $receta ? $receta['usuario_creador_nombre'] . ' ' . $receta['usuario_creador_apellido_p'] . ' ' . $receta['usuario_creador_apellido_m'] : Auth::user()->nombre . ' ' . Auth::user()->apellido_paterno . ' ' . Auth::user()->apellido_materno;
$universidadEgreso = $receta ? $receta['universidad_egreso'] : Auth::user()->universidad_egreso;
@endphp

<form id="form-receta" method="POST" enctype="multipart/form-data" onSubmit="return false">
    @csrf

    <div class="row no-imprimir {{ $receta ? 'd-none' : '' }}" style="justify-content: center;">
        <div class="col-md-6 mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-md-8">
                    <div class="form-floating form-floating-outline">
                        <input type="text" id="empleado-gafete-input" name="empleado[gafete]"
                            class="form-control" placeholder="Ej. 12345" autocomplete="off">
                        <label for="empleado-gafete-input">Gafete del paciente <i class="text-danger">*</i></label>
                    </div>
                </div>

                <div class="col-md-4 align-self-end">
                    <button type="button" class="btn btn-primary w-100" id="btn-buscar-empleado">
                        <i class="mdi mdi-magnify me-1"></i> Buscar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card p-5 ajuste-hoja-impresion" style="width: 100%; height: 100%; zoom: 0.7;">

        <div class="row">
            <div class="col-md-3 col-sm-3 d-flex" style="align-items: center;">
                <img src="{{ asset('assets/img/logos/agzback.png') }}" alt="Logo" class="logo-superior-receta">
            </div>

            <div class="col-md-6 col-sm-6 text-center">
                <span class="badge bg-primary fs-4" style="border-radius: 10px">RECETA MÉDICA</span>

                <h5 class="text-primary mt-4">
                    Folio: #F-{{ $receta['receta_id'] ?? $datos_vista['folio'] }}
                </h5>
            </div>

            <div class="col-md-3 col-sm-3">
                <div class="card shadow-none bg-label-primary text-end">
                    <div class="card-body mt-2" style="align-self: center;">

                        <h5 class="card-title text-primary">
                            <i class="mdi mdi-calendar-month"></i>
                            <b>Fecha:</b>
                            <span class="text-primary">
                                {{ isset($receta['fecha_creacion'])
                                        ? date('d/m/Y', strtotime($receta['fecha_creacion']))
                                        : now()->format('d/m/Y') }}
                            </span>
                        </h5>

                        <h5 class="card-title text-primary">
                            <i class="mdi mdi-clock-outline"></i>
                            <b>Hora:</b>
                            <span class="text-primary">
                                {{ isset($receta['fecha_creacion'])
                                        ? date('H:i:s', strtotime($receta['fecha_creacion']))
                                        : now()->format('H:i:s') }}
                            </span>
                        </h5>

                    </div>
                </div>
            </div>
        </div>

        <div class="divider divider-primary">
            <div class="divider-text">
                <i class="mdi mdi-pill-multiple text-primary"></i>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-4 col-sm-4">
                <div class="col-md-12">
                    <span class="badge bg-label-primary rounded-pill fs-5">PACIENTE</span>
                </div>

                <div class="col-md-12 mt-4">
                    <h5 class="text-primary">
                        <i class="mdi mdi-account-circle-outline text-primary fs-1 me-2"></i>
                        <label id="paciente_receta-nombre">
                            {{ $receta ? $receta['paciente_nombre'] . ' ' . $receta['paciente_apellido_p'] . ' ' . $receta['paciente_apellido_m'] : '' }}
                        </label>
                    </h5>

                    <h5 class="text-secondary">
                        <b>Edad:</b>
                        <label id="paciente_receta-edad"></label>
                        {{ $receta['paciente_edad'] ?? '' }} {{ $receta ? 'años' : '' }}
                    </h5>
                </div>
            </div>

            <div class="col-md-4 col-sm-4 text-center">
                <h3>
                    Dr.
                    {{ $receta
                            ? $receta['usuario_creador_nombre'] .
                                ' ' .
                                $receta['usuario_creador_apellido_p'] .
                                ' ' .
                                $receta['usuario_creador_apellido_m']
                            : Auth::user()->nombre . ' ' . Auth::user()->apellido_paterno . ' ' . Auth::user()->apellido_materno }}
                </h3>

                <h5 class="text-muted {{ (isset($universidadEgreso) && !empty($universidadEgreso)) ? '' : 'd-none' }}">
                    Egresado de {{ $receta['universidad_egreso'] ?? $universidadEgreso }}
                </h5>
                <h4 class="text-primary">
                    {{ $receta['usuario_perfil'] ?? $datos_vista['perfil_nombre']['nombre'] }}
                </h4>

                <h5>
                    <strong>C.P.</strong>
                    {{ $receta['cedula_profesional'] ?? Auth::user()->cedula_profesional }}
                </h5>

                {{-- <h5>
                    <strong>REG.SSA.</strong>
                    {{ $receta['registro_ssa'] ?? Auth::user()->registro_ssa }}
                </h5> --}}
            </div>

            <div class="col-md-4 col-sm-4 text-end">
                <img src="{{ asset('images/caduceo.png') }}" alt="Logo" class="logo-superior-receta">
            </div>
        </div>

        <div class="divider divider-dashed">
            <div class="divider-text">-</div>
        </div>

        <h4 class="text-primary bg-label-primary p-2">
            <i class="mdi mdi-pill-multiple text-primary fs-2 me-2"></i>MEDICAMENTOS
        </h4>

        <div class="table-responsive mb-4">
            <table class="table table-hover mb-0 table-sm" id="tabla-medicamentos-receta">
                <thead class="bg-label-primary">
                    <tr>
                        <th width="65%">Medicamento</th>
                        <th width="15%" class="text-center">Presentación</th>
                        <th width="10%" class="text-center">Cantidad</th>
                        <th width="10%" class="text-center no-imprimir {{ $receta ? 'd-none' : '' }}">Acción</th>
                    </tr>
                </thead>
                <tbody id="contenedor-medicamentos">
                    @if ($receta)
                    @foreach ($datos_vista['medicamentos'] as $medicamento)
                    <tr>
                        <td>{{ $medicamento['medicamento_nombre'] }}</td>
                        <td class="text-center">{{ $medicamento['abreviatura'] }}</td>
                        <td class="text-center">{{ $medicamento['cantidad_solicitada'] }}</td>
                        <td class="text-center d-none"></td>
                    </tr>
                    @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        <div class="col-md-12 col-sm-12 text-center no-imprimir {{ $receta ? 'd-none' : '' }}">
            <button type="button" class="btn btn-label-warning mb-4 mt-4 btn-sm" id="boton-obtener_listado_medicamentos">
                <i class="mdi mdi-plus-thick me-2"></i>Agregar medicamento
            </button>
        </div>

        <h4 class="text-primary bg-label-primary p-2">
            <i class="mdi mdi-clipboard-list fs-2 me-2"></i>INDICACIONES DEL MEDICAMENTO
        </h4>

        <div class="row">
            <div class="col-md-12 col-sm-12" style="font-size: 17px;">
                <textarea {{ $receta ? 'disabled' : '' }} id="receta-medicamento_indicaciones" name="receta[medicamento_indicaciones]" cols="30" rows="5" style="border:none;background:transparent;outline:none;width:100%;">{{ $receta['medicamento'] ?? '' }}</textarea>
            </div>
        </div>

        <h4 class="text-primary bg-label-primary p-2">
            <i class="mdi mdi-heart-pulse text-primary fs-2 me-2"></i>RECOMENDACIONES GENERALES
        </h4>

        <div class="row">
            <div class="col-md-12 col-sm-12" style="font-size: 17px;">
                <textarea {{ $receta ? 'disabled' : '' }} id="receta-recomendaciones" name="receta[recomendaciones]" class="mb-5" cols="30" rows="5" style="border:none;background:transparent;outline:none;width:100%;">{{ $receta['recomendaciones'] ?? '' }}</textarea>
            </div>
        </div>

        <div class="row mt-4 mb-4">

            <div class="col-md-6">
                @if($firmaPaciente)
                {{-- FIRMA (imagen) --}}
                <div class="col-md-12 text-center">
                    @if($firmaPaciente)
                    <img src="{{ asset($firmaPaciente) }}" style="max-height:120px;">
                    @else
                    <div style="height: 120px;"></div>
                    @endif
                </div>

                {{-- LÍNEA DE FIRMA --}}
                <div class="col-md-12 col-sm-12 text-center d-flex justify-content-center">
                    <div class="firma-linea"></div>
                </div>

                {{-- TEXTO DEL MÉDICO --}}
                <div class="col-md-12 col-sm-12 text-center">
                    <p class="firma-texto text-primary mb-0">
                        {{ $receta ? $receta['paciente_nombre'] . ' ' . $receta['paciente_apellido_p'] . ' ' . $receta['paciente_apellido_m'] : '' }}
                        <br>
                        Firma del paciente
                    </p>
                </div>
                @endif
            </div>

            <div class="col-md-6">
                {{-- FIRMA (imagen) --}}
                <div class="col-md-12 text-center">
                    @if($firma)
                    <img src="{{ asset($firma) }}" style="max-height:120px;">
                    @else
                    <div style="height: 120px;"></div>
                    @endif
                </div>

                {{-- LÍNEA DE FIRMA --}}
                <div class="col-md-12 col-sm-12 text-center d-flex justify-content-center">
                    <div class="firma-linea"></div>
                </div>

                {{-- TEXTO DEL MÉDICO --}}
                <div class="col-md-12 col-sm-12 text-center">
                    <p class="firma-texto text-primary mb-0">
                        Dr. {{ $nombreDoctor }}
                        <br>
                        Firma del médico
                    </p>
                </div>
            </div>

        </div>

        <div class="row mt-4 text-center">
            <div class="col-md-6 col-sm-6">
                <div class="col-md-12 col-sm-12">
                    <h5 class="text-primary">
                        <i class="mdi mdi-barcode-scan me-2 fs-3"></i>Código de receta
                    </h5>
                </div>

                @php
                $codigo = ($receta['receta_id'] ?? $datos_vista['folio']);
                @endphp

                <div class="col-md-12 col-sm-12 d-flex justify-content-center">
                    {!! DNS1D::getBarcodeHTML((string)$codigo, 'C128', 2.2, 60) !!}
                </div>

                <div class="col-md-12 col-sm-12 text-center">
                    <strong>F-{{ $codigo }}</strong>
                </div>
            </div>

            <div class="col-md-6 col-sm-6">
                <div class="row">
                    <div class="col-md-1 col-sm-1">
                        <i class="mdi mdi-clock-time-eight-outline text-primary fs-3"></i>
                    </div>

                    <div class="col-md-11 col-sm-11 text-start">
                        <h5 class="text-primary">
                            La presente receta tiene una vigencia de 72 horas a partir de su emisión
                            [{{ isset($receta['fecha_creacion']) ? date('d/m/Y H:i', strtotime($receta['fecha_creacion'])) : now()->format('d/m/Y H:i') }}].
                            <br>
                            <span class="text-danger">
                                <strong>Una vez transcurrido este periodo, la receta dejará de ser válida.</strong>
                            </span>
                        </h5>
                    </div>

                </div>

                <div class="row">
                    <div class="col-md-1 col-sm-1">
                        <i class="mdi mdi-line-scan text-primary fs-3"></i>
                    </div>

                    <div class="col-md-11 col-sm-11 text-start">
                        <h5 class="text-primary">
                            Acuda a la farmacia de Agrizar y solicite el escaneo de este código de barras en el sistema
                            del Servicio Médico para consultar la receta.
                        </h5>
                    </div>
                </div>
            </div>
        </div>

    </div>

</form>

<div class="row mt-4 no-imprimir {{ $receta ? '' : 'd-none' }}">
    <div class="col-md-12 col-sm-12 mb-4 text-center">
        <button id="boton-imprimir_receta_de_nuevo" type="button" class="btn btn-warning me-2" onclick="window.print();">
            <span class="mdi mdi-printer me-2"></span>
            Imprimir de nuevo
        </button>
    </div>
</div>

<div class="row mt-4 no-imprimir {{ $receta ? 'd-none' : '' }}">
    <div class="col-md-12 col-sm-12 mb-4 text-center">
        <button id="boton-imprimir_receta" type="button" class="btn btn-warning me-2">
            <span class="mdi mdi-printer me-2"></span>
            Guardar e imprimir receta
        </button>
    </div>
</div>

<div class="col-md-12 col-sm-12 no-imprimir">
    <div class="btn-flex-group" style="position: fixed; bottom: 2rem; right: 0.8rem; z-index: 1080;">
        <a href="/" class="btn btn-principal waves-effect waves-light me-2">
            <span class="mdi mdi-home me-2"></span>
            Inicio
        </a>
    </div>
</div>

{{-- Listado del medicamento en un modal lateral --}}
<div class="offcanvas offcanvas-end no-imprimir" data-bs-scroll="true" data-bs-backdrop="false" tabindex="-1" id="canvasMedicamentos" aria-labelledby="canvasMedicamentosLabel" style="width: 700px;">
    <div class="offcanvas-header">
        <h5 id="canvasMedicamentosLabel" class="offcanvas-title"><span class="mdi mdi-format-list-bulleted-square"></span> Listado de medicamentos</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body p-0 d-flex flex-column">
        <!-- Sección FIJA (sin scroll) -->
        <div class="p-3 border-bottom bg-white" style="flex-shrink: 0;">
            <div class="row justify-content-center">
                <div class="col-md-12 mb-3">
                    <div class="form-floating form-floating-outline">
                        <select class="form-select form-select-sm" id="almacen_medicamentos" name="almacen_medicamentos" onchange="obtenerCatalogoMedicamentosHispatec()">
                            <option value="" selected>Selecciona una opción</option>
                            @foreach ($datos_vista['usuario_almacenes'] as $almacen)
                            <option value="{{ $almacen['almacen_id'] }}" data-empresa_id="{{ $almacen['empresa_id'] }}" data-almacen_codigo="{{ $almacen['almacen_codigo'] }}">
                                {{ $almacen['almacen_codigo'] }} - {{ $almacen['almacen_nombre'] }}
                                ({{ $almacen['empresa_nombre'] }})
                            </option>
                            @endforeach
                        </select>
                        <label for="almacen_medicamentos">Almacén <i class="text-danger">*</i></label>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center" id="contenedor-filtros-medicamento" style="display: none;">
                <div class="col-md-12">
                    <div class="form-floating form-floating-outline">
                        <input type="text" id="filtro-medicamento" name="filtro-medicamento" class="form-control" placeholder="Ingresa el medicamento a buscar." />
                        <label for="filtro-medicamento">Buscar medicamento</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección con SCROLL (solo la lista) -->
        <div class="flex-grow-1" style="overflow-y: auto;">
            <div class="p-3">
                <ul class="list-group list-group-timeline" id="listado-medicamentos-receta">
                </ul>
            </div>
        </div>
    </div>

    <div class="offcanvas-footer p-3 border-top bg-white">
        <button type="button" class="btn btn-outline-danger w-100" data-bs-dismiss="offcanvas">
            Cerrar
        </button>
    </div>
</div>

@endsection
