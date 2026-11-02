<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

class SimpleExport implements FromCollection, WithEvents, WithHeadings
{
    public function __construct(
        protected array $headings,
        protected array $rows,
    ) {}

    public function collection()
    {
        return collect($this->rows);
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setRightToLeft(true);
                $sheet->getStyle('A1:'.$sheet->getHighestColumn().'1')->getFont()->setBold(true);
            },
        ];
    }
}
