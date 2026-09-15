moment.locale('es');

// Función para capitalizar la primera letra de una cadena
function capitalizeFirstLetter(string) {
  return string.charAt(0).toUpperCase() + string.slice(1).toLowerCase();
}

$(document).ready(function () {
  // Ocultamos la pantalla de carga cuando la pantalla termino de cargar todo el contenido
  pantallaCarga('off');

  generarTabla();
});

$('#boton-recargar-tabla-historicos').on('click', function () {
  generarTabla();
});

function generarTabla() {
  // Verificar si la tabla ya existe y destruirla para evitar el error de reinitialise
  if ($.fn.DataTable.isDataTable('.datatables-basic-filas')) {
    $('.datatables-basic-filas').DataTable().clear().destroy();
  }

  let table = $('.datatables-basic-filas').DataTable({
    buttons: [],
    processing: true,
    serverSide: true,
    ajax: {
      url: '/historia_clinica/api/obtener-lista-historicos-clinicos',
      type: 'POST',
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      beforeSend: function () {
        pantallaCarga('on');
      },
      complete: function () {
        pantallaCarga('off');
      },
      error: function (xhr, error, code) {
        if (xhr.status === 500) {
          // Si hay un error 500, espera 1 segundo y luego recarga los datos
          setTimeout(function () {
            table.ajax.reload();
          }, 1000);
        }
      }
    },
    columns: [
      { data: 'id' },
      { data: 'tipo_registro' },
      { data: 'curp' },
      { data: 'nombre_completo' },
      { data: 'genero' },
      { data: 'edad' },
      { data: 'antidoping' },
      { data: 'calificacion' },
      { data: 'idx' },
      { data: 'created_at' },
      { data: 'acciones' }
    ],
    columnDefs: [
      {
        className: 'control',
        orderable: false,
        responsivePriority: 2,
        targets: 0,
        visible: false
      },
      /* Acciones a realizar para cada fila */
      {
        targets: 1,
        title: 'Tipo de histórico',
        orderable: false,
        className: 'text-center',
        render: function (data, type, full, meta) {
          return `
                  <div class="row">
                      <div class="d-flex gap-2 align-items-center col-12">
                          <div class="card-info ">
                              <h6 class="mb-0">${full.tipo_registro != null ? full.tipo_registro : ''}</h6>
                          </div>
                      </div>
                  </div>`;
        }
      },
      {
        targets: 2,
        className: 'text-center',
        orderable: false,
        title: 'CURP',
        render: function (data, type, full, meta) {
          return `
                  <div class="row">
                      <div class="d-flex gap-2 align-items-center col-12">
                          <div class="card-info ">
                            <h6 class="mb-0">${full.curp != null ? full.curp : ''}</h6>
                          </div>
                      </div>
                  </div>`;
        }
      },
      {
        targets: 3,
        className: 'text-center',
        title: 'Nombre',
        orderable: false,
        render: function (data, type, full, meta) {
          return `
                  <div class="row">
                      <div class="d-flex gap-2 align-items-center col-12">
                          <div class="card-info ">
                            <h6 class="mb-0">${full.nombre_completo != null ? full.nombre_completo : ''}</h6>
                          </div>
                      </div>
                  </div>`;
        }
      },
      {
        targets: 4,
        className: 'text-center',
        title: 'Genero',
        orderable: false,
        render: function (data, type, full, meta) {
          return `
                  <div class="row">
                      <div class="d-flex gap-2 align-items-center col-12">
                          <div class="card-info ">
                            <h6 class="mb-0">${
                              full.genero != null ? (full.genero == 'F' ? 'Mujer' : 'Hombre') : ''
                            }</h6>
                          </div>
                      </div>
                  </div>`;
        }
      },
      {
        targets: 5,
        className: 'text-center',
        title: 'Edad',
        orderable: false,
        render: function (data, type, full, meta) {
          return `
                  <div class="row">
                      <div class="d-flex gap-2 align-items-center col-12">
                          <div class="card-info ">
                            <h6 class="mb-0">${full.edad != null ? full.edad + ' años' : ''}</h6>
                          </div>
                      </div>
                  </div>`;
        }
      },
      {
        targets: 6,
        className: 'text-center',
        title: 'Antidoping',
        orderable: false,
        render: function (data, type, full, meta) {
          return `
                  <div class="row">
                      <div class="d-flex gap-2 align-items-center col-12" style="justify-content: center;">
                          <div class="card-info ">
                            <h6 class="mb-0" style="color: ${
                              full.antidoping != null ? (full.antidoping == 1 ? '#f43232' : '#006c39') : ''
                            }">${full.antidoping != null ? (full.antidoping == 1 ? 'Positivo' : 'Negativo') : ''}</h6>
                          </div>
                      </div>
                  </div>`;
        }
      },
      {
        targets: 7,
        className: 'text-center',
        title: 'Calificación',
        orderable: false,
        render: function (data, type, full) {
          if (!full.calificacion) {
            return `
              <span class="badge bg-label-secondary px-3 py-2">
                Sin calificación
              </span>
            `;
          }

          let clase = 'bg-label-secondary';
          let icono = 'mdi-help-circle-outline';

          if (full.calificacion === 'Apto') {
            clase = 'bg-label-success';
            icono = 'mdi-check-circle-outline';
          } 
          else if (full.calificacion === 'No apto') {
            clase = 'bg-label-danger';
            icono = 'mdi-close-circle-outline';
          } 
          else if (full.calificacion === 'Apto con condicionante') {
            clase = 'bg-label-warning';
            icono = 'mdi-alert-circle-outline';
          }

          return `
            <span class="badge ${clase} px-3 py-2">
              <i class="mdi ${icono} me-1"></i>
              ${full.calificacion}
            </span>
          `;
        },

        createdCell: function (td, cellData, rowData) {

          $(td).css({
            'background-color': 'transparent',
            'border-left': '0'
          });
        }
      },
      {
        targets: 8,
        className: 'text-center',
        title: 'IDX',
        orderable: false,
        render: function (data, type, full, meta) {
          return `
                  <div class="row" style="max-height: 200px;overflow: auto;width: 200px;">
                      <div class="d-flex gap-2 align-items-center col-12">
                          <div class="card-info ">
                            <h6 class="mb-0">${full.idx != null ? full.idx : ''}</h6>
                          </div>
                      </div>
                  </div>`;
        }
      },
      {
        targets: 9,
        className: 'text-center',
        title: 'Fecha creación',
        orderable: false,
        render: function (data, type, full, meta) {
          // Formatear la fecha usando moment.js en español
          let fecha_creacion = moment(full.fecha_creacion).format('DD MMMM YYYY, HH:mm');

          // Capitalizar la primera letra del mes
          fecha_creacion = capitalizeFirstLetter(fecha_creacion);

          return `
                  <div class="row">
                    <div class="d-flex gap-2 align-items-center col-12">
                      <div class="card-info">
                          <h6 class="mb-0">${fecha_creacion}</h6><br>
                      </div>
                    </div>
                  </div>`;
        }
      }
    ],
    order: [[1, 'asc']],
    displayLength: 15,
    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"row"<"col-sm-12"B>><""t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
    lengthMenu: [15, 30, 50, 75, 100, 150, 200],
    language: {
      paginate: {
        // remove previous & next text from pagination
        previous: 'Anterior',
        next: 'Siguiente'
      }
    }
  });
}

function editarCalificacion(historicoId, calificacion, nombrePaciente, genero){
  // Eliminar modal anterior si existe
  $('#modalEditarCalificacion').remove();

  // Avatar del genero
  const avatar = genero === 'M' ? '/assets/img/avatars/13.png' : '/assets/img/avatars/10.png';

  $('body').append(`
    <div class="modal fade" id="modalEditarCalificacion" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
      <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-primary">
            <h5 class="modal-title text-white mb-4">
              <i class="mdi mdi-playlist-edit me-2 fs-2"></i>
              Editar calificación del historico clínico
            </h5>
          </div>
          <div class="modal-body">
            <div class="d-flex align-items-center mb-4">
              <img src="${avatar}" alt="Avatar del paciente" class="rounded-circle me-3" style="width: 65px; height: 65px; object-fit: cover;">
              <div>
                <div class="text-muted small">Paciente</div>
                <h5 class="mb-0 fw-semibold">${nombrePaciente}</h5>
              </div>
            </div>
            <hr>
            <div class="mb-2">
              <label class="form-label fw-semibold">Calificación</label>
              <div class="text-muted small mb-3">Seleccione la calificación correspondiente al paciente.</div>
            </div>
            <div class="form-check mb-3">
              <input class="form-check-input" type="radio" name="calificacion" id="calificacionApto" value="Apto" ${calificacion === 'Apto' ? 'checked' : ''}>
              <label class="form-check-label w-100" for="calificacionApto">
                <strong>Apto</strong><i class="mdi mdi-check-decagram ms-2" style="color: #28a745;"></i>
                <div class="text-muted small">El paciente cumple con los criterios establecidos.</div>
              </label>
            </div>
            <div class="form-check mb-3">
              <input class="form-check-input" type="radio" name="calificacion" id="calificacionNoApto" value="No apto" ${calificacion === 'No apto' ? 'checked' : ''}>
              <label class="form-check-label w-100" for="calificacionNoApto">
                <strong>No apto</strong><i class="mdi mdi-cancel ms-2" style="color: #dc3545;"></i>
                <div class="text-muted small">El paciente no cumple con los criterios establecidos.</div>
              </label>
            </div>
            <div class="form-check mb-3">
              <input class="form-check-input" type="radio" name="calificacion" id="calificacionCondicionante" value="Apto con condicionante" ${calificacion === 'Apto con condicionante' ? 'checked' : ''}>
              <label class="form-check-label w-100" for="calificacionCondicionante">
                <strong>Apto con condicionante</strong><i class="mdi mdi-alert-circle-outline ms-2" style="color: #ffc107;"></i>
                <div class="text-muted small">El paciente es apto bajo determinadas condiciones.</div>
              </label>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" onclick="cerrarModalEditarCalificacion();">
              <i class="mdi mdi-window-close me-1"></i>
              Cerrar
            </button>
            <button type="button" class="btn btn-success" onclick="guardarCalificacion(${historicoId});">
              <i class="mdi mdi-content-save-outline me-1"></i>
              Guardar cambios
            </button>
          </div>
        </div>
      </div>
    </div>
  `);

  const modal = new bootstrap.Modal(document.getElementById('modalEditarCalificacion'));
  modal.show();
}

function cerrarModalEditarCalificacion(){

  const modalElement = document.getElementById('modalEditarCalificacion');
  const modal = bootstrap.Modal.getInstance(modalElement);

  if (modal) {
    modal.hide();
  }
}

function guardarCalificacion(historicoId) {
  // Obtener la calificación seleccionada
  const calificacion = $('input[name="calificacion"]:checked').val();

  // Validar que haya una selección
  if (!calificacion) {
    alertify.error('Debe seleccionar una calificación.');
    return;
  }

  pantallaCarga('on');

  $.ajax({
    url: '/historia_clinica/api/modificar-calificacion',
    type: 'POST',
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    data: {
      historico_id: historicoId,
      calificacion: calificacion
    },
    success: function(response) {
      if (response.error) {
        alertify.error(response.mensaje || 'No fue posible actualizar la calificación.');
        return;
      }

      alertify.success(response.mensaje || 'La calificación se actualizó correctamente.');

      cerrarModalEditarCalificacion();

      // Recargar la tabla sin regresar a la primera página
      if ($.fn.DataTable.isDataTable('.datatables-basic-filas')) {
        $('.datatables-basic-filas').DataTable().ajax.reload(null, false);
      }
    },
    error: function(xhr) {
      console.error(xhr);
      if (xhr.responseJSON && xhr.responseJSON.mensaje) {
        alertify.error(xhr.responseJSON.mensaje);
      } else {
        alertify.error('Ocurrió un error al actualizar la calificación.');
      }
    },
    complete: function() {
      pantallaCarga('off');
    }
  });
}

function verMovimientosCalificacion(historicoId, nombrePaciente) {
  pantallaCarga('on');
  $.ajax({
    url: '/historia_clinica/api/obtener-movimientos-calificacion',
    type: 'POST',
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    data: {
      historico_id: historicoId
    },
    success: function(response) {
      if (response.error) {
        alertify.error(response.mensaje || 'No fue posible obtener los movimientos.');
        return;
      }

      let movimientos = response.movimientos || '';

      // Función para obtener estilos según la calificación
      function obtenerEstiloCalificacion(calificacion) {
        if (calificacion === 'Apto') {
          return { clase: 'bg-label-success', icono: 'mdi-check-circle-outline', color: 'success' };
        }
        if (calificacion === 'No apto') {
          return { clase: 'bg-label-danger', icono: 'mdi-close-circle-outline', color: 'danger' };
        }
        if (calificacion === 'Apto con condicionante') {
          return { clase: 'bg-label-warning', icono: 'mdi-alert-circle-outline', color: 'warning' };
        }
        return { clase: 'bg-label-secondary', icono: 'mdi-help-circle-outline', color: 'secondary' };
      }

      // Construir timeline
      let timeline = '';

      movimientos.forEach(function(movimiento, index) {
        const anterior = obtenerEstiloCalificacion(movimiento.calificacion_anterior);
        const nueva = obtenerEstiloCalificacion(movimiento.calificacion_nueva);
        const fecha = moment(movimiento.created_at).format('DD/MM/YYYY');
        const hora = moment(movimiento.created_at).format('HH:mm:ss');

        timeline += `
          <div class="movimiento-item">${index < movimientos.length - 1 ? `<div class="movimiento-linea"></div>` : ''}
            <div class="movimiento-punto">
              <i class="mdi ${nueva.icono}"></i>
            </div>
            <div class="movimiento-contenido">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                  <span class="fw-semibold text-dark">Cambio de calificación</span>
                  <div class="text-muted small mt-1">
                    <i class="mdi mdi-calendar-outline me-1"></i>
                    ${fecha}
                    <span class="mx-1">•</span>
                    <i class="mdi mdi-clock-outline me-1"></i>
                    ${hora}
                  </div>
                </div>
                <span class="badge bg-label-${nueva.color}">Movimiento #${movimientos.length - index}</span>
              </div>
              <div class="d-flex align-items-center justify-content-center gap-3 flex-wrap mb-3">
                <div class="calificacion-box ${anterior.clase}">
                  <i class="mdi ${anterior.icono} mdi-22px"></i>
                  <span>${movimiento.calificacion_anterior || 'Sin calificación'}</span>
                </div>
                <div class="movimiento-flecha">
                  <i class="mdi mdi-arrow-right mdi-24px"></i>
                </div>
                <div class="calificacion-box ${nueva.clase}">
                  <i class="mdi ${nueva.icono} mdi-22px"></i>
                  <span>${movimiento.calificacion_nueva}</span>
                </div>
              </div>
              <div class="movimiento-usuario">
                <div class="usuario-icono">
                  <i class="mdi mdi-account-outline"></i>
                </div>
                <div>
                  <div class="small text-muted">Modificado por</div>
                  <div class="fw-semibold">${movimiento.usuario_nombre || 'Sin información'}</div>
                </div>
              </div>
            </div>
          </div>
        `;
      });

      // Estilos del timeline
      const estilos = `
        <style>
          #modalMovimientosCalificacion .modal-content {
            border: 0;
            border-radius: 16px;
            overflow: hidden;
          }
          #modalMovimientosCalificacion .modal-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(67, 89, 113, 0.1);
          }
          #modalMovimientosCalificacion .modal-body {
            padding: 1.5rem;
          }
          .movimientos-timeline {
            position: relative;
            max-height: 55vh;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 5px 10px 10px 5px;
          }
          .movimientos-timeline::-webkit-scrollbar {
            width: 6px;
          }
          .movimientos-timeline::-webkit-scrollbar-track {
            background: transparent;
          }
          .movimientos-timeline::-webkit-scrollbar-thumb {
            background: rgba(105, 108, 255, 0.25);
            border-radius: 10px;
          }
          .movimientos-timeline::-webkit-scrollbar-thumb:hover {
            background: rgba(105, 108, 255, 0.45);
          }
          .movimiento-item {
            position: relative;
            display: flex;
            gap: 18px;
            padding-bottom: 24px;
          }
          .movimiento-linea {
            position: absolute;
            left: 18px;
            top: 40px;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, rgba(105, 108, 255, 0.35), rgba(105, 108, 255, 0.08));
          }
          .movimiento-punto {
            position: relative;
            z-index: 2;
            min-width: 36px;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #fff;
            border: 2px solid rgba(105, 108, 255, 0.35);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            color: #696cff;
          }
          .movimiento-contenido {
            flex: 1;
            background: #fff;
            border: 1px solid rgba(67, 89, 113, 0.12);
            border-radius: 12px;
            padding: 16px 18px;
            transition: all 0.2s ease;
          }
          .movimiento-contenido:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(67, 89, 113, 0.08);
            border-color: rgba(105, 108, 255, 0.25);
          }
          .calificacion-box {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 14px;
            border-radius: 10px;
            font-weight: 600;
            min-width: 190px;
            justify-content: center;
          }
          .movimiento-flecha {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #a1acb8;
          }
          .movimiento-usuario {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-top: 12px;
            border-top: 1px solid rgba(67, 89, 113, 0.08);
          }
          .usuario-icono {
            width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(105, 108, 255, 0.1);
            color: #696cff;
          }
          @media (max-width: 768px) {
            .movimientos-timeline {
              max-height: 60vh;
            }
            .movimiento-contenido {
              padding: 14px;
            }
            .calificacion-box {
              min-width: auto;
              width: 100%;
            }
            .movimiento-flecha {
              transform: rotate(90deg);
            }
          }
        </style>
      `;
      
      // Modal
      let modal = `
        ${estilos}
        <div class="modal fade" id="modalMovimientosCalificacion" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <div>
                  <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;background: rgba(105,108,255,.1);color: #696cff;">
                      <i class="mdi mdi-history mdi-22px"></i>
                    </div>
                    <div>
                      <h5 class="modal-title mb-0">Historial de calificaciones</h5>
                      <small class="text-muted">${nombrePaciente}</small>
                    </div>
                  </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="movimientos-timeline">
                  ${timeline}
                </div>
              </div>
              <div class="modal-footer">
                <span class="text-muted small me-auto">
                  <i class="mdi mdi-information-outline me-1"></i>
                  ${movimientos.length}
                  ${movimientos.length === 1 ? 'movimiento registrado' : 'movimientos registrados'}
                </span>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                  <i class="mdi mdi-close me-1"></i>
                  Cerrar
                </button>
              </div>
            </div>
          </div>
        </div>
      `;

      // Insertar modal
      $('#modalMovimientosCalificacion').remove();
      $('body').append(modal);

      const modalElement = document.getElementById('modalMovimientosCalificacion');
      const modalInstance = new bootstrap.Modal(modalElement);
      modalInstance.show();

      $(modalElement).on('hidden.bs.modal', function () {
        $(this).remove();
      });
    },
    error: function(xhr) {
      console.error(xhr);
      alertify.error(xhr.responseJSON?.mensaje || 'Ocurrió un error al obtener los movimientos.');
    },
    complete: function() {
      pantallaCarga('off');
    }
  });
}
