<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PhpParser\Node\Scalar\String_;

class teacher_semester_stats extends Model
{
    use HasFactory;
    protected $table = 'teacher_semester_stats';
    protected $fillable = [
        'teacher_id',
        'semesters',
        'course_count',
        'class_count',
        'status',
        'teaching_hours'
    ];
    //判断查询教学时长等信息
    public static function SelectCourseByTeacher(int $teacherId,String $semester)
    {
        $teacherSemesterStats = self::where('teacher_id', $teacherId)
            ->where('semesters', $semester)
            ->first();
        if (!$teacherSemesterStats) {
            $teacherSemesterStats = self::create([
                'teacher_id' => $teacherId,
                'semesters' => $semester,
                'course_count' => 1,
                'class_count' => 1,
                'teaching_hours' => 0
            ]);
        }
        return $teacherSemesterStats;
    }

    // 增加课程数量等信息
    public static function AddCourseByTeacherId(int $teacherId, String $semester,int $courseId)
    {
        $teacherSemesterStats = self::where('teacher_id', $teacherId)
            ->where('semesters', $semester)
            ->first();
        if ($teacherSemesterStats){
            $courseHours = courses::where('id',$courseId)
                ->value('hours');
            $teacherSemesterStats->teaching_hours += $courseHours;
            $teacherSemesterStats->course_count += 1;
            $teacherSemesterStats->class_count += 1;
            return $teacherSemesterStats->save();
        }
        return false;
    }

}
