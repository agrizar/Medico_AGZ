<div class="card mb-4">

    {{-- Encabezado --}}
    <div class="card-header py-3">
        <div class="row align-items-center">
            <div class="card shadow-none bg-transparent border border-success text-success">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 class="card-title text-success">
                                <span class="mdi mdi-pill fs-2"></span>
                                Medicamentos más recetados
                            </h5>
                            <p class="card-text">
                                <span class="mdi mdi-comma fs-6"></span>
                                En esta sección se muestran los medicamentos con mayor número de recetas durante el periodo seleccionado.
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <button type="button" class="btn btn-success" id="btn-exportar-medicamentos">
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
            {{-- Medicamento más recetado --}}
            <div class="col-md-4">
                <div class="d-flex align-items-center">
                    <div class="avatar me-3">
                        <span class="avatar-initial rounded bg-label-success">
                            <span class="mdi mdi-trophy fs-2"></span>
                        </span>
                    </div>
                    <div>
                        <small class="text-muted d-block">Medicamento más recetado</small>
                        <h5 class="mb-0" id="medicamento-mas-recetado">-</h5>
                        <small class="text-muted" id="cantidad-medicamento-mas-recetado">0 recetas</small>
                    </div>
                </div>
            </div>
            {{-- Total de medicamentos --}}
            <div class="col-md-4 mt-3 mt-md-0">
                <div class="d-flex align-items-center">
                    <div class="avatar me-3">
                        <span class="avatar-initial rounded bg-label-info">
                            <span class="mdi mdi-pill-multiple fs-2"></span>
                        </span>
                    </div>
                    <div>
                        <small class="text-muted d-block">Medicamentos registrados</small>
                        <h3 class="mb-0" id="total-medicamentos">0</h3>
                        <small class="text-muted">diferentes</small>
                    </div>
                </div>
            </div>

            {{-- Total de recetas --}}
            <div class="col-md-4 mt-3 mt-md-0">
                <div class="d-flex align-items-center">
                    <div class="avatar me-3">
                        <span class="avatar-initial rounded bg-label-warning">
                            <span class="mdi mdi-prescription fs-2"></span>
                        </span>
                    </div>
                    <div>
                        <small class="text-muted d-block">Total de recetas</small>
                        <h3 class="mb-0" id="total-recetas-medicamentos">0</h3>
                        <small class="text-muted">durante el periodo</small>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-4">

        {{-- Título --}}
        <div>
            <h6 class="mb-1">
                <span class="mdi mdi-podium-silver fs-3 me-2"></span>
                <b>TOP 5 medicamentos más recetados</b>
            </h6>
            <small class="text-muted">
                Medicamentos con mayor número de recetas durante el periodo seleccionado
            </small>
        </div>

    </div>

    {{-- Tabla --}}
    <div class="table-responsive p-2">
        <table class="table table-hover align-middle mb-0 table-light">
            <thead>
                <tr>
                    <th class="text-center">#</th>
                    <th>Medicamento</th>
                    <th class="text-center">Cantidad de recetas</th>
                    <th class="text-center">% del total</th>
                </tr>
            </thead>
            <tbody id="tabla-medicamentos-mas-recetados">
                <tr>
                    <td colspan="4" class="text-center text-muted py-5">
                        <i class="mdi mdi-calendar-search-outline d-block mb-2" style="font-size: 32px;"></i>
                        <div class="fw-semibold">Selecciona un rango de fechas</div>
                        <small>Los medicamentos más recetados aparecerán aquí</small>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>