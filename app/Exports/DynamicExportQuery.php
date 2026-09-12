<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DynamicExportQuery implements FromQuery, WithHeadings, WithEvents
{
  protected $query;
  protected $headings;

  public function __construct($query, $headings)
  {
    $this->query    = $query;
    $this->headings = $headings; # este array sirve para saber qué columnas mergear
  }

  public function query(){ return $this->query; }

  public function headings(): array { return $this->headings; }


  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        $sheet = $event->sheet->getDelegate(); # hoja de cálculo
        $sheet->insertNewRowBefore(1, 2); # insertar 2 filas al inicio para info de reporte

        $user = auth()->user();
        $userName = $user ? $user->nombre.' '.$user->apellido_paterno.' '.$user->apellido_materno : 'Usuario desconocido';
        $sheet->setCellValue('A1', 'Reporte generado por: ' . $userName); # renglon con nombre de usuario
        $sheet->setCellValue('A2', 'Fecha y hora: ' . now()->format('d/m/Y H:i:s')); # renglon con fecha y hora
        $sheet->getStyle('A1:A2')->getFont()->setBold(true); # negritas

        # Encabezados en fila 3
        foreach ($this->headings as $index => $columna) {
          $col = Coordinate::stringFromColumnIndex($index + 1); # convertir índice numérico a letra
          $sheet->setCellValue($col . '3', $columna); # establecer encabezado
        }
        $sheet->getStyle('A3:' . Coordinate::stringFromColumnIndex(count($this->headings)) . '3')->getFont()->setBold(true); # negritas encabezados

        $highestRow = $sheet->getHighestRow(); #
        $highestColumn = count($this->headings); # número de columnas exportadas

        # Convertimos índices → letras (A,B,C,...)
        $columns = [];
        for ($i = 1; $i <= $highestColumn; $i++) {
          $columns[] = Coordinate::stringFromColumnIndex($i); # convertir índice numérico a letra
        }

        $headerRange = "A3:" . Coordinate::stringFromColumnIndex($highestColumn) . "3"; # rango de encabezados
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF07633C'); # Fondo verde oscuro
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF'); # Texto blanco
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); # Centrado
        $sheet->setAutoFilter($headerRange); # Autofiltro

        # Ajustar ancho de columnas
        foreach ($columns as $col) {
          $sheet->getColumnDimension($col)->setAutoSize(true);
        }
      }
    ];
  }
}
