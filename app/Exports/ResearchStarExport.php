<?php
namespace App\Exports;

use App\Models\research_stars;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ResearchStarExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return research_stars::all();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'id',
            'project_name',
            'project_level',
            'ranking_total',
            'approval_time',
            'materials',
            'status',
            'rejection_reason',
        ];
    }
}
