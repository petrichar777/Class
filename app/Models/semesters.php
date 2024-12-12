<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class semesters extends Model
{
    use HasFactory;
    protected $table = 'semesters';
    protected $fillable = [
        'semester',
       'status'
    ];
    //查询该学期状态
    public static function checkCourse(String $semester)
    {
        $semesterStats = self::where('semester', $semester)->value('status');
        if(!$semesterStats){
            return '该学期数据不存在';
        }
        if($semesterStats === 'InProgress'){
            return '正在选课';
        }
        return '选课已结束';
    }
    //管理员结束选课
    public static function endCourse(String $semester)
    {
        $semester = self::where('semester',$semester)->first();
        if($semester){
            $semester->status = 'EndProgress';
            $semester->save();
            return '选课已结束';
        }
        return '该学期数据不存在';
    }
}
