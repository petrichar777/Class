<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Crypt;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class course_applications extends Authenticatable implements JWTSubject
{
    use HasFactory;

    protected $table = "course_applications";
    public $timestamps = true;
    protected $primaryKey = "id";
    protected $fillable = ['semester', 'status', 'created_at', 'submitted_at' . 'updated_at', 'teacher_id'];

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

    public static function insert_applications($data)
    {
        try {
            // 插入数据
            $Information = course_applications::insert([
                'only_course_id' => $data['only_course_id'],
                'teacher_id' => $data['teacher_id'],
                'submitted_at' => now(),
            ]);
            return $Information;
        } catch (Exception $e) {
            return 'error' . $e->getMessage();
        }
    }

    public static function deleted_applications($teacher_id, $data)
    {

        try {
            $Information = course_applications::where('teacher_id', $teacher_id)
                ->where('id', $data['id'])
                ->delete();
            // 如果记录存在，删除它
            // 返回受影响的行数
            return $Information;
        } catch (Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    public function course()
    {
        return $this->belongsTo(courses::class, 'course_id', 'id');
    }
}



