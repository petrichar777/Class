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

}
