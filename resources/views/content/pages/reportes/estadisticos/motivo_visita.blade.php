<div class="card mb-4">
    <div class="card-header py-3">
        <div class="row align-items-center">
            <div class="card shadow-none bg-transparent border border-secondary text-secondary">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 class="card-title text-secondary">
                                <span class="mdi mdi-clipboard-text-search fs-2"></span>
                                Motivos de visita médica más frecuentes
                            </h5>
                            <p class="card-text">
                                <span class="mdi mdi-comma fs-6"></span>
                                En esta sección se muestran los motivos de consulta médica más frecuentes durante el periodo seleccionado.
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <button type="button" class="btn btn-success" id="btn-exportar-motivos-visita">
                                <span class="mdi mdi-file-excel me-2"></span>
                                Generar reporte
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row align-items-center mb-4">
            {{-- Motivo más frecuente --}}
            <div class="col-md-4">
                <div class="d-flex align-items-center">
                    <div class="avatar me-3">
                        <span class="avatar-initial rounded bg-label-secondary">
                            <span class="mdi mdi-trophy fs-2"></span>
                        </span>
                    </div>
                    <div>
                        <small class="text-muted d-block">Motivo más frecuente</small>
                        <h5 class="mb-0" id="motivo-visita-mas-frecuente">-</h5>
                        <small class="text-muted" id="cantidad-motivo-visita-mas-frecuente">
                            0 consultas
                        </small>
                    </div>
                </div>
            </div>
            {{-- Motivos diferentes --}}
            <div class="col-md-4 mt-3 mt-md-0">
                <div class="d-flex align-items-center">
                    <div class="avatar me-3">
                        <span class="avatar-initial rounded bg-label-info">
                            <span class="mdi mdi-format-list-bulleted-type fs-2"></span>
                        </span>
                    </div>
                    <div>
                        <small class="text-muted d-block">Motivos registrados</small>
                        <h3 class="mb-0" id="total-motivos-visita">0</h3>
                        <small class="text-muted">diferentes</small>
                    </div>
                </div>
            </div>
            {{-- Total consultas --}}
            <div class="col-md-4 mt-3 mt-md-0">
                <div class="d-flex align-items-center">
                    <div class="avatar me-3">
                        <span class="avatar-initial rounded bg-label-success">
                            <span class="mdi mdi-stethoscope fs-2"></span>
                        </span>
                    </div>
                    <div>
                        <small class="text-muted d-block">Total de consultas</small>
                        <h3 class="mb-0" id="total-consultas-motivos">0</h3>
                        <small class="text-muted">durante el periodo</small>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-4">

        <div>
            <h6 class="mb-1">
                <span class="mdi mdi-podium-silver fs-3 me-2"></span>
                <b>TOP 5 motivos de visita médica</b>
            </h6>
            <small class="text-muted">
                Motivos de consulta con mayor frecuencia durante el periodo seleccionado
            </small>
        </div>
    </div>
    <div class="table-responsive p-2">
        <table class="table table-hover align-middle mb-0 table-light">
            <thead>
                <tr>
                    <th class="text-center">#</th>
                    <th>Motivo de visita</th>
                    <th class="text-center">Consultas</th>
                    <th class="text-center">% del total</th>
                </tr>
            </thead>
            <tbody id="tabla-motivos-visita">
                <tr>
                    <td colspan="5" class="text-center text-muted py-5">
                        <i class="mdi mdi-calendar-search-outline d-block mb-2" style="font-size: 32px;"></i>
                        <div class="fw-semibold">Selecciona un rango de fechas</div>
                        <small>Los motivos de visita aparecerán aquí</small>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>