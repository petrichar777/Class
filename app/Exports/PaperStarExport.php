<?php

namespace App\Exports;

use App\Models\paper_stars;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PaperStarExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return paper_stars::all();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'students ID',
            'Grade',
            'Major',
            'Class',
            'Name',
            'Project Category',
            'Project Name',
            'Approval Time',
            'Ranking',
            'Total People',
            'Status',
            'Certificate',
            'Rejection Reason',
        ];
    }
}
