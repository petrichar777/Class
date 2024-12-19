<?php

use App\Http\Controllers\WwjController;
use Illuminate\Support\Facades\Crypt;
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


use App\Http\Controllers\WdwController;

// 不需要身份验证的路由 ok
Route::post('login', [WdwController::class, 'login']);

//用户注册测试
Route::post('ceshi', [WdwController::class, 'ceshi']);

// 需要身份验证的路由组
Route::middleware('jwt.auth')->group(function () {
    //管理员导入课程表(可以使用异步处理优化) ok
    Route::post('/courses/import', [WdwController::class, 'import_courses_excel']);

    //管理员导出课程安排表 ok
    Route::post('/courses_assignments/export', [WdwController::class, 'exportcourse_assignments']);

    //超级管理员导入用户表(可以使用异步处理优化) ok
    Route::post('/admins/import-teachers-excel', [WdwController::class, 'import_users_excel']);

    //超级管理员导出解密用户表(导出全部的教师密码还是可以选择导出某专业的，也可以是全部的) 不ok
    Route::post('/admins/export-teachers-excel', [WdwController::class, 'exportUsers']);

    //管理员导出教学时长，教授班级，教授课程信息 ok
    Route::post('/teachers/export', [WdwController::class, 'exportteachers']);

    //管理员修改用户信息(不仅仅是密码) ok
    Route::post('/admins/reset_user', [WdwController::class, 'reset_user']);

    //管理员添加教师用户 ok
    Route::post('/admins/create_user', [WdwController::class, 'create_user']);

    //管理员搜索用户表 ok
    Route::post('/admins/search_teacher', [WdwController::class, 'search_teacher']);

    //管理员查看课程安排表 ok
    //Route::get('/admins/courses_assignments_search', [WdwController::class, 'courses_assignments_search']);

    //管理员搜索课程安排 ok 做一个字符串的拆分
    Route::post('/admins/courses_assignments/search', [WdwController::class, 'assignments_search']);

    //超级管理员添加新学期 ok
    Route::post('/admins/create_semester', [WdwController::class, 'create_semester']);

    //超级管理员添加授课老师
    Route::post('/admin/course_assignments/choose_teacher', [WdwController::class, 'choose_teacher']);

    //管理员删除课程安排表
    Route::delete('/admin/delete_course_assignments', [WdwController::class, 'delete_course_assignments']);
//lyw
    Route::post('/courses/leader-audit', [\App\Http\Controllers\LywController::class, 'audit']);
    Route::post('/courses/assign', [\App\Http\Controllers\LywController::class, 'assign']);
    Route::GET('/courses/leader-view', [\App\Http\Controllers\LywController::class, 'CheckApplication']);
    Route::GET('/courses/approved-teachers', [\App\Http\Controllers\LywController::class, 'approved_teachers']);
    Route::post('/courses/filter-for-assign', [\App\Http\Controllers\LywController::class, 'filter_assign']);
    Route::GET('/courses/search-for-assign', [\App\Http\Controllers\LywController::class, 'HeaderSearch']);
    Route::GET('/courses/search', [\App\Http\Controllers\LywController::class, 'getCourses']);

    //用户登出 ok
    Route::post('/user/logout', [WdwController::class, 'logout']);

    //王文杰
    //查询老师信息
    Route::get('/admins/users/query', [WwjController::class, 'getTeacher']);
//查询已通过申请的授课老师
    Route::get('/courses/approved-teachers', [WwjController::class, 'getApprovedTeachers']);
//确认该课程正式授课教师
    Route::post('/courses/assign-teacher', [WwjController::class, 'assignTeacher']);
//查看负责人
    Route::get('/courses/responsibles', [WwjController::class, 'getHead']);
//确认负责人
    Route::post('/courses/ConfirmResponse', [WwjController::class, 'confirmHead']);
//按学期查询课程信息
    Route::get('/courses/semester-info', [WwjController::class, 'selectCourse']);
//教师查看正式授课信息
    Route::get('/teachers/assigned-courses', [WwjController::class,'getCourseByTeacher']);
//结束选课

    //汪珂旭
    Route::post('/admins/end-course-selection', [WwjController::class,'endCourse']);

    Route::get('/courses/info-and-teachers',[\App\Http\Controllers\WkxController::class,'admin_see_courses_application']);//查看老师所有的课程申请（管理员审核申请）

    Route::get('/courses/search-teacher',[\App\Http\Controllers\WkxController::class,'teacher_search_courses']);//老师搜索课程信息（选择课程）

    Route::delete('/admins/users/delete',[\App\Http\Controllers\WkxController::class,'admins_delete_users']);//删除用户（重置密码）

    Route::get('/look/teachers/summary',[\App\Http\Controllers\WkxController::class,'look_teachers_summary']);//查看老师信息（老师总结页面）

    Route::post('/courses/audit',[\App\Http\Controllers\WkxController::class,'courses_audit']);//审核课程（管理员端负责人审核）
});




