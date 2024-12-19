<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MhwController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::get('courses/grade/name',[MhwController::class,'courses_grade_name']);//下拉专业，年级，课程查询
//Route::get('courses/assigned',[MhwController::class,'courses_assigned']);//查看已经正式授课的课程信息
Route::delete('courses/delete/application',[MhwController::class,'courses_delete_application']);//老师删除申请课程
Route::get('courses/all',[MhwController::class,'courses_all']);//老师查看全部课程
Route::get('courses/applied',[MhwController::class,'courses_applied']);//老师查看已申请课程
Route::post('courses/apply',[MhwController::class,'courses_apply']);//老师申请课程
Route::post('courses/add',[MhwController::class,'courses_add']);//管理员手动添加信息
//Route::delete('courses/delete',[MhwController::class,'courses_delete']);//管理员删除课程信息
Route::post('courses/updater',[MhwController::class,'courses_updater']);//管理员修改课程信息
Route::post('login', [MhwController::class, 'login']);
//需要身份验证的路由组
Route::middleware('jwt.auth')->group(function (){
//用户登出
    Route::post('/user/logout', [MhwController::class, 'logout']);
    Route::get('courses/filter',[MhwController::class,'courses_filter']);//管理员分级查询课表
});

