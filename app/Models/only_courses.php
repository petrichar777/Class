<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class only_courses extends Model
{
    use HasFactory;


    protected $table = "only_courses";
    public $timestamps = true;
    protected $primaryKey = "id";
    protected $guarded = [];

    protected $fillable = [
        'name' ,
        'category' ,
        'nature' ,
        'credit' ,
        'hours' ,
        'number_classes',
        'semester' ,];
    public function getJWTIdentifier()
    {
        //getKey() 方法用于获取模型的主键值
        return $this->getKey();
    }

    //返回一个包含自定义声明的关联数组。
    public function getJWTCustomClaims()
    {
        return ['role' => 'only_courses'];
    }

    // 与 company_stars 表的关联
    public function course_applications()
    {
        return $this->hasMany(course_applications::class, 'only_course_id', 'id');
    }

    public static function updateNumber_Classes($data)
    {
        try {
            // 第一步：查询 courses 表，找出需要上这门课程的班级数量
            $classCount = courses::where('name', $data['name'])  // 通过课程名查找
            ->where('semester',$data['semester'])
            ->count();  // 统计需要上这门课程的班级数

            // 如果班级数为 0，删除 only_courses 表中的数据
            if ($classCount == 0) {
                // 删除 only_courses 表中对应的记录
                $result = only_courses::where('name', $data['name'])
                    ->where('semester',$data['semester'])
                    ->delete();
            } else {
                // 第二步：更新 only_courses 表中的 number_classes 字段
                $result = only_courses::where('name', $data['name'])  // 根据课程名查找对应的记录
                ->where('semester',$data['semester'])
                ->update(['number_classes' => $classCount]);  // 更新字段
            }

            // 返回更新或删除的结果
            return $result;
        } catch (\Exception $e) {
            // 捕获异常并返回错误信息
            return 'error: ' . $e->getMessage();
        }
    }




}
