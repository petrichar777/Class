<?php

namespace App\Models;

use Exception;
use Illuminate\Foundation\Auth\User as Authenticatable; // 改为继承 Authenticatable
use Illuminate\Support\Facades\Hash;  // 引入 Hash 类
use Tymon\JWTAuth\Contracts\JWTSubject; // 引入 JWTSubject 接口
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class users extends Authenticatable implements JWTSubject // 实现 JWTSubject 接口
{
    use HasFactory;

    protected $table = "users";
    public $timestamps = true;
    protected $primaryKey = "id";
    protected $guarded = [];

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
        $userData = $this->toArray();
        unset($userData['password']); // 移除密码信息
        return $userData;
    }

    // 与 course_applications 表的关联
    public function courseApplications()
    {
        return $this->hasMany(course_applications::class, 'teacher_id', 'id');
    }

    // 与 course_assignments 表的关联
    public function courseAssignments()
    {
        return $this->hasMany(course_assignments::class, 'teacher_id', 'id');
    }

    // 与 teacher_semester_stats 表的关联
    public function teacherSemesterStats()
    {
        return $this->hasMany(teacher_semester_stats::class, 'teacher_id', 'id');
    }

    // 创建用户
    public static function createUser($data)
    {
        try {
            $affectedRows = Users::insert([
                'username' => $data['username'],
                'name' => $data['name'],
                'role' => $data['role'],
                'department' => $data['department'],
                'password' => Hash::make($data['password']),  // 使用 Hash 进行密码加密
            ]);
            return $affectedRows;
        } catch (\Exception $e) {
            return 'error: '. $e->getMessage();
        }
    }

    // 更新用户信息
    public static function updateUser($data)
    {
        try {
            $affectedRows = Users::where('id', $data['id'])
                ->update([
                    'username' => $data['username'],
                    'name' => $data['name'],
                    'department' => $data['department'],
                    'password' => Hash::make($data['password']),  // 使用 Hash 进行密码加密
                ]);
            return $affectedRows;
        } catch (\Exception $e) {
            return 'error: '. $e->getMessage();
        }
    }

    // 搜索教师
    public static function searchTeacher($name)
    {
        try {
            $result = Users::where('name', $name)
                ->select('username', 'name', 'department')
                ->get();
            return $result;
        } catch (\Exception $e) {
            return 'error: '. $e->getMessage();
        }
    }

    // 删除人员
    public static function deletePeople($id)
    {
        try {
            $data = Users::where('id', $id)
                ->delete();
            return $data;
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    // 获取所有教师
    public static function getTeachers()
    {
        return DB::table('users')
            ->get(['username', 'name', 'department']);
    }

    // 获取已通过申请的授课老师
    public static function getApprovedTeachers($courseId)
    {
        return self::join('course_applications', 'users.id', '=', 'course_applications.teacher_id')
            ->where('course_applications.status', '=', 'approved')
            ->where('course_applications.course_id', $courseId)
            ->get(['users.name', 'users.username', 'users.department']);
    }

    // 获取负责人
    public static function getHead()
    {
        return self::where('role', 'teacher')
            ->get(['users.name', 'users.username', 'users.department']);
    }

    // 查看教师信息
    public static function seeData($department)
    {
        try {
            $data = Users::where('department', $department)
                ->where('role', "teacher")
                ->select('name', 'id', 'department')
                ->get()
                ->toArray();
            return $data;
        } catch (Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    // 查看所有教师信息
    public static function seeData1()
    {
        try {
            $data = Users::where('role', "teacher")
                ->select('name', 'id', 'department')
                ->get()
                ->toArray();
            return $data;
        } catch (Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    // 查看角色
    public static function seeRole($id)
    {
        try {
            $data = Users::where('id', $id)
                ->value('role');  // 使用 value() 代替 pluck()
            return $data;
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    // 根据部门查看教师
    public static function lookData($department)
    {
        try {
            $data = Users::where('department', $department)
                ->select('name', 'id')
                ->get();
            return $data;
        } catch (Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    // 查看部门内所有教师
    public static function seeDataByDepartment($id)
    {
        try {
            $department = Users::where('id', $id)
                ->pluck('department');
            $data = Users::where('department', $department)
                ->where('role', "teacher")
                ->get('name', 'id')
                ->toArray();
            return $data;
        } catch (Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    // 获取教师姓名
    public static function seeName($teacherIds)
    {
        try {
            $data = Users::whereIn('id', $teacherIds)
                ->select('name')
                ->get()
                ->toArray();
            return $data;
        } catch (Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }
}
