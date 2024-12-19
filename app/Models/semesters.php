<?php

namespace App\Models;


use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Crypt;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;


class semesters extends Authenticatable implements JWTSubject // 实现 JWTSubject 接口

{
    use HasFactory;

    protected $table = "semesters";
    public $timestamps = true;
    protected $primaryKey = "id";

    protected $fillable = [  'semester','status','created_at'];


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

    public function getJWTCustomClaims()
    {
        return [];
    }
  public static function Semester_situation($data)
  {
            try{
                $situation=semesters::where('semester',$data['semester'])
                    ->select(
                        'status',
                    )
                    ->first();
                return $situation;
            } catch (Exception $e) {
                return 'error' . $e->getMessage();
            }
  }


    public static function create_semester($data)
    {
        try{
            $result = semesters::insert([
                'semester' => $data['semester'],
                'status' => $data['status'],
            ]);
            return $result;
        }catch (\Exception $e){
            return 'error: '. $e->getMessage();
        }
    }

    public static function find_judge($semester)
    {
        try{
            $result = semesters::where('semester',$semester)
                ->select('semester','status')
                ->get();
            return $result;
        }catch (\Exception $e){
            return 'error: '. $e->getMessage();
        }
    }

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
