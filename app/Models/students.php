<?php

namespace App\Models;


use Illuminate\Foundation\Auth\User as Authenticatable;//引用Authenticatable类使得DemoModel具有用户认证功能
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;


 class students extends Authenticatable implements JWTSubject
{
     // 定义可以批量赋值的字段
    protected $fillable = [
        'account',
        'password',
        'major',
        'class',
        'name',
        'email',
        'company_star_certificate_address',
        'competition_star_certificate_address',
        'research_star_certificate_address',
        'software_stars_certificate_address',
        'paper_stars_certificate_address',
        'created_at',
        'updated_at',
    ];
    protected $table = "students";
    public $timestamps = true;
    protected $primaryKey = "id";
    protected $guarded = [];

    // 隐藏密码字段
    protected $hidden = [
        'password',
    ];
    //不知道有什么用
    use HasFactory;

    //定义与CompanyStars表的关系
     public function companyStar()
     {
         return $this->hasMany(company_stars::class,'student_id','id');
     }

    // 修改器：在设置密码时自动进行哈希加密
     public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = bcrypt($password);
    }


    public function getJWTIdentifier()
    {
        //getKey() 方法用于获取模型的主键值
        return $this->getKey();
    }

    //返回一个包含自定义声明的关联数组。
    public function getJWTCustomClaims()
    {
        return ['role' => 'students'];
    }




    public function science_star_registrations()
    {
        return $this->hasOne(science_star_registrations::class, 'student_id', 'id');
    }

     public static function charu($dam)//查找学生ID
    {
        try {
            $dm = students::where('name', $dam['name'])
                -> where('major',$dam['major'])
                -> where('class',$dam['class'])
                ->where('grade',$dam['grade'])
               // 明确指定列名
                ->first();
            return $dm;
        } catch (Exception $e) {
            return 'error' . $e->getMessage();
        }
    }

          public static function WdwUserCheckNumber($account){
         try {
             $count = students::select('account')
                 ->where('account', $account)
                 ->count();
             return $count;
         } catch (Exception $e) {
             return 'error' . $e->getMessage();
         }
     }

          public static function WdwcreateUser($user)
     {
         try {
             $data = students::insert([
                 'account' => $user['account'],
                 'password' =>  bcrypt($user['password']),
                 'grade' => $user['grade'],
                 'major' => $user['major'],
                 'class' => $user['class'],
                 'name' => $user['name'],
                 'email'=>$user['email'],
                 'created_at' => now(),
                 'updated_at' => now(),
             ]);
             return $data;

         } catch (Exception $e) {
             return 'error'.$e->getMessage();
         }
     }
     public static function Finding($data)
     {
         try {
             $data = students::where('grade', $data['grade'])
                 ->where('name', $data['name'])
                 ->where('major', $data['major'])
                 ->where('class', $data['class'])
                 ->first();
             return $data;

         } catch (Exception $e) {
             return 'error ' . $e->getMessage();
         }
     }

}

