<?php

namespace App\Models;

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
            ->select('users.name')
            ->get();
    }

    //查看负责人
    public static function getHead()
    {
        return self::where('role','head')
            ->select('users.name')
            ->get();
    }
}
