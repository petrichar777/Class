<?php

namespace App\Exports;

<<<<<<< HEAD
<<<<<<< HEAD
use App\Http\Model\Company;
use App\Http\Model\Competition;
use App\Http\Model\Sci;
=======
use App\Models\Company;
use App\Models\Competition;
use App\Models\Sci;
>>>>>>> f96da5e945cc34c0ee9f5ea316ca832f5d45270a
=======
use App\Models\Company;
use App\Models\Competition;
use App\Models\Sci;
>>>>>>> d6f3464 (first commit)
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;


class YourExportClassNameSic implements FromCollection, WithHeadings
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
        return Sci::whereIn('id', $this->teacherIds)
            ->get()
            ->map(function ($teacher) {
                return [
                    '年级' => $teacher->grade,
                    '专业' => $teacher->major,
                    '班级' => $teacher->class,
                    '姓名' => $teacher->stuname,
                    '类别' => $teacher->scitype,
                    '项目名称/软著名称/期刊名称/' => $teacher->sciname,
                    '项目级别/颁发单位/论文名称' => $teacher->scigrade,
                    '排名/总人数' => $teacher->ranking,
                    '立项时间/获批时间/发表时间' => $teacher->signuptime,
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
            '类别',
            '项目名称/软著名称/期刊名称/',
            '项目级别/颁发单位/论文名称',
            '排名/总人数',
            '立项时间/获批时间/发表时间',
            '佐证材料',
            '状态',
        ];
    }
}
