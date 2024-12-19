<?php

namespace App\Http\Controllers;

use App\Exports\CoursesAssignmentsExport;
use App\Exports\TeacherExport;
use App\Exports\UsersExport;
use App\Imports\importcourses;
use App\Imports\importusers;
use App\Models\course_assignments;
use App\Models\courses;
use App\Models\only_courses;
use App\Models\semesters;
use App\Models\teacher_semester_stats;
use App\Models\users;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Crypt;
use Maatwebsite\Excel\Facades\Excel;
use Tymon\JWTAuth\Facades\JWTAuth;

//public function parse_token($token)
//{
//    try {
//        // 解密 Token
//        $decrypted = Crypt::decryptString($token);
//
//        // 将解密后的数据按 '|' 分割成数组
//        list($userId, $userName, $userRole, $userDepartment, $timestamp) = explode('|', $decrypted);
//
//        // 现在可以使用 $userId, $userName, $userRole, $userDepartment 和 $timestamp
//        // 比如返回这些数据
//        return response()->json([
//            'user_id' => $userId,
//            'user_name' => $userName,
//            'user_role' => $userRole,
//            'user_department' => $userDepartment,
//            'timestamp' => $timestamp
//        ], 200);
//
//    } catch (\Exception $e) {
//        return response()->json(['message' => 'Invalid or expired token'], 400);
//    }
//}

class WdwController extends Controller
{
    //用户登录
    public function login(Request $request)
    {
        $username = $request->input('username');
        $password = $request->input('password');

        if (empty($username) || empty($password)) {
            return json_fail('请输入账号或密码', null, 100);
        }

        $user = users::where('username', $username)->first();

        if (!$user) {
            return json_fail('未查找到该用户', null, 100);
        }

        try {
            // 解密数据库中存储的密码
            $decryptedPassword = Crypt::decrypt($user->password);
            // 比较密码
            if ($decryptedPassword !== $password) {
                return json_fail('密码错误', null, 100);
            }

            // 生成 JWT Token
            $data['token'] = JWTAuth::fromUser($user);
            $data['role'] = $user->role;
            return json_success('登录成功', $data, 200);
        } catch (\Exception $e) {
            return json_fail('出现错误', $e->getMessage(), 100);
        }
    }

    public function logout(Request $request)
    {
        try {
            //使得当前用户的token失效
            JWTAuth::invalidate(JWTAuth::getToken());
            return json_success('登出成功',null,200);
        }catch (\Exception $e){
            return json_fail('登出失败',$e,100);
        }
    }

    //导入课程表
    public function import_courses_excel(Request $request)
    {

        // 解析 JWT Token 并获取 payload
        $payload = JWTAuth::parseToken()->getPayload();

        // 获取 role 信息
        $role = $payload->get('role');

        // 判断用户角色权限
        if ($role !== 'super_admin' && $role !== 'admin') {
            return json_fail('权限不足', null, 100);
        }
        // 验证上传的文件
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $file = $request->file('file');

        try {
            // 调用导入类处理数据
            Excel::import(new importcourses, $file);

            return json_success('导入成功',null,200);
        } catch (\Exception $e) {
            return json_fail('导入失败',$e->getMessage(),100);
        }
    }



    public function exportcourse_assignments(Request $request)
    {

        // 解析 JWT Token 并获取 payload
        $payload = JWTAuth::parseToken()->getPayload();

        // 获取 role 信息
        $role = $payload->get('role');

        // 判断用户角色权限
        if ($role !== 'super_admin' && $role !== 'admin') {
            return json_fail('权限不足', null, 100);
        }

        $semester = $request->input('semester');

        $fileName = '教师教学日志表_' . $semester . '.xlsx';
        return Excel::download(new CoursesAssignmentsExport($semester), $fileName);

    }

    //导入用户表
    public function import_users_excel(Request $request)
    {

        // 解析 JWT Token 并获取 payload
        $payload = JWTAuth::parseToken()->getPayload();

        // 获取 role 信息
        $role = $payload->get('role');

        // 判断用户角色权限
        if ($role !== 'super_admin') {
            return json_fail('权限不足', null, 100);
        }

        // 验证上传的文件
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $file = $request->file('file');



        try {
            // 调用导入类处理数据
            Excel::import(new importusers, $file);

            return json_success('导入成功',null,200);
        } catch (\Exception $e) {
            return json_fail('导入失败',$e->getMessage(),100);
        }
    }

    //导出解密后的用户表
    public function exportUsers(Request $request)
    {
        // 解析 JWT Token 并获取 payload
        $payload = JWTAuth::parseToken()->getPayload();

        // 获取 role 信息
        $role = $payload->get('role');

        // 判断用户角色权限
        if ($role !== 'super_admin') {
            return json_fail('权限不足', null, 100);
        }

        $department = $request->input('department');

        $fileName = '用户表.xlsx'; // 设置导出文件名
        return Excel::download(new UsersExport($department), $fileName);
    }

    //管理员导出教学时长，教授班级，教授课程信息
    public function exportteachers(Request $request)
    {
        // 解析 JWT Token 并获取 payload
        $payload = JWTAuth::parseToken()->getPayload();

        // 获取 role 信息
        $role = $payload->get('role');

        // 判断用户角色权限
        if ($role !== 'super_admin' && $role !== 'admin') {
            return json_fail('权限不足', null, 100);
        }

        $semester = $request->input('semester');
        if (empty($semester)) {
            return json_fail('缺少学期参数', null, 101);
        }
        // 动态设置文件名，包含学期信息
        $fileName = '教师教学日志表_' . $semester . '.xlsx';
        return Excel::download(new TeacherExport($semester), $fileName);
    }

    private function generateUniqueUsername(string $username): string
    {
        $originalUsername = $username;
        $counter = 1;

        while (users::where('username', $username)->exists()) {
            $username = $originalUsername . '_' . $counter;
            $counter++;
        }

        return $username;
    }

    //超级管理员添加新教师用户
    public function create_user(Request $request)
    {
        // 解析 JWT Token 并获取 payload
        $payload = JWTAuth::parseToken()->getPayload();

        // 获取 role 信息
        $role = $payload->get('role');

        // 判断用户角色权限
        if ($role !== 'super_admin') {
            return json_fail('权限不足', null, 100);
        }
        $data['username'] = $request->input('username');
        // 检查用户名并生成唯一用户名
        $uniqueUsername = $this->generateUniqueUsername($data['username']);

        $data['username'] = $uniqueUsername;
        $data['name'] = $request->input('name');
        $data['role'] = $request->input('role');
        $data['department'] = $request->input('department');
        $data['password'] = $request->input('password');

        $judge = users::CreateUser($data);

        if (!$judge){
            return json_fail('添加失败',$judge,100);
        }
        return json_success('添加成功',null,200);
    }

    //超级管理员重置教师密码
    public function reset_user(Request $request)
    {
        // 解析 JWT Token 并获取 payload
        $payload = JWTAuth::parseToken()->getPayload();

        // 获取 role 信息
        $role = $payload->get('role');

        // 判断用户角色权限
        if ($role !== 'super_admin') {
            return json_fail('权限不足', null, 100);
        }

        $data['id'] = $request->input('id');
        $data['username'] = $request->input('username');
        $data['name'] = $request->input('name');
        $data['department'] = $request->input('department');
        $data['password'] = $request->input('password');

        $judge = users::UpdatedUser($data);

        if (!$judge){
            return json_fail('修改失败',$judge,100);
        }
        return json_success('修改成功',null,200);
    }

    //超级管理员查询教师用户信息
    public function search_teacher(Request $request)
    {
        // 解析 JWT Token 并获取 payload
        $payload = JWTAuth::parseToken()->getPayload();

        // 获取 role 信息
        $role = $payload->get('role');

        // 判断用户角色权限
        if ($role !== 'super_admin') {
            return json_fail('权限不足', null, 100);
        }

        $name = $request->input('name');

        //通过姓名查找教师用户信息
        $result = users::search_teacher($name);

        if (!$result){
            return json_fail('查询失败',$result,100);
        }
        return json_success('查询成功',$result,200);
    }


    //管理员查询课程安排表
//    public function courses_assignments_search(Request $request)
//    {
//        // 解析 JWT Token 并获取 payload
//        $payload = JWTAuth::parseToken()->getPayload();
//
//        // 获取 role 信息
//        $role = $payload->get('role');
//
//        // 判断用户角色权限
//        if ($role !== 'super_admin' && $role !== 'admin') {
//            return json_fail('权限不足', null, 100);
//        }
//
//        $semester = $request->input('semester');
//
//        $result = course_assignments::courses_assignments_search($semester);
//
//        if (!$result){
//            return json_fail('查询失败',$result,100);
//        }
//        return json_success('查询成功',$result,200);
//    }

    //管理员通过课程和班级精确查找数据
    public function assignments_search(Request $request)
    {
        // 解析 JWT Token 并获取 payload
        $payload = JWTAuth::parseToken()->getPayload();

        // 获取 role 信息
        $role = $payload->get('role');

        // 判断用户角色权限
        if ($role !== 'super_admin' && $role !== 'admin') {
            return json_fail('权限不足', null, 100);
        }

        // 获取请求参数
        $data['ask'] = $request->input('ask');
        $data['semester'] = $request->input('semester');

        // 检查学期是否有效
        $judge = semesters::find_judge($data['semester']);

        if (!$judge) {
            return json_fail('学期无效', null, 100);
        }

        // 查询课程安排
        $result = course_assignments::assignments_search($data);

        if (!$result) {
            return json_fail('查询失败', $result, 100);
        }

        // 将查询的学期数据和课程安排数据合并
        $combinedResult = [
            'semester_info' => $judge, // 这里返回学期相关的信息
            'course_assignments' => $result, // 这里返回课程安排的数据
        ];

        // 返回合并后的结果
        return json_success('查询成功', $combinedResult, 200);
    }


    //管理员添加新学期
    public function create_semester(Request $request)
    {
        // 解析 JWT Token 并获取 payload
        $payload = JWTAuth::parseToken()->getPayload();

        // 获取 role 信息
        $role = $payload->get('role');

        // 判断用户角色权限
        if ($role !== 'super_admin' && $role !== 'admin') {
            return json_fail('权限不足', null, 100);
        }

        $data['semester'] = $request->input('semester');
        $data['status'] = $request->input('status');

        $result = semesters::create_semester($data);

        if (!$result){
            return json_fail('添加失败',$result,100);
        }
        return json_success('添加成功',null,200);
    }

    public function ceshi(Request $request)
    {
        $p = $request->input('p');
        $p = Crypt::encrypt($p);
        if (!$p){
            return json_fail('添加失败',$p,100);
        }
        return json_success('添加成功',$p,200);
    }

    // 管理员添加新学期
    public function choose_teacher(Request $request)
    {
        // 解析 JWT Token 并获取 payload
        $payload = JWTAuth::parseToken()->getPayload();

        // 获取 role 信息
        $role = $payload->get('role');

        // 判断用户角色权限
        if ($role !== 'super_admin' && $role !== 'admin' && $role !== 'head') {
            return json_fail('权限不足', null, 100);
        }

        // 获取请求数据
        $data['semester'] = $request->input('semester');;
        $data['old_id'] = $request->input('old_id');
        $data['new_id'] = $request->input('new_id');
        $data['id'] = $request->input('id');
        // 查找该课程的学时
        $data['hours'] = $request->input('hours');

        // 如果课程学时无效，返回错误
        if (!is_numeric($data['hours'])) {
            return json_fail('课程学时无效', null, 100);
        }

        // 如果原教师存在
        if ($data['old_id'] !== null) {
            // 处理旧教师数据，更新学时、课程数量和班级数量
            $p = teacher_semester_stats::updateOldTeacherStats($data);
            if ($p === 'error') {
                return json_fail('时长,班级修改失败', null, 100);
            }

            // 更新课程分配信息中的教师 ID
            $result = course_assignments::revise_teacher_id($data);
            if ($result === 'error') {
                return json_fail('teacher_id 修改失败', null, 100);
            }

            // 更新旧教师的学期统计数据
            $oldCourseCount = course_assignments::getTeacherCourseCount($data['old_id']); // 获取旧教师教授课程的数量
            if ($oldCourseCount === 'error') {
                return json_fail('获取旧教师课程数量失败', null, 100);
            }
            $updateOldTeacherStats = teacher_semester_stats::updateTeacherCourseCount($data['old_id'], $oldCourseCount); // 更新旧教师学期统计数据
            if ($updateOldTeacherStats === 'error') {
                return json_fail('更新旧教师学期统计数据失败', null, 100);
            }
        } else {
            // 如果原教师不存在（即课程没有分配过教师）
            $result = course_assignments::create_teacher_id($data);
            if ($result === 'error') {
                return json_fail('数据添加失败', null, 100);
            }
        }

        // 在新教师的学期统计数据中更新或添加数据
        $q = teacher_semester_stats::updateOrCreateTeacherSemesterStats($data);
        if ($q === 'error') {
            return json_fail('新教师学期统计数据添加失败', null, 100);
        }

        // 获取新教师教授课程的数量
        $newCourseCount = course_assignments::getTeacherCourseCount($data['new_id']);
        if ($newCourseCount === 'error') {
            return json_fail('获取新教师课程数量失败', null, 100);
        }

        // 更新新教师学期统计数据
        $updateNewTeacherStats = teacher_semester_stats::updateTeacherCourseCount($data['new_id'], $newCourseCount);
        if ($updateNewTeacherStats === 'error') {
            return json_fail('更新新教师学期统计数据失败', null, 100);
        }

        return json_success('添加成功', null, 200);
    }

    public function delete_course_assignments(Request $request){
        // 解析 JWT Token 并获取 payload
        $payload = JWTAuth::parseToken()->getPayload();

        // 获取 role 信息
        $role = $payload->get('role');

        // 判断用户角色权限
        if ($role !== 'super_admin' && $role !== 'admin') {
            return json_fail('权限不足', null, 100);
        }

        // 获取请求数据
        $data['semester'] = $request->input('semester');;
        $data['old_id'] = $request->input('old_id');
        $data['new_id'] = $request->input('new_id');
        $data['id'] = $request->input('id');
        // 查找该课程的学时
        $data['hours'] = $request->input('hours');
        $data['name'] = $request->input('name');

        //前提条件传入的new_id必须为空

        //该课程未安排教师
        if($data['old_id'] == null && $data['new_id'] == null){
            $deleteCourses = courses::delete_courses($data['id']);
            if ($deleteCourses === 'error') {
                return json_fail('课程表数据删除失败', null, 100);
            }
        }else {
            //该课程已经了安排教师
            //根据old_id删除课程安排表中的数据
            $deleteCourse_assignments = course_assignments::deleteCourse_assignments($data['id']);
            if ($deleteCourse_assignments === 'error') {
                return json_fail('删除课程安排表失败', null, 100);
            }

            // 处理旧教师数据，更新学时、课程数量和班级数量
            $p = teacher_semester_stats::updateOldTeacherStats($data);
            if ($p === 'error') {
                return json_fail('时长,班级修改失败', null, 100);
            }
            // 更新旧教师的学期统计数据
            $oldCourseCount = course_assignments::getTeacherCourseCount($data['old_id']); // 获取旧教师教授课程的数量
            if ($oldCourseCount === 'error') {
                return json_fail('获取旧教师课程数量失败', null, 100);
            }
            $updateOldTeacherStats = teacher_semester_stats::updateTeacherCourseCount($data['old_id'], $oldCourseCount); // 更新旧教师学期统计数据
            if ($updateOldTeacherStats === 'error') {
                return json_fail('更新旧教师学期统计数据失败', null, 100);
            }
            $deleteCourses = courses::delete_courses($data['id']);
            if ($deleteCourses === 'error') {
                return json_fail('课程表数据删除失败', null, 100);
            }
        }
        //处理only_coures表
        //更新或者删除only_course表中的数据
        $updateOnlyCourses = only_courses::updateNumber_Classes($data);
        if ($updateOnlyCourses === 'error') {
            return json_fail('课程安排表更新或者删除失败', null, 100);
        }
        return json_success('课程表删除成功',null,200);
    }
}







use Illuminate\Http\Request;

class WdwController extends Controller
{
    //
}
