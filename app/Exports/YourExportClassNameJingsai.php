<?php

namespace App\Exports;

<<<<<<< HEAD
<<<<<<< HEAD
use App\Http\Model\Competition;
=======
use App\Models\Competition;
>>>>>>> f96da5e945cc34c0ee9f5ea316ca832f5d45270a
=======
use App\Models\Competition;
>>>>>>> d6f3464 (first commit)
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;


class YourExportClassNameJingsai implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */

    protected $teacherIds;

    public function __construct($teacherIds)
    {
        $this->teacherIds = $teacherIds;
    }

    public function collection()
    {
        return Competition::whereIn('id', $this->teacherIds)
            ->get()
            ->map(function ($teacher) {
                return [
                    '年级' => $teacher->grade,
                    '专业' => $teacher->major,
                    '班级' => $teacher->class,
                    '姓名' => $teacher->stuname,
                    '参与竞赛名称' => $teacher->entryname,
                    '佐证材料' => $teacher->url,
                    '状态' => $teacher->state,
                ];
            });
    }

    public function headings(): array
    {
        return [
            '年级',
            '专业',
            '班级',
            '姓名',
            '参与竞赛名称',
            '佐证材料',
            '状态',
        ];
    }
}
