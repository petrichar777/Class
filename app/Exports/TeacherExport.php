<?php

namespace App\Exports;

use App\Models\teacher_semester_stats;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TeacherExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    protected $semester; // 保存传入的学期
    public function __construct(string $semester)
    {
        $this->semester = $semester;
    }

    public function collection()
    {
        // 查询 teacher_semester_stats 表并过滤学期数据
        $teacher_semester_stars = teacher_semester_stats::with('users') // 加载关联的 users
        ->where('semester', $this->semester) // 按学期过滤
        ->select('teacher_id', 'semester', 'teaching_hours', 'course_count', 'class_count')
            ->get()
            ->map(function ($star) {
                // 将关联的 users 表数据合并进结果中
                return [
                    'name' => $star->users->name ?? '未知', // 用户名
                    'semester' => $star->semester,
                    'department' => $star->users->department ?? '未知', // 系别
                    'teaching_hours' => $star->teaching_hours,
                    'course_count' => $star->course_count,
                    'class_count' => $star->class_count,
                ];
            });

        return collect($teacher_semester_stars);
    }

    public function headings(): array
    {
        return ['教师名', '学期', '系别', '教学时长', '教授课程数', '教授班级数'];
    }
}
