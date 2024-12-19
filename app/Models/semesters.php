<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable; // 改为继承 Authenticatable
use Illuminate\Support\Facades\Crypt;
use Tymon\JWTAuth\Contracts\JWTSubject; // 引入 JWTSubject 接口
use Illuminate\Database\Eloquent\Factories\HasFactory;

class semesters extends Authenticatable implements JWTSubject // 实现 JWTSubject 接口
{
    use HasFactory;

    protected $table = "semesters";
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
        return ['role' => 'semester'];
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

}
