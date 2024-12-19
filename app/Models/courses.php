<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Crypt;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class courses extends Authenticatable implements JWTSubject
{
    use HasFactory;

    protected $table = "courses";
    public $timestamps = true;
    protected $primaryKey = "id";
    protected $fillable = ['semester', 'department', 'name', 'code', 'category', 'credit', 'hours', 'grade', 'class_name', 'class_size', 'created_at', 'updated_at'];

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
        return [];
    }

    public static function getCourses(array $conditions)
    {
        try {
            // 使用 Eloquent 查询返回结果
            return self::where($conditions)->get();
        } catch (Exception $e) {
            // 捕获异常并返回错误信息
            return 'error: ' . $e->getMessage();
        }
    }

    /**
     * 更新课程信息。
     */
    public static function revise($data)
    {
        try {
            $information = Courses::where('id', $data['id'])
                ->update([
                    'name' => $data['name'], //课程名称
                    'code' => $data['code'], //课程代码
                    'category' => $data['category'], //课程类别
                    'nature' => $data['nature'], //课程性质
                    'credit' => $data['credit'], //学分
                    'hours' => $data['hours'], //总学时
                    'grade' => $data['grade'],
                    'class_name' => $data['class_name'], //班级
                    'class_size' => $data['class_size'], //人数
                    'semester' => $data['semester'],
                    'updated_at' => now(),
                ]);
            // 返回受影响的行数
            return $information;
        } catch (Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    public static function class_deleted($data) //查询课程表
    {
        try {
            $information = Courses::where('id', $data['id'])->delete();
            // 如果记录存在，删除它
            // 返回受影响的行数
            return $information;
        } catch (Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    // 与 company_stars 表的关联
    public function course_applications()
    {
        return $this->hasMany(course_applications::class, 'teacher_id', 'id');
    }

    // 与 course_assignments 表的关联
    public function course_assignments()
    {
        return $this->hasMany(\course_assignments::class, 'teacher_id', 'id');
    }

    // 与 teacher_semester_stars 表的关联
    public function teacher_semester_stars()
    {
        return $this->hasMany(teacher_semester_stats::class, 'teacher_id', 'id');
    }

    public static function delete_courses($id)
    {
        try {
            $result = Courses::where('id', $id)
                ->delete();
            return $result;
        } catch (Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    /**
     * 向数据库插入新课程数据。
     */
    public static function create($data)
    {
        try {
            $ss = Courses::insert([
                'name' => $data['name'], //课程名称
                'code' => $data['code'], //课程代码
                'category' => $data['category'], //课程类别
                'credit' => $data['credit'], //学分
                'nature' => $data['nature'], //课程性质
                'hours' => $data['hours'], //总学时
                'grade' => $data['grade'],
                'class_name' => $data['class_name'], //班级
                'class_size' => $data['class_size'], //人数
                'department' => $data['department'],
                'semester' => $data['semester'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return $ss;
        } catch (Exception $e) {
            return 'error' . $e->getMessage();
        }
    }

    public function applications()
    {
        return $this->hasMany(course_applications::class, 'course_id', 'id');
    }

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
