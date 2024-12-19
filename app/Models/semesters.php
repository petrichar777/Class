<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Crypt;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class semesters extends Authenticatable implements JWTSubject
{
    use HasFactory;

    protected $table = "semesters";
    public $timestamps = true;
    protected $primaryKey = "id";
    protected $fillable = [  'semester','status','created_at'];


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








}
