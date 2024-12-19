<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable; // 改为继承 Authenticatable
use Illuminate\Support\Facades\Crypt;
use Tymon\JWTAuth\Contracts\JWTSubject; // 引入 JWTSubject 接口
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        // getKey() 方法用于获取模型的主键值
        return $this->getKey();
    }

    /**
     * 返回一个包含自定义声明的关联数组。
     */

    //将用户的数据存储到token中
    public function getJWTCustomClaims()
    {
        // 将用户的所有数据存储到 token 中
        $userData = $this->toArray();

        // 你可以选择排除敏感数据，例如密码
        unset($userData['password']);

        return $userData;
    }

    // 与 course_applications 表的关联
    public function course_applications()
    {
        return $this->hasMany(course_applications::class, 'teacher_id', 'id');
    }

    // 与 course_assignments 表的关联
    public function course_assignments()
    {
        return $this->hasMany(course_assignments::class, 'teacher_id', 'id');
    }

    // 与 teacher_semester_stars 表的关联
    public function teacher_semester_stars()
    {
        return $this->hasMany(teacher_semester_stats::class, 'teacher_id', 'id');
    }



    public static function CreateUser($data){
        try{
            $affectedRows = users::insert([
                    'username' => $data['username'],
                    'name' => $data['name'],
                    'role' => $data['role'],
                    'department' => $data['department'],
                    'password' => Crypt::encrypt($data['password']),
                ]);
            return $affectedRows;
        }catch (\Exception $e){
            return 'error: '. $e->getMessage();
        }
    }

    public static function UpdatedUser($data){
        try{
            $affectedRows = users::where('id',$data['id'])
                ->update([
                    'username' => $data['username'],
                    'name' => $data['name'],
                    'department' => $data['department'],
                    'password' => Crypt::encrypt($data['password']),
                ]);
            return $affectedRows;
        }catch (\Exception $e){
            return 'error: '. $e->getMessage();
        }
    }

    public static function search_teacher($name){
        try{
           $result = users::where('name',$name)
               ->select('username','name','department',)
               ->get();
           return $result;
        }catch (\Exception $e){
            return 'error: '. $e->getMessage();
        }
    }


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class users extends Model
{
    use HasFactory;
    protected $table = 'users';
    protected $fillable = [
        'username',
        'password',
        'name',
        'department',
        'role',
        'status'
    ];

    //（重置密码）查询教师信息
    public static function getTeachers()
    {
        return DB::table('users')
            ->get(['username','name','department']);
    }
    //查看已通过申请的授课老师
    public static function getApprovedTeachers($courseId)
    {
        return self::join('course_applications', 'users.id', '=', 'course_applications.teacher_id')
            ->where('course_applications.status', '=', 'approved')
            ->where('course_applications.course_id', $courseId)
//            ->select('users.name')
//            ->select('users.department')
//            ->select('users.username')
            ->get(['users.name','users.username','users.department']);
    }

    //查看负责人
    public static function getHead()
    {
        return self::where('role','teacher')
//            ->select('users.name')
//            ->select('users.department')
            ->get(['users.name','users.username','users.department']);
    }


}
