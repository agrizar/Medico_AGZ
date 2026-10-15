$(document).ready(function () {
  // Ocultamos la pantalla de carga cuando la pantalla termino de cargar todo el contenido
  pantallaCarga('off');

  $('.select2').select2();

  formateaCampoNumeroEmpleado("[name='filtro[paciente_numero_gafete]']");
  formateaCampoCelular("[name='paciente[celular]']");
  formateaCampoEdad("[name='paciente[edad]']");
  formateaCampoCURP("[name='paciente[curp]']");

  // Inicializa el formulario de filtros para buscar empleado
  const formFiltroPaciente = document.getElementById('form-filtros_buscar_paciente');
  const validacionesFiltroPaciente = FormValidation.formValidation(formFiltroPaciente, {
    fields: {
      'filtro[paciente_numero_gafete]': {
        validators: {
          notEmpty: {
            message: 'Para buscar llena este campo'
          },
          stringLength: {
            min: 1,
            message: 'El tamaño minimo es de 1 caracteres'
          },
          regexp: {
            regexp: /^[A-Za-zÑñáéíóúÁÉÍÓÚ0-9\s\$.,()¿?¡!'/_#:;]*$/,
            message: 'Solo se aceptan letras, números y algunos caracteres especiales.'
          }
        }
      }
    },
    plugins: {
      trigger: new FormValidation.plugins.Trigger(),
      bootstrap5: new FormValidation.plugins.Bootstrap5({
        eleValidClass: '',
        rowSelector: '.row'
      })
    }
  });

  // Inicializa el formulario de registro de paciente
  const formRegistroPaciente = document.getElementById('form-registrar_nuevo_paciente');
  const validacionesInputsPaciente = FormValidation.formValidation(formRegistroPaciente, {
    fields: {
      'paciente[gafete]': {
        validators: {
          notEmpty: {
            message: 'Este campo es obligatorio'
          },
          stringLength: {
            min: 1,
            message: 'El tamaño minimo es de 1 caracteres'
          },
          regexp: {
            regexp: /^[0-9]+$/,
            message: 'Solo se aceptan números.'
          }
        }
      },
      'paciente[nombre]': {
        validators: {
          notEmpty: {
            message: 'Este campo es obligatorio'
          },
          stringLength: {
            min: 3,
            message: 'El tamaño minimo es de 3 caracteres'
          },
          regexp: {
            message: 'Solo se aceptan letras y espacios.'
          }
        }
      },
      'paciente[genero]': {
        validators: {
          notEmpty: {
            message: 'Este campo es obligatorio'
          }
        }
      },
      'paciente[edad]': {
        validators: {
          notEmpty: {
            message: 'Este campo es obligatorio'
          },
          regexp: {
            regexp: /^(?:[1-9][0-9]|1[01][0-9]|120)$/,
            message: 'La edad debe ser un número entero entre 10 y 120.'
          }
        }
      },
      'paciente[curp]': {
        validators: {
          notEmpty: {
            message: 'Este campo es obligatorio'
          },
          stringLength: {
            min: 18,
            max: 18,
            message: ' '
          },
          regexp: {
            regexp: /^[A-Z0-9]{18}$/,
            message: 'El CURP no es válido. Asegúrese de que tenga 18 caracteres con el formato adecuado.'
          }
        }
      },
      'paciente[paciente_empresa_id]': {
        validators: {
          notEmpty: {
            message: 'Este campo es obligatorio'
          }
        }
      },
      'paciente[paciente_unidad_negocio_id]': {
        validators: {
          notEmpty: {
            message: 'Este campo es obligatorio'
          }
        }
      },
      'paciente[paciente_area_id]': {
        validators: {
          notEmpty: {
            message: 'Este campo es obligatorio'
          }
        }
      },
      'paciente[paciente_subarea_id]': {
        validators: {
          notEmpty: {
            message: 'Este campo es obligatorio'
          }
        }
      }
    },
    plugins: {
      trigger: new FormValidation.plugins.Trigger(),
      bootstrap5: new FormValidation.plugins.Bootstrap5({
        eleValidClass: '',
        rowSelector: '.row'
      })
    }
  });

  // Botón para buscar un paciente
  $('#boton-buscar_paciente').click(async function (e) {
    // Reseteamos las validaciones de los campos del formulario
    validacionesInputsPaciente.resetForm(true);
    $('#paciente_id').val('');

    var boton = $(this); // Referencia al botón
    boton.prop('disabled', true); // Deshabilitar el botón para evitar el doble clic

    const camposFormulario = validacionesFiltroPaciente.getFields(); // Variable para obtener los campos del formulario
    let campoInvalidoEncontrado = false; // Inicializamos la variable que cambiara cuando este un campo mal llenado

    // Iterar sobre cada campo en el formulario del registro del paciente
    for (const nameCampo in camposFormulario) {
      // Solo proceder si no se ha encontrado otro campo inválido
      if (camposFormulario.hasOwnProperty(nameCampo) && !campoInvalidoEncontrado) {
        const estadoCampo = await validacionesFiltroPaciente.validateField(nameCampo);

        if (estadoCampo === 'Invalid') {
          const primerCampoInvalido = $(`[name="${nameCampo}"]`);
          if (primerCampoInvalido.length) {
            primerCampoInvalido.focus();
            campoInvalidoEncontrado = true;
          }
          break;
        }
      }
    }

    // Si no hay campos invalidos entra
    if (!campoInvalidoEncontrado) {
      // Obtenemos los datos del formulario de filtros
      var formData = $('#form-filtros_buscar_paciente').serialize();
      // CSRF Token al encabezado de la solicitud
      var csrfToken = $('meta[name="csrf-token"]').attr('content');

      // Obtiene valores del formulario de filtros
      var numero_gafete = $('#filtro-paciente_numero_gafete').val().trim();

      // Restablecer el formulario de nuevo paciente y eliminar el modo lectura'
      $('#form-registrar_nuevo_paciente')[0].reset(); // Esto restablece todos los campos
      $('#form-registrar_nuevo_paciente').find('[readonly]').prop('readonly', false);
      $('#form-registrar_nuevo_paciente').find('[disabled]').prop('disabled', false);

      $('#paciente-paciente_empresa_id').val('').trigger('change');
      $('#paciente-paciente_unidad_negocio_id').val('').trigger('change');
      $('#paciente-paciente_area_id').val('').trigger('change');
      $('#paciente-paciente_subarea_id').val('').trigger('change');

      $('#boton-registrar_paciente').attr('style', ''); // Elimina el estilo inline
      $('#boton-continuar_valoracion').get(0).style.setProperty('display', 'none', 'important'); // Aplica display:block !important

      // Validar si están vacíos
      if (!numero_gafete) {
        alertify.error('Para buscar ingresa el numero de empleado.');
        boton.prop('disabled', false);
        return;
      }

      pantallaCarga('on');

      // Enviamos la solicitud AJAX para buscar el paciente
      $.ajax({
        url: '/pacientes/api/consultar',
        method: 'POST',
        data: formData,
        headers: {
          'X-CSRF-TOKEN': csrfToken
        },
        success: function (response) {
          pantallaCarga('off');

          if (response.error == false) {
            $('#contenedor-datos_registro_paciente').removeClass('d-none');
            $('#contenedor-sin_datos_encontrados').addClass('d-none');
            if (response.bandera == 201) {
              // Si la bandera es 1 es porque el paciente ya ha tenido consultas
              if (response.body != []) {
                // Poner los valores en el formulario
                $('#paciente-gafete').val(response.body.codigo).prop('readonly', true);
                $('#paciente-nombre').val(response.body.nombre).prop('readonly', true);
                $('#paciente-apellido_paterno').val(response.body.ap_paterno).prop('readonly', true);
                $('#paciente-apellido_materno').val(response.body.ap_materno).prop('readonly', true);
                $('#paciente-genero').val(response.body.sexo).prop('disabled', true).trigger('change');
                $('#paciente-celular').val(response.body.celular).prop('readonly', true);
                $('#paciente-edad').val(response.body.edad).prop('readonly', true);
                $('#paciente-curp').val(response.body.curp).prop('readonly', true);
                $('#paciente-paciente_empresa_id')
                  .val(response.body.empresa_id)
                  .trigger('change')
                  .prop('disabled', true);
                $('#paciente-paciente_unidad_negocio_id')
                  .val(response.body.unidad_negocio_id)
                  .trigger('change')
                  .prop('disabled', true);
                $('#paciente-paciente_area_id').val(response.body.area_id).trigger('change').prop('disabled', true);
                $('#paciente-paciente_subarea_id')
                  .val(response.body.subarea_id)
                  .trigger('change')
                  .prop('disabled', true);

                $('#boton-continuar_valoracion').attr('style', ''); // Elimina el estilo inline
                $('#boton-registrar_paciente').get(0).style.setProperty('display', 'none', 'important'); // Aplica display:block !important

                $('#paciente_id').val(response.body.id);
                alertify.success(response.msg);
              } else {
                $('#contenedor-datos_registro_paciente').addClass('d-none');
                $('#contenedor-sin_datos_encontrados').removeClass('d-none');
                alertify.error(response.msg);
              }
            } else {
              // Si body es diferente de null es porque si encontró resultados en fortia
              if (response.body != []) {
                response.body = response.body[0];
                // Poner los valores en el formulario
                $('#paciente-gafete').val(response.body.employee_id).prop('readonly', true);
                $('#paciente-nombre').val(response.body.employee_name).prop('readonly', true);
                $('#paciente-apellido_paterno').val(response.body.employee_last_name).prop('readonly', true);
                $('#paciente-apellido_materno').val(response.body.employee_second_last_name).prop('readonly', true);
                $('#paciente-genero').val(response.body.gender).prop('disabled', true).trigger('change');
                $('#paciente-curp').val(response.body.curp).prop('readonly', true);

                alertify.success(response.msg);
              } else {
                alertify.error(response.msg);
                $('#contenedor-datos_registro_paciente').addClass('d-none');
                $('#contenedor-sin_datos_encontrados').removeClass('d-none');
              }
            }
          } else {
            $('#contenedor-datos_registro_paciente').addClass('d-none');
            $('#contenedor-sin_datos_encontrados').removeClass('d-none');
            alertify.error(response.msg);
          }
        },
        error: function () {
          $('#contenedor-datos_registro_paciente').addClass('d-none');
          $('#contenedor-sin_datos_encontrados').removeClass('d-none');
          alertify.error('Ocurrió un error al buscar los pacientes.');
        },
        complete: function () {
          boton.prop('disabled', false);
        }
      });
    } else {
      boton.prop('disabled', false);
    }
  });

  // Botón para registrar un paciente
  $('#boton-registrar_paciente').click(async function (e) {
    var boton = $(this); // Referencia al botón
    boton.prop('disabled', true); // Deshabilitar el botón para evitar el doble clic

    const camposFormulario = validacionesInputsPaciente.getFields(); // Variable para obtener los campos del formulario
    let campoInvalidoEncontrado = false; // Inicializamos la variable que cambiara cuando este un campo mal llenado

    // Iterar sobre cada campo en el formulario del registro del paciente
    for (const nameCampo in camposFormulario) {
      // Solo proceder si no se ha encontrado otro campo inválido
      if (camposFormulario.hasOwnProperty(nameCampo) && !campoInvalidoEncontrado) {
        const estadoCampo = await validacionesInputsPaciente.validateField(nameCampo);

        if (estadoCampo === 'Invalid') {
          const primerCampoInvalido = $(`[name="${nameCampo}"]`);
          if (primerCampoInvalido.length) {
            primerCampoInvalido.focus();
            campoInvalidoEncontrado = true;
          }
          break;
        }
      }
    }

    // Si no hay campos invalidos entra
    if (!campoInvalidoEncontrado) {
      // Pregunta si se desea guardar el registro del paciente
      alertify.confirm(
        '¿Desea guardar el paciente?',
        function (e, ui) {
          guardarRegistroPaciente();
          boton.prop('disabled', false);
        },
        function (e, ui) {
          alertify.error('Operación cancelada, no guardado');
          boton.prop('disabled', false);
        }
      );
    } else {
      boton.prop('disabled', false);
    }
  });

  // Funcion para guardar el registro de un paciente
  function guardarRegistroPaciente() {
    // Obtenemos el formulario de registro de paciente
    var formData = $('#form-registrar_nuevo_paciente').serialize();
    // CSRF Token al encabezado de la solicitud
    var csrfToken = $('meta[name="csrf-token"]').attr('content');

    var paciente_genero = $('#paciente-genero').val();
    formData += '&paciente[genero]=' + encodeURIComponent(paciente_genero);

    pantallaCarga('on');

    $.ajax({
      url: '/pacientes/api/registrar',
      method: 'POST',
      data: formData,
      headers: {
        'X-CSRF-TOKEN': csrfToken
      },
      success: function (response) {
        pantallaCarga('off');
        if (response.error == false) {
          alertify.success(response.msg);

          $('#paciente-gafete').prop('readonly', true);
          $('#paciente-nombre').prop('readonly', true);
          $('#paciente-apellido_paterno').prop('readonly', true);
          $('#paciente-apellido_materno').prop('readonly', true);
          $('#paciente-genero').prop('disabled', true).trigger('change');
          $('#paciente-celular').prop('readonly', true);
          $('#paciente-edad').prop('readonly', true);
          $('#paciente-curp').prop('readonly', true);
          $('#paciente-paciente_empresa_id').prop('disabled', true).trigger('change');
          $('#paciente-paciente_unidad_negocio_id').prop('disabled', true).trigger('change');
          $('#paciente-paciente_area_id').prop('disabled', true).trigger('change');
          $('#paciente-paciente_subarea_id').prop('disabled', true).trigger('change');

          $('#boton-continuar_valoracion').attr('style', ''); // Elimina el estilo inline
          $('#boton-registrar_paciente').get(0).style.setProperty('display', 'none', 'important'); // Aplica display:block !important

          $('#contenedor_buscador_empleado').get(0).style.setProperty('display', 'none', 'important');

          $('#paciente_id').val(response.paciente_id);
        } else {
          alertify.error(response.msg);
        }
      },
      error: function () {
        // Si ocurre un error en la solicitud
        console.log('Error en la solicitud');
        alertify.error(
          '¡Lo sentimos! No fue posible guardar el registro. Inténtalo de nuevo y si el problema persiste contacta con el equipo de desarrollo.'
        );
      }
    });
  }

  // Boton para ir a la vista de valoracion
  $('#boton-continuar_valoracion').click(function (e) {
    e.preventDefault();
    var pacienteId = $('#paciente_id').val();
    window.location.href = '/pacientes/registrar-valoracion?paciente_id=' + encodeURIComponent(pacienteId);
  });
});
