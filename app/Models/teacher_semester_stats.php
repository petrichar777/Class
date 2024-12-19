<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable; // 如果需要身份验证，可以保留
use Tymon\JWTAuth\Contracts\JWTSubject; // 如果需要 JWT 身份验证，可以保留

class teacher_semester_stats extends Authenticatable implements JWTSubject // 保持此部分，如果你需要身份验证和JWT支持
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
        return $this->getKey(); // 获取主键值
    }

    /**
     * 返回一个包含自定义声明的关联数组。
     */
    public function getJWTCustomClaims(): array
    {
        return ['role' => 'teacher_semester_stats'];
    }

    // 关联用户（假设用户模型是 Users）
    public function users()
    {
        return $this->belongsTo(Users::class, 'teacher_id', 'id');
    }

    // 更新旧教师的学期统计数据，减少学时和班级数量
    public static function updateOldTeacherStats($data)
    {
        try {
            $result = self::where('teacher_id', $data['old_id'])->get();

            $result->each(function ($item) use ($data) {
                $item->teaching_hours -= $data['hours']; // 减少教学时数
                $item->class_count -= 1; // 减少班级数
                $item->save(); // 保存
            });

            return $result;
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    // 更新或创建教师学期统计数据
    public static function updateOrCreateTeacherSemesterStats($data)
    {
        try {
            if (!is_numeric($data['hours'])) {
                return 'error: hours should be a numeric value.';
            }

            $existingRecord = self::where('teacher_id', $data['new_id'])
                ->where('semester', $data['semester'])
                ->first();

            if ($existingRecord) {
                // 更新现有记录
                $existingRecord->teaching_hours += (int)$data['hours'];
                $existingRecord->class_count += 1;
                $existingRecord->save();
            } else {
                // 创建新记录
                self::create([
                    'teacher_id' => $data['new_id'],
                    'semester' => $data['semester'],
                    'teaching_hours' => (int)$data['hours'],
                    'course_count' => 1,
                    'class_count' => 1,
                ]);
            }

            return '操作成功';
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    // 更新教师课程数量
    public static function updateTeacherCourseCount($id, $o)
    {
        try {
            $result = self::where('teacher_id', $id)
                ->update(['course_count' => $o]);

            return $result;
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    // 判断是否存在教师的统计数据，如果不存在则创建
    public static function SelectCourseByTeacher(int $teacherId, String $semester)
    {
        return self::firstOrCreate([
            'teacher_id' => $teacherId,
            'semester' => $semester
        ], [
            'course_count' => 1,
            'class_count' => 1,
            'teaching_hours' => 0
        ]);
    }

    // 增加新教师的课程数量和时长
    public static function AddCourseByTeacherId(int $teacherId, String $semester, int $courseId)
    {
        $teacherSemesterStats = self::where('teacher_id', $teacherId)
            ->where('semester', $semester)
            ->first();

        if ($teacherSemesterStats) {
            $courseHours = courses::where('id', $courseId)->value('hours');
            if ($courseHours !== null) {
                $teacherSemesterStats->teaching_hours += $courseHours;
                $teacherSemesterStats->course_count += 1;
                $teacherSemesterStats->class_count += 1;
                return $teacherSemesterStats->save();
            }
        } else {
            $courseHours = courses::where('id', $courseId)->value('hours');
            if ($courseHours !== null) {
                return self::create([
                    'teacher_id' => $teacherId,
                    'semester' => $semester,
                    'course_count' => 1,
                    'class_count' => 1,
                    'teaching_hours' => $courseHours
                ]);
            }
        }
        return false;
    }

    // 减少旧教师的教学时长
    public static function DecrementCourseByTeacherId(int $teacherId, int $courseId, String $semester)
    {
        $teacherSemesterStats = self::where('teacher_id', $teacherId)
            ->where('semester', $semester)
            ->first();

        if ($teacherSemesterStats) {
            $courseHours = courses::where('id', $courseId)->value('hours');
            if ($courseHours !== null) {
                $teacherSemesterStats->teaching_hours -= $courseHours;
                $teacherSemesterStats->course_count -= 1;
                $teacherSemesterStats->class_count -= 1;
                return $teacherSemesterStats->save();
            }
        }
        return false;
    }

    // 删除旧教师的学期统计数据
    public static function DeleteTeacherStats(int $teacherId, String $semester)
    {
        $teacherSemesterStats = self::where('teacher_id', $teacherId)
            ->where('semester', $semester)
            ->first();

        if ($teacherSemesterStats) {
            return $teacherSemesterStats->delete();
        }
        return false;
    }

    // 获取教师的所有学期统计数据
    public static function new_data($ids, $semester)
    {
        try {
            return self::whereIn('teacher_id', $ids)
                ->where('semester', $semester)
                ->select('teaching_hours', 'course_count', 'class_count')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }
}
