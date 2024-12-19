<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use function Symfony\Component\Translation\t;

class course_assignments extends Model
{
    use HasFactory;
    protected $table = 'course_assignments';
    protected $fillable = [
        'course_id',
        'teacher_id',
        'assigned_at',
        'head_id'
    ];
    public $timestamps = false;
    protected $casts = [
        'assigned_at' => 'datetime',
    ];
    //确认该课程正式授课教师
    public static function assignTeacher(int $courseId,int $teacherId)
    {
        $applicationExists = DB::table('course_applications')
            ->where('course_id', $courseId)
            ->where('teacher_id', $teacherId)
            ->where('status', 'approved')
            ->exists();
        if (!$applicationExists) {
            return false;
        }
        $assignment = self::firstOrCreate(
            ['course_id' => $courseId],
            ['teacher_id' => $teacherId, 'assigned_at' => now()]
        );
        // 如果记录存在且需要更新
        if ($assignment->wasRecentlyCreated === false) {
            $assignment->teacher_id = $teacherId;
            $assignment->assigned_at = now();
            return $assignment->save();
        }
        return $assignment;
    }

//    //确认该课程的负责人
//    public static function confirmHead(int $courseId, int $headId)
//    {
//        $assignment = self::firstOrCreate(
//            ['course_id' => $courseId], // 根据课程 ID 查找记录
//            ['head_id' => $headId, 'assigned_at' => now()] // 如果记录不存在，插入数据
//        );
//        if (!$assignment) {
//            return false;
//        }
//        // 如果记录存在且需要更新
//        if ($assignment->wasRecentlyCreated === false) {
//            $assignment->head_id = $headId;
//            $assignment->assigned_at = now();
//            return $assignment->save();
//        }
//        return $assignment;
//    }

    //确认该课程的负责人
    public static function confirmHead(int $courseId, int $headId)
    {
        $assignment = self::firstOrCreate(
            ['course_id' => $courseId],
            ['head_id' => $headId, 'assigned_at' => now()]
        );
        if ($assignment->wasRecentlyCreated === false) {
            $assignment->head_id = $headId;
            $assignment->assigned_at = now();
            $assignment->save();
        }
        $user = users::find($headId);
        if ($user) {
            $user->role = 'head';
            $user->save();
        }
        return $assignment;
    }
    //查询授课老师和负责人姓名
    public static function getTeacherAndHead(int $courseId){
        return self::join('users as teachers', 'course_assignments.teacher_id', '=', 'teachers.id')
            ->join('users as heads', 'course_assignments.head_id', '=', 'heads.id')
            ->where('course_assignments.course_id', $courseId)
            ->select('teachers.name as teacher_name', 'heads.name as head_name')
            ->first();
    }
    //教师查看正式授课的课程信息
    public static function getCourse(String $semester,int $teacherId)
    {
        return self::join('courses', 'course_assignments.course_id', '=', 'courses.id')
            ->join('semesters','courses.semester','=','semesters.semester')
            ->where('course_assignments.teacher_id',$teacherId)
            ->where('semesters.semester',$semester)
            ->get([
                'courses.id',
                'courses.name',
                'courses.code',
                'courses.category',
                'courses.nature',
                'courses.credit',
                'courses.hours',
                'courses.semester',
                'courses.class_name',
                'courses.class_size',
                'courses.department'
            ]);
    }

}
