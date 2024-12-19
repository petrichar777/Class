<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class courses extends Model
{
    use HasFactory;

    protected $table = "courses";
    public $timestamps = true;
    protected $primaryKey = "id";
    protected $guarded = [];

    protected $fillable = [ 'name' ,
                            'code',
                            'category' ,
                            'nature' ,
                            'credit' ,
                            'hours' ,
                            'grade' ,
                            'class_name',
                            'class_size' ,
                            'department' ,
                            'semester' ,];
    public function getJWTIdentifier()
    {
        //getKey() 方法用于获取模型的主键值
        return $this->getKey();
    }

    //返回一个包含自定义声明的关联数组。
    public function getJWTCustomClaims()
    {
        return ['role' => 'courses'];
    }

    // 与 company_stars 表的关联
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

    public static function delete_courses($id)
    {
        try {
           $result = courses::where('id',$id)
               ->delete();
           return $result;
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

}
