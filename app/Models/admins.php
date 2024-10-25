<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Exception;

class admins extends  Authenticatable implements JWTSubject
{
    use HasFactory;
    protected $table = "admins";
    protected $fillable = [
        'account',
        'password',
        'major'
    ];
    public $timestamps = false;

    protected $primaryKey = "id";

    protected $guarded = [];

    public function getJWTIdentifier()
    {
        // TODO: Implement getJWTIdentifier() method.
    }

    public function getJWTCustomClaims()
    {
        // TODO: Implement getJWTCustomClaims() method.
    }
}
