<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class courses extends Model
{
    use HasFactory;
    protected $table = 'courses';
    protected $fillable = [
        'name',
        'code',
        'category',
        'nature',
        'credit',
        'hours',
        'grade',
        'semester',
        'class_name',
        'class_size',
        'department'
    ];
    //查询课程信息
    public static function getCourseInfo(String $semester)
    {
        return self::join('teacher_semester_stats', 'courses.semester', '=', 'teacher_semester_stats.semester')
            ->where('teacher_semester_stats.semester', 'like', "%$semester%")  // 使用 LIKE 进行模糊匹配
            ->get([
                'courses.id', 'courses.name', 'courses.code', 'courses.category',
                'courses.nature', 'courses.credit', 'courses.hours', 'courses.grade',
                'courses.semester', 'courses.class_name', 'courses.class_size', 'courses.department'
            ]);
    }
}
