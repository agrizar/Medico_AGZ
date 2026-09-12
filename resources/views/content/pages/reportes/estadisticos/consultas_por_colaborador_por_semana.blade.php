<div class="card mb-4">

    {{-- Encabezado --}}
    <div class="card-header py-3">
        <div class="row align-items-center">

            {{-- Titulo --}}
            <div class="card shadow-none bg-transparent border border-primary text-primary">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h5 class="card-title text-primary">
                                <span class="mdi mdi-calendar-week-outline fs-2"></span>
                                Consultas semanales por colaborador
                            </h5>
                            <p class="card-text">                                
                                <span class="mdi mdi-comma fs-6"></span>
                                En esta sección se puede visualizar los colaboradores con mayor número de consultas semanales durante el periodo seleccionado previamente. Para obtener el reporte detallado descarga el reporte en formato excel.
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <button type="button" class="btn btn-success" id="btn-exportar-consultas-colaborador">
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
            <div class="col-md-6">
                <div class="d-flex align-items-center">
                    <div class="avatar me-3">
                        <span class="avatar-initial rounded bg-label-primary">
                            <span class="mdi mdi-account-group-outline fs-2"></span>
                        </span>
                    </div>
                    <div>
                        <small class="text-muted d-block">Colaboradores</small>
                        <h3 class="mb-0" id="total-colaboradores">0</h3>
                        <small class="text-muted">durante el periodo seleccionado</small>
                    </div>
                </div>
            </div>

            {{-- Consultas --}}
            <div class="col-md-6 mt-3 mt-md-0">
                <div class="d-flex align-items-center">
                    <div class="avatar me-3">
                        <span class="avatar-initial rounded bg-label-success">
                            <span class="mdi mdi-stethoscope fs-2"></span>
                        </span>
                    </div>
                    <div>
                        <small class="text-muted d-block">Consultas totales</small>
                        <h3 class="mb-0" id="total-consultas-periodo">0</h3>
                        <small class="text-muted">durante el periodo seleccionado</small>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-4">

        {{-- Gráfico --}}
        <div style="height: auto;">
            <div id="grafico-consultas-colaborador"></div>
        </div>

        <hr class="my-4">

        {{-- Tabla Top 5 --}}
        <div>
            <div class="d-flex justify-content-between align-items-center mb-3"></div>
            <h6 class="mb-1"><span class="mdi mdi-podium-silver fs-3 me-2"></span><b>TOP 5 colaboradores con más consultas semanales - Formato tabla</b></h6>
            <small class="text-muted">Colaboradores con mayor número de consultas durante el periodo seleccionado</small>
        </div>
    </div>

    <div class="table-responsive p-2">
        <table class="table table-hover align-middle mb-0 table-light">
            <thead>
                <tr>
                    <th class="text-center">#</th>
                    <th>Nombre de colaborador</th>
                    <th class="text-center">Semana</th>
                    <th class="text-center">Total consultas</th>
                    <th class="text-center">Año</th>
                </tr>
            </thead>

            <tbody id="tabla-consultas-colaborador">
                <tr>
                    <td colspan="5" class="text-center text-muted py-5">
                        <i class="mdi mdi-calendar-search-outline d-block mb-2"
                           style="font-size: 32px;"></i>

                        <div class="fw-semibold">
                            Selecciona un rango de fechas
                        </div>

                        <small>
                            La información del TOP 5 aparecerá aquí
                        </small>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>