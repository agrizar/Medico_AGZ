<div class="card mb-4">

    {{-- Encabezado --}}
    <div class="card-header py-3">
        <div class="row align-items-center">
            <div class="card shadow-none bg-transparent border border-info text-info">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 class="card-title text-info">
                                <span class="mdi mdi-chart-bell-curve-cumulative fs-2"></span>
                                Estadísticas de consultas por colaborador
                            </h5>
                            <p class="card-text">
                                <span class="mdi mdi-comma fs-6"></span>
                                En esta sección se muestran la media, mediana y moda de las consultas semanales realizadas por cada colaborador durante el periodo seleccionado.
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <button type="button" class="btn btn-success" id="btn-exportar-estadisticas-colaborador">
                                <span class="mdi mdi-file-excel me-2"></span>
                                Generar reporte
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Contenido --}}
    <div class="card-body">
        {{-- Indicadores --}}
        <div class="row align-items-center mb-4">
            
            {{-- Media --}}
            <div class="col-md-3">
                <div class="d-flex align-items-center">
                    <div class="avatar me-3">
                        <span class="avatar-initial rounded bg-label-primary">
                            <span class="mdi mdi-chart-line fs-2"></span>
                        </span>
                    </div>
                    <div>
                        <small class="text-muted d-block">Media semanal</small>
                        <h3 class="mb-0" id="media-consultas">0</h3>
                        <small class="text-muted">consultas</small>
                    </div>
                </div>
            </div>

            {{-- Mediana --}}
            <div class="col-md-3 mt-3 mt-md-0">
                <div class="d-flex align-items-center">
                    <div class="avatar me-3">
                        <span class="avatar-initial rounded bg-label-info">
                            <span class="mdi mdi-chart-box-outline fs-2"></span>
                        </span>
                    </div>
                    <div>
                        <small class="text-muted d-block">Mediana semanal</small>
                        <h3 class="mb-0" id="mediana-consultas">0</h3>
                        <small class="text-muted">consultas</small>
                    </div>
                </div>
            </div>

            {{-- Moda --}}
            <div class="col-md-3 mt-3 mt-md-0">
                <div class="d-flex align-items-center">
                    <div class="avatar me-3">
                        <span class="avatar-initial rounded bg-label-warning">
                            <span class="mdi mdi-chart-bell-curve fs-2"></span>
                        </span>
                    </div>
                    <div>
                        <small class="text-muted d-block">Moda semanal</small>
                        <h3 class="mb-0" id="moda-consultas">0</h3>
                        <small class="text-muted">consultas</small>
                    </div>
                </div>
            </div>

            {{-- Total --}}
            <div class="col-md-3 mt-3 mt-md-0">
                <div class="d-flex align-items-center">
                    <div class="avatar me-3">
                        <span class="avatar-initial rounded bg-label-success">
                            <span class="mdi mdi-stethoscope fs-2"></span>
                        </span>
                    </div>
                    <div>
                        <small class="text-muted d-block">Consultas totales</small>
                        <h3 class="mb-0" id="total-consultas-estadisticas">0</h3>
                        <small class="text-muted">durante el periodo</small>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-4">

        {{-- Tabla --}}
        <div>
            <h6 class="mb-1">
                <span class="mdi mdi-podium-silver fs-3 me-2"></span>
                <b>TOP 5 colaboradores - Estadísticas de consultas</b>
            </h6>
            <small class="text-muted">
                Resumen estadístico de consultas semanales durante el periodo seleccionado
            </small>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="table-responsive p-2">
        <table class="table table-hover align-middle mb-0 table-light">
            <thead>
                <tr>
                    <th class="text-center">#</th>
                    <th>Nombre de colaborador</th>
                    <th class="text-center">Media</th>
                    <th class="text-center">Mediana</th>
                    <th class="text-center">Moda</th>
                    <th class="text-center">Total consultas</th>
                </tr>
            </thead>
            <tbody id="tabla-estadisticas-colaborador">
                <tr>
                    <td colspan="6" class="text-center text-muted py-5">
                        <i class="mdi mdi-calendar-search-outline d-bloc mb-2" style="font-size: 32px;"></i>
                        <div class="fw-semibold">Selecciona un rango de fechas</div>
                        <small>Las estadísticas aparecerán aquí</small>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>