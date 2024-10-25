<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Exception;

class company_stars extends Authenticatable implements JWTSubject
{
    use HasFactory;

    protected $table = 'company_stars';
    protected $fillable = [
        'student_id',
        'project_name',
        'project_level',
        'ranking_total',
        'approval_time',
        'materials',
        'status',
        'created_at',
        'updated_at',
        'rejection_reason'
    ];

    public $timestamps = false;
    protected $primaryKey = "student_id";
    protected $guarded = [];

    //定义与students表的关系
    public function students()
    {
        return $this->belongsTo(students::class, 'student_id', 'id');
    }
    public function getJWTIdentifier()
{
    // TODO: Implement getJWTIdentifier() method.
    }
    public function getJWTCustomClaims()
{
    // TODO: Implement getJWTCustomClaims() method.
    }

}
