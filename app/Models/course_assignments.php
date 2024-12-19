<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Crypt;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class course_assignments extends Authenticatable implements JWTSubject
{
    use HasFactory;

    protected $table = "course_assignments";
    public $timestamps = true;
    protected $primaryKey = "id";
    protected $fillable = ['semester', 'created_at', 'assigned_at' . 'updated_at', 'teacher_id', 'course_id'];

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

}
