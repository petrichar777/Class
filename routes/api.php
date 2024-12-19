<?php

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Route;
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

    //用户登出 ok
    Route::post('/user/logout', [WdwController::class, 'logout']);


});



