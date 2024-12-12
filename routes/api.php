<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WwjController;

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

//查询教师信息（重置密码）
Route::get('/admins/users/query',[\App\Http\Controllers\WwjController::class,'getTeacher']);
//查看已通过申请的授课老师
Route::get('/courses/approved-teachers',[\App\Http\Controllers\WwjController::class,'getApprovedTeachers']);
//确认该课程正式授课教师
Route::post('/courses/assign-teacher', [WwjController::class, 'assignTeacher']);
//查看负责人
Route::get('/courses/responsibles', [WwjController::class, 'getHead']);
//确认该课程的负责人
Route::post('/courses/ConfirmResponse', [WwjController::class, 'confirmHead']);
//按学期查询课程信息
Route::get('/courses/semesters-info', [WwjController::class, 'selectCourse']);
//查询正式授课课程信息
Route::get('/teachers/assigned-courses', [WwjController::class,'getCourseByTeacher']);
//管理员结束选课
Route::post('/admins/end-course-selection', [WwjController::class,'endCourse']);
