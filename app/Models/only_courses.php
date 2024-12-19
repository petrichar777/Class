<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Crypt;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class only_courses extends Authenticatable implements JWTSubject
{
    use HasFactory;

    protected $table = "only_courses";
    public $timestamps = true;
    protected $primaryKey = "id";
    protected $fillable = ['semester', 'created_at', 'name' . 'updated_at', 'category', 'nature'.'credit','hours'];

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
    public static function check($data)
    {
        try {
            $Information = only_courses::where('semester', $data['semester'])
                ->select(
                    'name',
                    'category',
                    'nature',
                    'credit',
                    'hours',
                    'number_classes',
                    'semester',
                )
                ->get();
            return $Information;
        } catch (Exception $e) {
            return 'error' . $e->getMessage();
        }
    }
    public static function hand_insert($data)
    {
        try {
            // 确保 $data 中的键名与数据库字段匹配
            $Information = only_courses::insert([
                [
                    'name' => $data['name'],
                    'category' => $data['category'],
                    'nature' => $data['nature'],
                    'number_classes' => 1, // 固定值
                    'credit' => $data['credit'],
                    'hours' => $data['hours'],
                    'semester' => $data['semester'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
            return $Information;
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
}

}
