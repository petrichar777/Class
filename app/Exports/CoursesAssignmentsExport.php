<?php

namespace App\Exports;

use App\Models\course_assignments;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CoursesAssignmentsExport implements FromCollection, WithHeadings
{
    protected $semester;

    public function __construct(string $semester)
    {
        $this->semester = $semester;
    }

    public function collection()
    {
        // 查询数据并加载关联关系
        $data = course_assignments::with(['course', 'teacher', 'head']) // 预加载课程、教师、负责人信息
        ->whereHas('course', function ($query) {
            $query->where('semester', $this->semester); // 按学期筛选
        })
            ->get()
            ->map(function ($assignment) {
                return [
                    'semester' => $assignment->course->semester ?? '未知',
                    'course_name' => $assignment->course->name ?? '未知',       // 课程名称
                    'course_code' => $assignment->course->code ?? '未知',      // 课程代码
                    'course_category' => $assignment->course->category ?? '未知',      // 课程类别
                    'course_nature' => $assignment->course->nature ?? '未知', // 课程性质
                    'credits' => $assignment->course->credit ?? 0,           // 学分
                    'hours' => $assignment->course->hours ?? 0,               // 学时
                    'grade' => $assignment->course->grade ?? '未知',           // 年级
                    'class_name' => $assignment->course->class_name ?? '未知', // 班级
                    'class_size' => $assignment->course->class_size ?? 0,     // 人数
                    'teacher' => $assignment->teacher->name ?? '未知',         // 授课老师
                    'head' => $assignment->head->name ?? '未知',               // 负责人
                ];
            });
        return collect($data); // 转换为集合返回
    }

    public function headings(): array
    {
        return ['学期','课程名称', '课程代码', '课程类别', '课程性质', '学分', '学时', '年级', '班级', '人数', '授课老师', '负责人'];
    }
}
