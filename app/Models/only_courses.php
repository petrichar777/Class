<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;
use Tymon\JWTAuth\Contracts\JWTSubject;

class only_courses extends Model implements JWTSubject
{
    use HasFactory;

    protected $table = "only_courses";
    public $timestamps = true;
    protected $primaryKey = "id";
    protected $guarded = []; // 表示所有字段都可以被批量赋值

    protected $fillable = [
        'name',
        'category',
        'nature',
        'credit',
        'hours',
        'number_classes',
        'semester'
    ];

    /**
     * 获取将存储在 JWT 中的标识符。
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * 返回一个包含自定义声明的关联数组。
     */
    public function getJWTCustomClaims()
    {
        return ['role' => 'only_courses'];
    }

    // 查询课程信息
    public static function check($data)
    {
        try {
            $information = self::where('semester', $data['semester'])
                ->select(
                    'name',
                    'category',
                    'nature',
                    'credit',
                    'hours',
                    'number_classes',
                    'semester'
                )
                ->get();
            return $information;
        } catch (Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    // 插入新课程信息
    public static function hand_insert($data)
    {
        try {
            $information = self::insert([
                [
                    'name' => $data['name'],
                    'category' => $data['category'],
                    'nature' => $data['nature'],
                    'number_classes' => 1, // 固定值
                    'credit' => $data['credit'],
                    'hours' => $data['hours'],
                    'semester' => $data['semester'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
            return $information;
        } catch (Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    // 更新课程的班级数量
    public static function updateNumberClasses($data)
    {
        try {
            // 查询课程的班级数量
            $classCount = Courses::where('name', $data['name'])
                ->where('semester', $data['semester'])
                ->count();

            // 如果没有班级，删除 only_courses 中的相关记录
            if ($classCount == 0) {
                $result = self::where('name', $data['name'])
                    ->where('semester', $data['semester'])
                    ->delete();
            } else {
                // 更新班级数量
                $result = self::where('name', $data['name'])
                    ->where('semester', $data['semester'])
                    ->update(['number_classes' => $classCount]);
            }

            return $result;
        } catch (Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    // 教师查询课程
    public static function teacherSearchCourses($semester, $name)
    {
        try {
            $data = self::where('semester', $semester)
                ->where('name', 'like', "%$name%") // 使用模糊查询
                ->select('id', 'name', 'category', 'nature', 'credit', 'hours', 'number_classes')
                ->get();
            return $data;
        } catch (Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    // 查看课程信息
    public static function seeCourse($onlyCourseIds)
    {
        try {
            $data = self::whereIn('id', $onlyCourseIds)
                ->select('name', 'category', 'nature', 'credit', 'hours', 'number_classes')
                ->get()
                ->toArray();
            return $data;
        } catch (Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    // 与 course_applications 表的关联
    public function courseApplications()
    {
        return $this->hasMany(course_applications::class, 'only_course_id', 'id');
    }
}
