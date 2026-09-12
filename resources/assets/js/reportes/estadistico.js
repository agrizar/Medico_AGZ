$(document).ready(function () {
  pantallaCarga('off');
  $('.select2').select2();
});




let graficoConsultasColaborador = null;
let graficoConsultasTotalesColaborador = null;




// # Funciones para el reporte y graficos de consultas por colaborador - INICIO
function obtenerConsultasPorColaborador() {
  pantallaCarga('on');
  let csrfToken = $('meta[name="csrf-token"]').attr('content');
  let fechaInicio = $('#filtro-fecha_inicio').val();
  let fechaFin = $('#filtro-fecha_fin').val();

  $.ajax({
    url: '/reportes/api/estadistico-consulta-por-colaborador/obtener',
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': csrfToken
    },
    data: {
      fecha_inicio: fechaInicio,
      fecha_fin: fechaFin
    },
    success: function (response) {
      $('#total-colaboradores').text(response.total_colaboradores);
      $('#total-consultas-periodo').text(response.total_consultas);
      $('#tabla-consultas-colaborador').empty();

      if (!response.datos || response.datos.length === 0) {
        $('#tabla-consultas-colaborador').html(`
            <tr>
                <td colspan="5" class="text-center text-muted py-4">
                    No se encontraron consultas en el periodo seleccionado.
                </td>
            </tr>
        `);
        generarGraficoConsultasColaborador([]);
        return;
      }

      /* Ordenamos los resultados para sacar el top 5 */
      let top5 = [...response.datos]
        .sort(function (a, b) {
          return parseInt(b.numero_consultas || 0) - parseInt(a.numero_consultas || 0);
        })
        .slice(0, 5);

      /* Tabla top 5 */
      top5.forEach(function (item, index) {
        let posicion = index + 1;
        let clasePosicion = '';

        if (posicion === 1) {
          clasePosicion = 'bg-label-danger';
        } else if (posicion === 2) {
          clasePosicion = 'bg-label-warning';
        } else if (posicion === 3) {
          clasePosicion = 'bg-label-info';
        } else if (posicion === 4) {
          clasePosicion = 'bg-label-secondary';
        } else {
          clasePosicion = 'bg-label-success';
        }

        let nombre = item.colaborador_nombre || 'Sin nombre';
        let clave = item.colaborador_clave || 'Sin clave';
        let semana = item.numero_semana || '-';
        let consultas = parseInt(item.numero_consultas || 0);
        let anio = item.anio || '-';

        $('#tabla-consultas-colaborador').append(`
          <tr>
            <td class="text-center">
              <span class="badge ${clasePosicion} rounded-pill px-3 py-2 me-2">#${posicion}</span>
              <span class="fw-semibold text-center">${clave}</span>
            </td>
            <td>
              <div class="d-flex align-items-center">
                <div class="avatar avatar-sm me-3">
                  <span class="avatar-initial rounded-circle bg-label-primary">${nombre.charAt(0).toUpperCase()}</span>
                </div>
                <span class="fw-semibold">${nombre}</span>
              </div>
            </td>
            <td  class="text-center">
              <span class="badge bg-label-secondary">Semana ${semana}</span>
            </td>
            <td class="text-center">
              <span class="badge bg-label-primary px-3 py-2">${consultas} ${consultas === 1 ? 'consulta' : 'consultas'}</span>
            </td>
            <td>
              <span class="text-muted fw-semibold">${anio}</span>
            </td>
          </tr>
        `);
      });

      /* Grafico del top 5 */
      generarGraficoConsultasColaborador(top5);
    },
    error: function (xhr) {
      console.log(xhr);
      alertify.error('Ocurrió un error al consultar la información.');
      pantallaCarga('off');
    }
  });
}

function generarGraficoConsultasColaborador(datos) {
  const fechaInicio = $('#filtro-fecha_inicio').val();
  const fechaFin = $('#filtro-fecha_fin').val();
  const periodo = `Periodo: ${formatearFecha(fechaInicio)} al ${formatearFecha(fechaFin)}`;
  const elemento = document.querySelector('#grafico-consultas-colaborador');

  if (!elemento) {
    console.error('No existe #grafico-consultas-colaborador');
    return;
  }

  if (graficoConsultasColaborador) {
    graficoConsultasColaborador.destroy();
  }

  if (!datos || datos.length === 0) {
    elemento.innerHTML = `<div class="text-center text-muted py-5">No hay información para mostrar</div>`;
    return;
  }

  /* Agrupa por colaborador */
  let agrupados = {};

  datos.forEach(function (item) {
    const clave = item.colaborador_clave;

    if (!agrupados[clave]) {
      agrupados[clave] = {
        clave: clave,
        nombre: item.colaborador_nombre,
        total: 0
      };
    }
    agrupados[clave].total += parseInt(item.numero_consultas || 0);
  });

  /* Ordenamos de mayor a menor */
  let ranking = Object.keys(agrupados)
    .map(function (clave) {
      return agrupados[clave];
    })
    .sort(function (a, b) {
      return b.total - a.total;
    })
    .slice(0, 5);

  /* Categorias y valores */
  let categorias = ranking.map(function (item) {
    return item.nombre;
  });

  let valores = ranking.map(function (item) {
    return item.total;
  });

  /* Crea la grafica */
  graficoConsultasColaborador = new ApexCharts(elemento, {
    chart: {
      type: 'bar',
      height: 380,
      toolbar: {
        show: true,
        tools: {
          download: true,
          selection: false,
          zoom: false,
          zoomin: false,
          zoomout: false,
          pan: false,
          reset: false
        }
      },
      animations: {
        enabled: true,
        easing: 'easeinout',
        speed: 700
      }
    },
    series: [
      {
        name: 'Consultas',
        data: valores
      }
    ],
    plotOptions: {
      bar: {
        horizontal: true,
        borderRadius: 7,
        borderRadiusApplication: 'end',
        barHeight: '55%'
      }
    },
    fill: {
      type: 'gradient',
      gradient: {
        shade: 'light',
        type: 'horizontal',
        shadeIntensity: 0,
        gradientToColors: ['#78b596'],
        inverseColors: false,
        opacityFrom: 0.95,
        opacityTo: 0.85,
        stops: [0, 100]
      }
    },
    colors: ['#006c39'],
    xaxis: {
      categories: categorias,
      min: 0,
      title: {
        text: 'Número de consultas'
      },
      labels: {
        formatter: function (valor) {
          return Math.round(valor);
        }
      },
      axisBorder: {
        show: false
      },
      axisTicks: {
        show: false
      }
    },
    yaxis: {
      labels: {
        maxWidth: 220,
        style: {
          fontSize: '12px',
          fontWeight: 500
        }
      }
    },
    dataLabels: {
      enabled: true,
      formatter: function (valor) {
        return valor;
      },
      offsetX: 8,
      style: {
        fontSize: '12px',
        fontWeight: 600
      }
    },
    tooltip: {
      theme: 'light',
      custom: function ({ dataPointIndex }) {
        const colaborador = ranking[dataPointIndex];
        if (!colaborador) {
          return '';
        }
        return `
          <div style="padding: 12px 15px;min-width: 220px;">
            <div style="font-weight: 600;margin-bottom: 5px;">
              ${colaborador.nombre}
            </div>
            <div style="font-size: 12px;color: #6c757d;margin-bottom: 7px;">
              Colaborador:${colaborador.clave}
            </div>
            <div style="font-size: 14px;font-weight: 600;">
              ${colaborador.total}
              ${colaborador.total == 1 ? 'consulta' : 'consultas'}
            </div>
          </div>
        `;
      }
    },
    grid: {
      borderColor: '#e9ecef',
      strokeDashArray: 4,
      yaxis: {
        lines: {
          show: false
        }
      }
    },
    legend: {
      show: false
    },
    title: {
      text: 'TOP 5 colaboradores con más consultas semanales - Formato grafica',
      align: 'left',
      style: {
        fontSize: '16px',
        fontWeight: 600
      }
    },
    subtitle: {
      text: periodo,
      align: 'left',
      style: {
        fontSize: '12px'
      }
    },
    noData: {
      text: 'No hay información para mostrar'
    }
  });
  graficoConsultasColaborador.render();
  pantallaCarga('off');
}

$('#btn-exportar-consultas-colaborador').click(function (e) {
  e.preventDefault();
  pantallaCarga('on');

  let fechaInicio = $('#filtro-fecha_inicio').val();
  let fechaFin = $('#filtro-fecha_fin').val();

  if (!fechaInicio || !fechaFin) {
    pantallaCarga('off');
    alertify.error('<b>Selecciona un rango de fechas.</b><br>' + 'Debes seleccionar una fecha de inicio y una fecha de fin antes de generar el reporte.');
    return;
  }

  let csrfToken = $('meta[name="csrf-token"]').attr('content');

  $.ajax({
    url: '/reportes/api/estadistico-consulta-por-colaborador/exportar',
    method: 'POST',
    xhrFields: {
      responseType: 'blob'
    },
    headers: {
      'X-CSRF-TOKEN': csrfToken
    },
    data: {
      fecha_inicio: fechaInicio,
      fecha_fin: fechaFin
    },
    success: function (data) {
      pantallaCarga('off');
      let blob = new Blob([data], {
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
      });
      let url = window.URL.createObjectURL(blob);
      let a = document.createElement('a');

      a.href = url;
      a.download = 'Reporte_Consultas_Semanales_Colaborador.xlsx';
      document.body.appendChild(a);
      a.click();
      a.remove();
      window.URL.revokeObjectURL(url);

      alertify.success('Reporte generado y descargado con éxito.');
    },
    error: function (xhr) {
      pantallaCarga('off');
      console.log(xhr);
      alertify.error('Sin resultados para generar el reporte.');
    }
  });
});
// # Funciones para el reporte y graficos de consultas por colaborador - FIN




// # Funciones para el reporte de consultas totales por colaborador - INICIO
function obtenerConsultasTotalesPorColaborador() {
  pantallaCarga('on');
  let csrfToken = $('meta[name="csrf-token"]').attr('content');
  let fechaInicio = $('#filtro-fecha_inicio').val();
  let fechaFin = $('#filtro-fecha_fin').val();

  $.ajax({
    url: '/reportes/api/estadistico-consulta-total-por-colaborador/obtener',
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': csrfToken
    },
    data: {
      fecha_inicio: fechaInicio,
      fecha_fin: fechaFin
    },
    success: function (response) {
      $('#total-colaboradores-totales').text(response.total_colaboradores);
      $('#total-consultas-periodo-totales').text(response.total_consultas);
      $('#tabla-consultas-totales-colaborador').empty();

      if (!response.datos || response.datos.length === 0) {
        $('#tabla-consultas-totales-colaborador').html(`
            <tr>
                <td colspan="3" class="text-center text-muted py-4">
                    No se encontraron consultas en el periodo seleccionado.
                </td>
            </tr>
        `);
        generarGraficoConsultasTotalesColaborador([]);
        return;
      }

      /* Ordenamos los resultados para sacar el top 5 */
      let top5 = [...response.datos]
        .sort(function (a, b) {
          return parseInt(b.numero_consultas || 0) - parseInt(a.numero_consultas || 0);
        })
        .slice(0, 5);

      /* Tabla top 5 */
      top5.forEach(function (item, index) {
        let posicion = index + 1;
        let clasePosicion = '';

        if (posicion === 1) {
          clasePosicion = 'bg-label-danger';
        } else if (posicion === 2) {
          clasePosicion = 'bg-label-warning';
        } else if (posicion === 3) {
          clasePosicion = 'bg-label-info';
        } else if (posicion === 4) {
          clasePosicion = 'bg-label-secondary';
        } else {
          clasePosicion = 'bg-label-success';
        }

        let nombre = item.colaborador_nombre || 'Sin nombre';
        let clave = item.colaborador_clave || 'Sin clave';
        let consultas = parseInt(item.numero_consultas || 0);

        $('#tabla-consultas-totales-colaborador').append(`
          <tr>
            <td class="text-center">
              <span class="badge ${clasePosicion} rounded-pill px-3 py-2 me-2">#${posicion}</span>
              <span class="fw-semibold text-center">${clave}</span>
            </td>
            <td>
              <div class="d-flex align-items-center">
                <div class="avatar avatar-sm me-3">
                  <span class="avatar-initial rounded-circle bg-label-warning">${nombre.charAt(0).toUpperCase()}</span>
                </div>
                <span class="fw-semibold">${nombre}</span>
              </div>
            </td>
            <td class="text-center">
              <span class="badge bg-label-warning px-3 py-2">${consultas} ${consultas === 1 ? 'consulta' : 'consultas'}</span>
            </td>
          </tr>
        `);
      });

      /* Grafico del top 5 */
      generarGraficoConsultasTotalesColaborador(top5);
    },
    error: function (xhr) {
      console.log(xhr);
      alertify.error('Ocurrió un error al consultar la información.');
      pantallaCarga('off');
    }
  });
}

function generarGraficoConsultasTotalesColaborador(datos) {
  const fechaInicio = $('#filtro-fecha_inicio').val();
  const fechaFin = $('#filtro-fecha_fin').val();
  const periodo = `Periodo: ${formatearFecha(fechaInicio)} al ${formatearFecha(fechaFin)}`;
  const elemento = document.querySelector('#grafico-consultas-totales-colaborador');

  if (!elemento) {
    console.error('No existe #grafico-consultas-totales-colaborador');
    return;
  }

  if (graficoConsultasTotalesColaborador) {
    graficoConsultasTotalesColaborador.destroy();
  }

  if (!datos || datos.length === 0) {
    elemento.innerHTML = `<div class="text-center text-muted py-5">No hay información para mostrar</div>`;
    return;
  }

  /* Agrupa por colaborador */
  let agrupados = {};

  datos.forEach(function (item) {
    const clave = item.colaborador_clave;

    if (!agrupados[clave]) {
      agrupados[clave] = {
        clave: clave,
        nombre: item.colaborador_nombre,
        total: 0
      };
    }
    agrupados[clave].total += parseInt(item.numero_consultas || 0);
  });

  /* Ordenamos de mayor a menor */
  let ranking = Object.keys(agrupados)
    .map(function (clave) {
      return agrupados[clave];
    })
    .sort(function (a, b) {
      return b.total - a.total;
    })
    .slice(0, 5);

  /* Categorias y valores */
  let categorias = ranking.map(function (item) {
    return item.nombre;
  });

  let valores = ranking.map(function (item) {
    return item.total;
  });

  /* Crea la grafica */
  graficoConsultasTotalesColaborador = new ApexCharts(elemento, {
    chart: {
      type: 'bar',
      height: 380,
      toolbar: {
        show: true,
        tools: {
          download: true,
          selection: false,
          zoom: false,
          zoomin: false,
          zoomout: false,
          pan: false,
          reset: false
        }
      },
      animations: {
        enabled: true,
        easing: 'easeinout',
        speed: 700
      }
    },
    series: [
      {
        name: 'Consultas',
        data: valores
      }
    ],
    plotOptions: {
      bar: {
        horizontal: true,
        borderRadius: 7,
        borderRadiusApplication: 'end',
        barHeight: '55%'
      }
    },
    fill: {
      type: 'gradient',
      gradient: {
        shade: 'light',
        type: 'horizontal',
        shadeIntensity: 0,
        gradientToColors: ['#f8e0b7'],
        inverseColors: false,
        opacityFrom: 0.95,
        opacityTo: 0.85,
        stops: [0, 100]
      }
    },
    colors: ['#fdb528'],
    xaxis: {
      categories: categorias,
      min: 0,
      title: {
        text: 'Número de consultas'
      },
      labels: {
        formatter: function (valor) {
          return Math.round(valor);
        }
      },
      axisBorder: {
        show: false
      },
      axisTicks: {
        show: false
      }
    },
    yaxis: {
      labels: {
        maxWidth: 220,
        style: {
          fontSize: '12px',
          fontWeight: 500
        }
      }
    },
    dataLabels: {
      enabled: true,
      formatter: function (valor) {
        return valor;
      },
      offsetX: 8,
      style: {
        fontSize: '12px',
        fontWeight: 600
      }
    },
    tooltip: {
      theme: 'light',
      custom: function ({ dataPointIndex }) {
        const colaborador = ranking[dataPointIndex];
        if (!colaborador) {
          return '';
        }
        return `
          <div style="padding: 12px 15px;min-width: 220px;">
            <div style="font-weight: 600;margin-bottom: 5px;">
              ${colaborador.nombre}
            </div>
            <div style="font-size: 12px;color: #6c757d;margin-bottom: 7px;">
              Colaborador:${colaborador.clave}
            </div>
            <div style="font-size: 14px;font-weight: 600;">
              ${colaborador.total}
              ${colaborador.total == 1 ? 'consulta' : 'consultas'}
            </div>
          </div>
        `;
      }
    },
    grid: {
      borderColor: '#e9ecef',
      strokeDashArray: 4,
      yaxis: {
        lines: {
          show: false
        }
      }
    },
    legend: {
      show: false
    },
    title: {
      text: 'TOP 5 colaboradores con más consultas totales - Formato grafica',
      align: 'left',
      style: {
        fontSize: '16px',
        fontWeight: 600
      }
    },
    subtitle: {
      text: periodo,
      align: 'left',
      style: {
        fontSize: '12px'
      }
    },
    noData: {
      text: 'No hay información para mostrar'
    }
  });
  graficoConsultasTotalesColaborador.render();
  pantallaCarga('off');
}

$('#btn-exportar-consultas-totales-colaborador').click(function (e) {
  e.preventDefault();
  pantallaCarga('on');

  let fechaInicio = $('#filtro-fecha_inicio').val();
  let fechaFin = $('#filtro-fecha_fin').val();

  if (!fechaInicio || !fechaFin) {
    pantallaCarga('off');
    alertify.error('<b>Selecciona un rango de fechas.</b><br>' + 'Debes seleccionar una fecha de inicio y una fecha de fin antes de generar el reporte.');
    return;
  }

  let csrfToken = $('meta[name="csrf-token"]').attr('content');

  $.ajax({
    url: '/reportes/api/estadistico-consulta-total-por-colaborador/exportar',
    method: 'POST',
    xhrFields: {
      responseType: 'blob'
    },
    headers: {
      'X-CSRF-TOKEN': csrfToken
    },
    data: {
      fecha_inicio: fechaInicio,
      fecha_fin: fechaFin
    },
    success: function (data) {
      pantallaCarga('off');
      let blob = new Blob([data], {
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
      });
      let url = window.URL.createObjectURL(blob);
      let a = document.createElement('a');

      a.href = url;
      a.download = 'Reporte_Consultas_Totales_Colaborador.xlsx';
      document.body.appendChild(a);
      a.click();
      a.remove();
      window.URL.revokeObjectURL(url);

      alertify.success('Reporte generado y descargado con éxito.');
    },
    error: function (xhr) {
      pantallaCarga('off');
      console.log(xhr);
      alertify.error('Sin resultados para generar el reporte.');
    }
  });
});
// # Funciones para el reporte de consultas totales por colaborador - FIN




// # Funciones para el reporte de media, mediana y moda - INICIO
function obtenerEstadisticasConsultasColaborador() {
  pantallaCarga('on');
  let csrfToken = $('meta[name="csrf-token"]').attr('content');
  let fechaInicio = $('#filtro-fecha_inicio').val();
  let fechaFin = $('#filtro-fecha_fin').val();

  $.ajax({
    url: '/reportes/api/estadistico-consulta-media-mediana-moda/obtener',
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': csrfToken
    },
    data: {
      fecha_inicio: fechaInicio,
      fecha_fin: fechaFin
    },

    success: function (response) {
      $('#total-colaboradores-estadisticas').text(
        response.total_colaboradores || 0
      );

      $('#total-consultas-estadisticas').text(
        response.total_consultas || 0
      );

      $('#tabla-estadisticas-colaborador').empty();

      if (!response.datos || response.datos.length === 0) {
        $('#tabla-estadisticas-colaborador').html(`<tr><td colspan="6" class="text-center text-muted py-4">No se encontraron consultas en el periodo seleccionado.</td></tr>`);
        $('#media-consultas').text('0.00');
        $('#mediana-consultas').text('0.00');
        $('#moda-consultas').text('0');

        pantallaCarga('off');
        return;
      }

      let top5 = [...response.datos].sort(function (a, b) {
        return parseInt(b.total_consultas || 0) - parseInt(a.total_consultas || 0);
      }).slice(0, 5);

      let medias = response.datos.map(function (item) {
        return parseFloat(item.media || 0);
      });

      let sumaMedias = medias.reduce(function (total, valor) {
        return total + valor;
      }, 0);

      let mediaGeneral = sumaMedias / medias.length;

      $('#media-consultas').text(mediaGeneral.toFixed(2));

      let medianas = response.datos.map(function (item) {
        return parseFloat(item.mediana || 0);
      });

      let modas = response.datos.map(function (item) {
        return parseFloat(item.moda || 0);
      });

      medianas.sort(function (a, b) {
        return a - b;
      });

      let medianaGeneral;
      let mitad = Math.floor(medianas.length / 2);

      if (medianas.length % 2 === 0) {
        medianaGeneral = (medianas[mitad - 1] + medianas[mitad]) / 2;
      } else {
        medianaGeneral = medianas[mitad];
      }

      let frecuenciaModas = {};

      modas.forEach(function (valor) {
        frecuenciaModas[valor] = (frecuenciaModas[valor] || 0) + 1;
      });

      let modaGeneral = modas[0] || 0;
      let mayorFrecuencia = 0;

      Object.keys(frecuenciaModas).forEach(function (valor) {
        if (frecuenciaModas[valor] > mayorFrecuencia) {
          mayorFrecuencia = frecuenciaModas[valor];
          modaGeneral = parseFloat(valor);
        }
      });

      $('#mediana-consultas').text(medianaGeneral.toFixed(2));
      $('#moda-consultas').text(modaGeneral.toFixed(2));

      top5.forEach(function (item, index) {
        let posicion = index + 1;
        let clasePosicion = '';

        if (posicion === 1) {
          clasePosicion = 'bg-label-danger';
        } else if (posicion === 2) {
          clasePosicion = 'bg-label-warning';
        } else if (posicion === 3) {
          clasePosicion = 'bg-label-info';
        } else if (posicion === 4) {
          clasePosicion = 'bg-label-secondary';
        } else {
          clasePosicion = 'bg-label-success';
        }

        let nombre = item.colaborador_nombre || 'Sin nombre';
        let clave = item.colaborador_clave || 'Sin clave';
        let media = parseFloat(item.media || 0);
        let mediana = parseFloat(item.mediana || 0);
        let moda = parseFloat(item.moda || 0);
        let totalConsultas = parseInt(item.total_consultas || 0);

        $('#tabla-estadisticas-colaborador').append(`
          <tr>
            <td class="text-center">
              <span class="badge ${clasePosicion} rounded-pill px-3 py-2 me-2">
                #${posicion}
              </span>
              <span class="fw-semibold">
                ${clave}
              </span>
            </td>
            <td>
              <div class="d-flex align-items-center">
                <div class="avatar avatar-sm me-3">
                  <span class="avatar-initial rounded-circle bg-label-info">
                    ${nombre.charAt(0).toUpperCase()}
                  </span>
                </div>
                <span class="fw-semibold">
                  ${nombre}
                </span>
              </div>
            </td>
            <td class="text-center">
              <span class="badge bg-label-primary px-3 py-2">
                ${media.toFixed(2)}
              </span>
            </td>
            <td class="text-center">
              <span class="badge bg-label-info px-3 py-2">
                ${mediana.toFixed(2)}
              </span>
            </td>
            <td class="text-center">
              <span class="badge bg-label-success px-3 py-2">
                ${moda}
              </span>
            </td>
            <td class="text-center">
              <span class="badge bg-label-warning px-3 py-2">
                ${totalConsultas}
                ${totalConsultas === 1 ? 'consulta' : 'consultas'}
              </span>
            </td>
          </tr>
        `);
      });
    },
    error: function (xhr) {
      console.log(xhr);
      alertify.error('Ocurrió un error al consultar la información.');
    },
    complete: function () {
      pantallaCarga('off');
    }
  });
}

$('#btn-exportar-estadisticas-colaborador').click(function (e) {
  e.preventDefault();
  pantallaCarga('on');

  let fechaInicio = $('#filtro-fecha_inicio').val();
  let fechaFin = $('#filtro-fecha_fin').val();

  if (!fechaInicio || !fechaFin) {
    pantallaCarga('off');
    alertify.error('Selecciona la fecha de inicio y la fecha fin.');
    return;
  }

  if (fechaInicio > fechaFin) {
    pantallaCarga('off');
    alertify.error('La fecha de inicio no puede ser mayor que la fecha fin.');
    return;
  }

  let csrfToken = $('meta[name="csrf-token"]').attr('content');

  $.ajax({
    url: '/reportes/api/estadistico-consulta-media-mediana-moda/exportar',
    method: 'POST',
    xhrFields: {
      responseType: 'blob'
    },
    headers: {
      'X-CSRF-TOKEN': csrfToken
    },
    data: {
      fecha_inicio: fechaInicio,
      fecha_fin: fechaFin
    },
    success: function (data) {
      let blob = new Blob([data], {
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
      });
      let url = window.URL.createObjectURL(blob);
      let a = document.createElement('a');

      a.href = url;
      a.download = 'Reporte_Media_Mediana_Moda.xlsx';
      document.body.appendChild(a);
      a.click();
      a.remove();
      window.URL.revokeObjectURL(url);
      alertify.success('Reporte generado y descargado con éxito.');
    },
    error: function (xhr) {
      console.log(xhr);
      alertify.error('Sin resultados para generar el reporte.');
    },
    complete: function () {
      pantallaCarga('off');
    }
  });
});
// # Funciones para el reporte de media, mediana y moda - INICIO




// # Funciones para el medicamento mas recetado - INICIO
function obtenerMedicamentosMasRecetados() {
  pantallaCarga('on');
  let csrfToken = $('meta[name="csrf-token"]').attr('content');
  let fechaInicio = $('#filtro-fecha_inicio').val();
  let fechaFin = $('#filtro-fecha_fin').val();

  $.ajax({
    url: '/reportes/api/estadistico-medicamento-mas-recetado/obtener',
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': csrfToken
    },
    data: {
      fecha_inicio: fechaInicio,
      fecha_fin: fechaFin
    },

    success: function (response) {
      $('#total-medicamentos').text(
        response.total_medicamentos || 0
      );

      $('#total-recetas-medicamentos').text(
        response.total_recetas || 0
      );

      $('#tabla-medicamentos-mas-recetados').empty();

      if (!response.datos || response.datos.length === 0) {
        $('#medicamento-mas-recetado').text('-');
        $('#cantidad-medicamento-mas-recetado').text('0 recetas');
        $('#tabla-medicamentos-mas-recetados').html(`
          <tr>
            <td colspan="4" class="text-center text-muted py-5">
              <i class="mdi mdi-pill-off-outline d-block mb-2"
                 style="font-size: 32px;"></i>
              <div class="fw-semibold">
                No se encontraron medicamentos
              </div>
              <small>
                No existen recetas durante el periodo seleccionado
              </small>
            </td>
          </tr>
        `);

        return;
      }

      let medicamentoTop = response.datos[0];

      $('#medicamento-mas-recetado').text(
        medicamentoTop.medicamento || 'Sin medicamento'
      );

      $('#cantidad-medicamento-mas-recetado').text(
        `${parseInt(medicamentoTop.total_recetas || 0)} ${
          parseInt(medicamentoTop.total_recetas || 0) === 1 ? 'receta' : 'recetas'
        }`
      );

      let top5 = [...response.datos].sort(function (a, b) {
        return parseInt(b.total_recetas || 0) - parseInt(a.total_recetas || 0);
      }).slice(0, 5);

      top5.forEach(function (item, index) {
        let posicion = index + 1;
        let clasePosicion = '';

        if (posicion === 1) {
          clasePosicion = 'bg-label-danger';
        } else if (posicion === 2) {
          clasePosicion = 'bg-label-warning';
        } else if (posicion === 3) {
          clasePosicion = 'bg-label-info';
        } else if (posicion === 4) {
          clasePosicion = 'bg-label-secondary';
        } else {
          clasePosicion = 'bg-label-success';
        }

        let medicamento = item.medicamento || 'Sin medicamento';
        let codigo = item.medicamento_codigo || 'Sin código';
        let recetas = parseInt(item.total_recetas || 0);
        let porcentaje = parseFloat(item.porcentaje || 0);

        $('#tabla-medicamentos-mas-recetados').append(`
          <tr>
            <td class="text-center">
              <span class="badge ${clasePosicion} rounded-pill px-3 py-2">
                #${posicion}
              </span>
            </td>
            <td>
              <div class="d-flex align-items-center">
                <div class="avatar avatar-sm me-3">
                  <span class="avatar-initial rounded-circle bg-label-success">
                    <span class="mdi mdi-pill"></span>
                  </span>
                </div>
                <div>
                  <span class="fw-semibold d-block">
                    ${medicamento}
                  </span>
                  <small class="text-muted">
                    Código: ${codigo}
                  </small>
                </div>
              </div>
            </td>
            <td class="text-center">
              <span class="badge bg-label-warning px-3 py-2">
                ${recetas}
                ${recetas === 1 ? 'receta' : 'recetas'}
              </span>
            </td>
            <td class="text-center">
              <span class="badge bg-label-primary px-3 py-2">
                ${porcentaje.toFixed(2)}%
              </span>
            </td>
          </tr>
        `);
      });
    },
    error: function (xhr) {
      console.log(xhr);
      alertify.error(
        'Ocurrió un error al consultar los medicamentos.'
      );
    },
    complete: function () {
      pantallaCarga('off');
    }
  });
}

$('#btn-exportar-medicamentos').click(function (e) {
  e.preventDefault();

  pantallaCarga('on');

  let fechaInicio = $('#filtro-fecha_inicio').val();
  let fechaFin = $('#filtro-fecha_fin').val();

  if (!fechaInicio || !fechaFin) {
    pantallaCarga('off');
    alertify.error('Selecciona la fecha de inicio y la fecha fin.');
    return;
  }

  if (fechaInicio > fechaFin) {
    pantallaCarga('off');
    alertify.error('La fecha de inicio no puede ser mayor que la fecha fin.');
    return;
  }

  let csrfToken = $('meta[name="csrf-token"]').attr('content');

  $.ajax({
    url: '/reportes/api/estadistico-medicamento-mas-recetado/exportar',
    method: 'POST',
    xhrFields: {
      responseType: 'blob'
    },
    headers: {
      'X-CSRF-TOKEN': csrfToken
    },
    data: {
      fecha_inicio: fechaInicio,
      fecha_fin: fechaFin
    },

    success: function (data) {

      let blob = new Blob([data], {
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
      });

      let url = window.URL.createObjectURL(blob);

      let a = document.createElement('a');
      a.href = url;
      a.download = 'Reporte_Medicamentos_Mas_Recetados.xlsx';

      document.body.appendChild(a);
      a.click();
      a.remove();

      window.URL.revokeObjectURL(url);

      alertify.success(
        'Reporte de medicamentos generado y descargado con éxito.'
      );
    },

    error: function (xhr) {
      console.log(xhr);
      alertify.error(
        'No se encontraron medicamentos para generar el reporte.'
      );
    },

    complete: function () {
      pantallaCarga('off');
    }
  });
});
// # Funciones para el medicamento mas recetado - FIN




// # Funciones para los motivos de la visita mas frecuentes - INICIO
function obtenerMotivosVisitaMasFrecuentes() {
  pantallaCarga('on');
  let csrfToken = $('meta[name="csrf-token"]').attr('content');
  let fechaInicio = $('#filtro-fecha_inicio').val();
  let fechaFin = $('#filtro-fecha_fin').val();

  $.ajax({
    url: '/reportes/api/estadistico-motivos-visita-frecuentes/obtener',
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': csrfToken
    },
    data: {
      fecha_inicio: fechaInicio,
      fecha_fin: fechaFin
    },
    success: function (response) {
      $('#total-motivos-visita').text(response.total_motivos || 0);
      $('#total-consultas-motivos').text(response.total_consultas || 0);
      $('#tabla-motivos-visita').empty();
      
      if (!response.datos || response.datos.length === 0) {
        $('#motivo-visita-mas-frecuente').text('-');
        $('#cantidad-motivo-visita-mas-frecuente').text('0 consultas');
        $('#tabla-motivos-visita').html(`
          <tr>
            <td colspan="5" class="text-center text-muted py-5">
              <i class="mdi mdi-clipboard-off-outline d-block mb-2" style="font-size: 32px;"></i>
              <div class="fw-semibold">No se encontraron motivos de visita</div>
              <small>No existen consultas durante el periodo seleccionado</small>
            </td>
          </tr>
        `);
        return;
      }
      
      let motivoTop = response.datos[0];
      let totalMotivoTop = parseInt(motivoTop.total_consultas || 0);

      $('#motivo-visita-mas-frecuente').text(motivoTop.motivo_visita || 'Sin motivo');
      $('#cantidad-motivo-visita-mas-frecuente').text(`${totalMotivoTop} ${totalMotivoTop === 1? 'consulta' : 'consultas'}`);

      let top5 = [...response.datos].sort(function (a, b) {
        return parseInt(b.total_consultas || 0) - parseInt(a.total_consultas || 0);
      }).slice(0, 5);

      top5.forEach(function (item, index) {
        let posicion = index + 1;
        let clasePosicion = '';

        if (posicion === 1) {
          clasePosicion = 'bg-label-danger';
        } else if (posicion === 2) {
          clasePosicion = 'bg-label-warning';
        } else if (posicion === 3) {
          clasePosicion = 'bg-label-info';
        } else if (posicion === 4) {
          clasePosicion = 'bg-label-secondary';
        } else {
          clasePosicion = 'bg-label-success';
        }

        let motivo = item.motivo_visita || 'Sin motivo';
        let consultas = parseInt(item.total_consultas || 0);
        let porcentaje = parseFloat(item.porcentaje || 0);

        $('#tabla-motivos-visita').append(`
          <tr>
            <td class="text-center">
              <span class="badge ${clasePosicion} rounded-pill px-3 py-2">
                #${posicion}
              </span>
            </td>
            <td>
              <div class="d-flex align-items-center">
                <div class="avatar avatar-sm me-3">
                  <span class="avatar-initial rounded-circle bg-label-warning">
                    <span class="mdi mdi-stethoscope"></span>
                  </span>
                </div>
                <span class="fw-semibold">
                  ${motivo}
                </span>
              </div>
            </td>
            <td class="text-center">
              <span class="badge bg-label-warning px-3 py-2">
                ${consultas}
                ${consultas === 1 ? 'consulta' : 'consultas'}
              </span>
            </td>
            <td class="text-center">
              <span class="badge bg-label-primary px-3 py-2">
                ${porcentaje.toFixed(2)}%
              </span>
            </td>
          </tr>
        `);
      });
    },
    error: function (xhr) {
      console.log(xhr);
      alertify.error('Ocurrió un error al consultar los motivos de visita.');
    },
    complete: function () {
      pantallaCarga('off');
    }
  });
}

$('#btn-exportar-motivos-visita').click(function (e) {
  e.preventDefault();
  pantallaCarga('on');

  let fechaInicio = $('#filtro-fecha_inicio').val();
  let fechaFin = $('#filtro-fecha_fin').val();

  if (!fechaInicio || !fechaFin) {
    pantallaCarga('off');
    alertify.error('Selecciona la fecha de inicio y la fecha fin.');
    return;
  }

  if (fechaInicio > fechaFin) {
    pantallaCarga('off');
    alertify.error('La fecha de inicio no puede ser mayor que la fecha fin.');
    return;
  }

  let csrfToken = $('meta[name="csrf-token"]').attr('content');

  $.ajax({
    url: '/reportes/api/estadistico-motivos-visita-frecuentes/exportar',
    method: 'POST',
    xhrFields: {
      responseType: 'blob'
    },
    headers: {
      'X-CSRF-TOKEN': csrfToken
    },
    data: {
      fecha_inicio: fechaInicio,
      fecha_fin: fechaFin
    },
    success: function (data) {
      let blob = new Blob([data], {
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
      });
      let url = window.URL.createObjectURL(blob);
      let a = document.createElement('a');

      a.href = url;
      a.download = 'Reporte_Motivos_Visita_' + fechaInicio + '_' + fechaFin +'.xlsx';
      document.body.appendChild(a);
      a.click();
      a.remove();
      window.URL.revokeObjectURL(url);
      alertify.success('Reporte de motivos de visita generado y descargado con éxito.');
    },
    error: function (xhr) {
      console.log(xhr);
      alertify.error('No se encontraron datos para generar el reporte.');
    },
    complete: function () {
      pantallaCarga('off');
    }
  });
});
// # Funciones para los motivos de la visita mas frecuentes - FIN




// # Funciones generales
function formatearFecha(fecha) {
  if (!fecha) {
    return '';
  }
  const partes = fecha.split('-');
  return `${partes[2]}/${partes[1]}/${partes[0]}`;
}




// # Botones generales
$('#btn-generar-reporte').on('click', function () {
  let fechaInicio = $('#filtro-fecha_inicio').val();
  let fechaFin = $('#filtro-fecha_fin').val();

  if (!fechaInicio || !fechaFin) {
    alertify.error('Selecciona la fecha de inicio y la fecha fin.');
    pantallaCarga('off');
    return;
  }

  if (fechaInicio > fechaFin) {
    alertify.error('La fecha de inicio no puede ser mayor que la fecha fin.');
    pantallaCarga('off');
    return;
  }

  obtenerConsultasPorColaborador();
  obtenerConsultasTotalesPorColaborador();
  obtenerEstadisticasConsultasColaborador();
  obtenerMedicamentosMasRecetados();
  obtenerMotivosVisitaMasFrecuentes();
});
