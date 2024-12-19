<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


use Illuminate\Foundation\Auth\User as Authenticatable; // 改为继承 Authenticatable
use Tymon\JWTAuth\Contracts\JWTSubject; // 引入 JWTSubject 接口
use Illuminate\Database\Eloquent\Factories\HasFactory;

class teacher_semester_stats extends Authenticatable implements JWTSubject // 实现 JWTSubject 接口
{
    use HasFactory;

    protected $table = "teacher_semester_stats";
    public $timestamps = true;
    protected $primaryKey = "id";
    protected $guarded = [];

    /**
     * 获取将存储在 JWT 中的标识符。
     */
    public function getJWTIdentifier()
    {
        // getKey() 方法用于获取模型的主键值
        return $this->getKey();
    }

    /**
     * 返回一个包含自定义声明的关联数组。
     */

    //将用户的数据存储到token中
    public function getJWTCustomClaims(): array
    {
        return ['role' => 'teacher_semester_stars'];
    }

    public function users()
    {
        return $this->belongsTo(Users::class, 'teacher_id', 'id');
    }

    // 更新旧教师的学期统计数据，减少学时、和班级数量
    public static function updateOldTeacherStats($data)
    {
        try {
            // 获取教师的学期统计数据
            $result = teacher_semester_stats::where('teacher_id', $data['old_id'])->get();

            // 遍历查询到的数据
            $result->each(function ($item) use ($data) {
                // 减去 hours 的值
                $item->teaching_hours = $item->teaching_hours - $data['hours'];
                // class_count 减 1
                $item->class_count = $item->class_count - 1;
                // 保存修改后的数据
                $item->save();
            });

            // 返回处理后的结果
            return $result;
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    // 更新或创建教师的学期统计数据
    public static function updateOrCreateTeacherSemesterStats($data)
    {
        try {
            // 确保 hours 是一个数字
            if (!is_numeric($data['hours'])) {
                return 'error: hours should be a numeric value.';
            }

            // 查找是否已有该教师的学期统计数据
            $existingRecord = teacher_semester_stats::where('teacher_id', $data['new_id'])
                ->where('semester', $data['semester'])  // 确保是当前学期的数据
                ->first();  // 获取第一条记录

            if ($existingRecord) {
                // 如果找到了记录，更新教学时数、课程数量、班级数量
                $existingRecord->teaching_hours += (int)$data['hours'];  // 增加 teaching_hours
                $existingRecord->class_count += 1;   // 班级数量加 1
                $existingRecord->save();  // 保存更新后的记录
            } else {
                // 如果没有找到记录，创建新的统计数据
                teacher_semester_stats::create([
                    'teacher_id' => $data['new_id'],
                    'semester' => $data['semester'],
                    'teaching_hours' => (int)$data['hours'],  // 初始化为传入的 hours
                    'course_count' => 1,  // 课程数量设为 1
                    'class_count' => 1,   // 班级数量设为 1
                ]);
            }

            return '操作成功';
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    // 更新教师的课程数量
    public static function updateTeacherCourseCount($id, $o)
    {
        try {
            // 获取教师的学期统计数据，并更新 course_count 字段
            $result = teacher_semester_stats::where('teacher_id', $id)
                ->update([  // 使用 update 来更新
                    'course_count' => $o,
                ]);

            // 返回处理后的结果
            return $result;  // 返回更新的行数，更新成功时会返回受影响的行数
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }







use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PhpParser\Node\Scalar\String_;


class teacher_semester_stats extends Model
{
    use HasFactory;


    protected $table = 'teacher_semester_stats';
    protected $fillable = [
        'teacher_id',
        'semester',
        'course_count',
        'class_count',
        'status',
        'teaching_hours'
    ];
    //判断查询教学时长等信息
    public static function SelectCourseByTeacher(int $teacherId,String $semester)
    {
//        $teacherSemesterStats = self::where('teacher_id', $teacherId)
//            ->where('semester', $semester)
//            ->first();
//        if (!$teacherSemesterStats) {
//            $teacherSemesterStats = self::create([
//                'teacher_id' => $teacherId,
//                'semester' => $semester,
//                'course_count' => 1,
//                'class_count' => 1,
//                'teaching_hours' => 0
//            ]);
//        }
//        return $teacherSemesterStats;
        $teacherSemesterStats = self::firstOrCreate([
            'teacher_id' => $teacherId,'semester' => $semester],
            ['course_count' => 1,'class_count' => 1,'teaching_hours' => 0]
        );
        return $teacherSemesterStats;
    }

    // 增加新教师课程数量等信息
    public static function AddCourseByTeacherId(int $teacherId, String $semester,int $courseId)
    {
        $teacherSemesterStats = self::where('teacher_id', $teacherId)
            ->where('semester', $semester)
            ->first();
        if ($teacherSemesterStats){
            $courseHours = courses::where('id',$courseId)
                ->value('hours');
            if($courseHours !== null) {
                $teacherSemesterStats->teaching_hours += $courseHours;
                $teacherSemesterStats->course_count += 1;
                $teacherSemesterStats->class_count += 1;
                return $teacherSemesterStats->save();
            }
        }else{
            $courseHours = courses::where('id',$courseId)
                ->value('hours');
            if($courseHours !== null) {
                return  self::create([
                    'teacher_id' => $teacherId,'semester' => $semester,
                    'course_count' => 1,'class_count' => 1,'teaching_hours' => $courseHours
                ]);
            }
        }
        return false;
    }
    //减少旧教师的教学时长
    public static function DecrementCourseByTeacherId(int $teacherId, int $courseId,String $semester)
    {
        $teacherSemesterStats = self::where('teacher_id', $teacherId)
            ->where('semester', $semester)
            ->first();
        if ($teacherSemesterStats){
            $courseHours = courses::where('id',$courseId)
                ->value('hours');
            if ($courseHours!== null) {
                $teacherSemesterStats->teaching_hours -= $courseHours;
                $teacherSemesterStats->course_count -= 1;
                $teacherSemesterStats->class_count -= 1;
                return $teacherSemesterStats->save();
            }
        }
        return false;
    }
    //删除旧教师的教学时长
    public static function DeleteTeacherStats(int $teacherId,String $semester)
    {
        $teacherSemesterStats = self::where('teacher_id', $teacherId)
            ->where('semester', $semester)
            ->first();
        if ($teacherSemesterStats){
            return $teacherSemesterStats->delete();
        }
        return false;
    }



}
