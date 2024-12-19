<?php

namespace App\Http\Controllers;

use App\Models\course_applications;
use App\Models\course_assignments;
use App\Models\courses;
use App\Models\only_courses;
use App\Models\semesters;
use App\Models\users;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Tymon\JWTAuth\Facades\JWTAuth;

class LywController extends Controller
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

    //用户登出
    public function logout(Request $request)
    {
        try {
            //使得当前用户的token失效
            JWTAuth::invalidate(JWTAuth::getToken());
            return json_success('登出成功', null, 200);
        } catch (\Exception $e) {
            return json_fail('登出失败', $e, 100);
        }
    }

    public function audit(Request $request)
    {
        // 解析 JWT Token 并获取 payload
        $payload = JWTAuth::parseToken()->getPayload();

        // 获取 role 信息（假如获取更多信息只需要）
        //$role = $payload->get('id');
        //$role = $payload->get('name');
        $role = $payload->get('role');

        // 判断用户角色权限
        if ($role !== 'head' && $role !== 'admin') {
            return json_fail('权限不足', null, 100);
        }

        // 验证输入
        $data = $request->validate([
            'only_course_id' => 'required|int',
            'teacher_id' => 'required|int',
            'action' => 'required|string|in:approve,reject',
            'semester' => 'required|string'
        ]);

        // 获取 course_id 和 action
        $OnlyCourseId = $data['only_course_id'];
        $teacherId = $data['teacher_id'];
        $action = $data['action'];
        $semester = $data['semester'];
        $LywCheck = semesters::where('semester', $semester)
            ->where('status', 'InProgress')
            ->exists();
        if (!$LywCheck) {
            return json_fail('学期过期，请切换学期', null, 100);
        }


        // 查找数据库中的对应记录
        $courseApplication = course_applications::where('only_course_id', $OnlyCourseId)
            ->where('teacher_id', $teacherId)
            ->first();

        if ($courseApplication) {
            // 设置新的 status 和 message
            $newStatus = '';
            $message = '';

            switch ($action) {
                case 'approve':
                    $newStatus = 'approved';
                    $message = '审核通过';
                    break;
                case 'reject':
                    $newStatus = 'rejected';
                    $message = '审核不通过';
                    break;
                default:
                    $newStatus = 'pending';
                    $message = '状态未知';
            }

            // 更新 status 并保存
            $courseApplication->status = $newStatus;
            $courseApplication->save();

            // 返回自定义格式的响应
            return response()->json([
                'status' => 'success',
                'message' => $message
            ]);
        } else {
            // 如果没有找到记录
            return response()->json([
                'status' => 'error',
                'message' => 'Course application not found'
            ], 404);
        }
    }

    public static function CheckApplication(Request $request)//查询申请的课程数据
    {
        // 解析 JWT Token 并获取 payload
        $payload = JWTAuth::parseToken()->getPayload();

        // 获取 role 信息（假如获取更多信息只需要）
        //$role = $payload->get('id');
        //$role = $payload->get('name');
        $role = $payload->get('role');

        // 判断用户角色权限
        if ($role !== 'head' && $role !== 'admin') {
            return json_fail('权限不足', null, 100);
        }

        $data = $request->validate([
            'semester' => 'required|string',
        ]);

        $semester = $data['semester'];
        $LywCheck = semesters::where('semester', $semester)
            ->where('status', 'InProgress')
            ->exists();
        if (!$LywCheck) {
            return json_fail('学期过期，请切换学期', null, 100);
        }

        $courseIds = course_applications::pluck('course_id'); // 获取所有的 course_id
        if ($courseIds->isEmpty()) {
            return json_fail('没有找到相关课程申请', null, 100);
        }

        // 查询 course 表中所有 course_id 在 $courseIds 列表中的课程，并且 semester 匹配
        $courses = courses::whereIn('id', $courseIds)  // 查找所有 course_id 在 course_application 表中的课程
        ->where('semester', $semester)
            ->get(['id', 'name', 'code', 'category', 'nature', 'credit', 'hours', 'grade', 'class_name', 'class_size', 'department', 'semester']);

        if ($courses->isEmpty()) {
            return json_fail('没有找到该学期的课程', null, 100);
        }
        return json_success('找到相关课程数据', $courses, 200);
    }

    public static function approved_teachers(Request $request)//查询申通过老师的信息
    {
        // 解析 JWT Token 并获取 payload
        $payload = JWTAuth::parseToken()->getPayload();

        // 获取 role 信息（假如获取更多信息只需要）
        //$role = $payload->get('id');
        //$role = $payload->get('name');
        $role = $payload->get('role');

        // 判断用户角色权限
        if ($role !== 'head' && $role !== 'admin') {
            return json_fail('权限不足', null, 100);
        }
        $data = $request->validate([
            'course_id' => 'required|int',
            'semester' => 'required|string'
        ]);
        $courseId = $data['course_id'];
        $semester = $data['semester'];
        $LywCheck = semesters::where('semester', $semester)
            ->where('status', 'InProgress')
            ->exists();
        if (!$LywCheck) {
            return json_fail('学期过期，请切换学期', null, 100);
        }

        $teacherId = course_assignments::where('course_id', $courseId)
            ->pluck('teacher_id');
        if ($teacherId->isEmpty()) {
            return json_fail('没有通过的老师', null, 100);
        }
        $Users = users::whereIn('id', $teacherId)
            ->get(['role', 'name', 'department']);
        if ($Users->isEmpty()) {
            return json_fail('没有找到信息', null, 100);
        }
        return json_success('找到相关老师数据', $Users, 200);
    }

    public static function filter_assign(Request $request)//下拉框分级查询
    {
        // 解析 JWT Token 并获取 payload
        $payload = JWTAuth::parseToken()->getPayload();

        // 获取 role 信息（假如获取更多信息只需要）
        //$role = $payload->get('id');
        //$role = $payload->get('name');
        $role = $payload->get('role');

        // 判断用户角色权限
        if ($role !== 'head' && $role !== 'admin') {
            return json_fail('权限不足', null, 100);
        }

        $semester = $request->input('semester');
        $filter = $request->input('filter');
        $LywCheck = semesters::where('semester', $semester)
            ->where('status', 'InProgress')
            ->exists();
        if (!$LywCheck) {
            return json_fail('学期过期，请切换学期', null, 100);
        }

        // 初始查询：根据学期筛选
        $query = courses::where('semester', $semester);

        // 逐步筛选条件
        if (isset($filter['department'])) {
            $query->where('department', $filter['department']);
        }

        if (isset($filter['grade'])) {
            $query->where('grade', $filter['grade']);
        }

        if (isset($filter['class_name'])) {
            $query->where('class_name', $filter['class_name']);
        }

        // 获取筛选后的课程列表
        $courses = $query->get();

        // 格式化返回数据
        $result = $courses->map(function ($course) {
            return [
                'course_id' => $course->id,
                'name' => $course->name,
                'category' => $course->category,
                'nature' => $course->nature,
                'credit' => $course->credit,
                'hours' => $course->hours,
                'grade' => $course->grade,
                'class_name' => $course->class_name
            ];
        });

        return json_success('查询成功', $result, 200);
    }

    public function HeaderSearch(Request $request)//搜索框模糊查询
    {
        // 解析 JWT Token 并获取 payload
        $payload = JWTAuth::parseToken()->getPayload();

        // 获取 role 信息（假如获取更多信息只需要）
        //$role = $payload->get('id');
        //$role = $payload->get('name');
        $role = $payload->get('role');

        // 判断用户角色权限
        if ($role !== 'head' && $role !== 'admin') {
            return json_fail('权限不足', null, 100);
        }

        $query = $request->input('name');
        $class = $request->input('class_name');
        $semester = $request->input('semester');
        $LywCheck = semesters::where('semester', $semester)
            ->where('status', 'InProgress')
            ->exists();
        if (!$LywCheck) {
            return json_fail('学期过期，请切换学期', null, 100);
        }


        // 查询课程数据
        $courses = courses::query()
            ->when($query, function ($queryBuilder, $searchQuery) {
                return $queryBuilder->where('name', 'like', "%{$searchQuery}%");
            })
            ->when($class, function ($queryBuilder, $class) {
                return $queryBuilder->where('class_name', $class);
            })
            ->when($semester, function ($queryBuilder, $semester) {
                return $queryBuilder->where('semester', $semester);
            })
            ->get(['id', 'name', 'code', 'category', 'nature', 'credit', 'hours', 'grade', 'class_name', 'class_size', 'department', 'semester']);
        return json_success('查询成功', $courses, 200);
    }

    public function getCourses(Request $request)//下拉框查询
    {
        // 解析 JWT Token 并获取 payload
        $payload = JWTAuth::parseToken()->getPayload();

        // 获取 role 信息（假如获取更多信息只需要）
        //$role = $payload->get('id');
        //$role = $payload->get('name');
        $role = $payload->get('role');

        // 判断用户角色权限
        if ($role !== 'head' && $role !== 'admin') {
            return json_fail('权限不足', null, 100);
        }

        $majorId = $request->input('department');
        $gradeId = $request->input('grade');
        $classId = $request->input('class_name');
        $semester = $request->input('semester');
        $LywCheck = semesters::where('semester', $semester)
            ->where('status', 'InProgress')
            ->exists();
        if (!$LywCheck) {
            return json_fail('学期过期，请切换学期', null, 100);
        }

        // 构建查询条件
        $query = courses::query();

        // 根据传入的条件进行过滤
        if ($majorId) {
            $query->where('department', $majorId);
        }

        if ($gradeId) {
            $query->where('grade', $gradeId);
        }

        if ($classId) {
            $query->where('class_name', $classId);
        }

        // 执行查询，返回符合条件的课程列表
        $courses = $query->get(['department', 'grade', 'class_name']);

        // 返回课程列表的 JSON 响应
        return json_success('查询成功', $courses, 200);
    }
}
