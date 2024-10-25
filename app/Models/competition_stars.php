<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\User as Authenticatable;//引用Authenticatable类使得DemoModel具有用户认证功能
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Exception;

class competition_stars extends Authenticatable implements JWTSubject
{
    // 定义可以批量赋值的字段
    protected $fillable = [
        'student_id',
        'competition_name',
        'registration_time',
        'materials',
        'status',
        'created_at',
        'updated_at',
        'registration_time',
        'rejection_reason',
    ];
    protected $table = "competition_stars";

    public $timestamps = false;
    protected $primaryKey = "id";
    protected $guarded = [];

    //不知道有什么用
    use HasFactory;

    public function getJWTIdentifier()
    {
        //getKey() 方法用于获取模型的主键值
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        // TODO: Implement getJWTCustomClaims() method.
    }
}
