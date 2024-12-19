<?php

namespace App\Http\Controllers;



use App\Models\course_assignments;
use App\Models\course_applications;
use App\Models\only_courses;
use App\Models\semesters;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Crypt;
use Tymon\JWTAuth\Facades\JWTAuth;
use   Illuminate\Routing\Controller as Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use App\Models\courses;
use App\Models\users;
class MhwController extends Controller//发表论文
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

    public function courses_grade_name()//下拉专业，年级，课程查询
    {

        $payload = JWTAuth::parseToken()->getPayload();
        $role = $payload->get('role');
        // 判断用户角色权限
        if ($role !== 'super_admin' && $role !== 'admin') {
            return json_fail('权限不足', null, 100);
        }
        // 查询数据库获取所有年级、专业和课程，并去重
        $grades = Courses::select('grade')->distinct()->pluck('grade'); // 去重年级，当数据量大的时候就用到distinct()
        $departments = Courses::select('department')->distinct()->pluck('department'); // 去重专业
        $courses = Courses::select('name')->distinct()->pluck('name'); // 去重课程

        return response()->json([
            'code' => 200,
            'message' => '获取筛选项成功',
            'data' => [
                'grades' => $grades,
                'departments' => $departments,
                'courses' => $courses
            ]
        ]);
    }
    public function courses_filter(Request $request)//分级查询,如何点击查询下拉的年级，专业，课程，实现该系统可维护性
    {

        // 解析 JWT Token 并获取 payload
        $payload = JWTAuth::parseToken()->getPayload();

        // 获取 role 信息（假如获取更多信息只需要）
        //$role = $payload->get('id');
        //$role = $payload->get('name');
        $role = $payload->get('role');

        // 判断用户角色权限
        if ($role !== 'super_admin' && $role !== 'admin') {
            return json_fail('权限不足', null, 100);
        }
        // 验证请求数据
        $validator = Validator::make($request->all(), [
            'semester' => 'nullable|string',
            'filter.department' => 'nullable|string',
            'filter.grade' => 'nullable|string',
            'filter.name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return json_fail('请求数据格式不正确', null, 100);
        }
        // 获取学期和过滤条件
        $semester = $request->input('semester'); // 学期
        $filters = $request->input('filter', []); // 过滤条件

        // 动态生成条件数组
        $conditions = array_filter([
            'semester' => $semester,
            'department' => $filters['department'] ?? null, // 专业
            'grade' => $filters['grade'] ?? null,      // 年级
            'name' => $filters['name'] ?? null,        // 学科名称
        ]);
        // 执行查询并返回结果
        // 调用模型方法查询数据
        $pan = semesters::Semester_situation($conditions);

        if ( $pan->isNotEmpty()) {
            $status = $pan->first()->status;  // 访问集合中的第一个元素的属性

            if ($status == 'InProgress'){
                $results = Courses::getCourses($conditions);
                return response()->json([
                    'code' => 200,
                    'message' => '查询成功',
                    'data' => $results
                ]);
            } elseif ($status == 'EndProgress') {
                $results = Courses::getCourses($conditions);
                return response()->json([
                    'code' => 200,
                    'message' => '查询成功，当前学期已结束',
                    'data' => $results,
                    'notice' => '当前学期已经结束，不能进行添加、修改或删除操作。'
                ]);
            }
        } else {
            return response()->json([
                'code' => 100,
                'message' => '未找到学期信息',
                'data' => []
            ]);
        }
    }
    public function courses_updater(Request $request)//修改表格信息
    {
        // 检查用户权限
        $role = JWTAuth::parseToken()->getPayload()->get('role');
        if (!in_array($role, ['super_admin', 'admin'])) {
            return response()->json([
                'code' => 100,
                'message' => '权限不足。',
            ]);
        }
        // 验证并解析请求数据
        $data = $request->validate([
            'id' => 'required|integer',        // 必须的课程 ID
            'name' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'nature' => 'nullable|string|max:100',
            'credit' => 'nullable|numeric|min:0|max:10', // 学分范围 0 - 10
            'hours' => 'nullable|integer|min:0',
            'grade' => 'nullable|string|max:100',
            'class_name' => 'nullable|string|max:255',
            'class_size' => 'nullable|integer|min:0',
            'semester' => 'nullable|string|max:255',
        ]);

        $pan = semesters::Semester_situation($data);

// 确保 $p 是集合或对象
        if (!( $pan instanceof \Illuminate\Support\Collection) || $pan->isEmpty()) {
            return response()->json([
                'code' => 100,
                'message' => '未找到学期信息。',
            ]);
        }
// 检查学期状态
        $status = $pan->first()->status;
        if ($status !== 'InProgress') {
            return response()->json([
                'code' => 100,
                'notice' => '当前学期已经结束，不能进行添加、修改或删除操作。',
            ]);
        }
        // 检查课程代码是否重复
        if (courses::where('code', $data['code'])->where('id', '!=', $data['id'])->exists()) {
            return response()->json([
                'code' => 100,
                'message' => '课程代码已存在，请检查后重新提交。',
            ]);
        }

        // 检查课程组合是否重复
        if (courses::where('name', $data['name'])
            ->where('class_name', $data['class_name'])
            ->where('grade', $data['grade'])
            ->where('semester', $data['semester'])
            ->where('id', '!=', $data['id'])
            ->exists()) {
            return response()->json([
                'code' => 100,
                'message' => '课程已存在，请检查后重新提交。',
            ]);
        }

        // 更新课程信息
        $affectedRows = courses::revise($data);

        return $affectedRows > 0
            ? response()->json([
                'code' => 200,
                'message' => '修改成功。',
                'data' => $affectedRows,
            ])
            : response()->json([
                'code' => 100,
                'message' => '修改失败，请检查数据后重试。',
            ]);
    }
    /**
     * 添加新课程。
     */
    /**
     * 添加新课程。
     */
    public function courses_add(Request $request)//手动添加课程
    {
        // 解析 JWT Token 并获取 payload
        $payload = JWTAuth::parseToken()->getPayload();

        // 获取 role 信息（假如获取更多信息只需要）
        //$role = $payload->get('id');
        //$role = $payload->get('name');
        $role = $payload->get('role');

        // 判断用户角色权限
       // if ($role !== 'super_admin' && $role !== 'admin') {
         //   return json_fail('权限不足', null, 100);
      //  }
            // 获取输入数据
            $newCourseData = [
                'name' => $request->input('name'),
                'nature'=>$request->input('nature'),
                'code' => $request->input('code'),
                'category' => $request->input('category'),
                'credit' => $request->input('credit'),
                'hours' => $request->input('hours'),
                'grade' => $request->input('grade'),
                'class_name' => $request->input('class_name'),
                'class_size' => $request->input('class_size'),
                'department' => $request->input('department'),
                'semester' => $request->input('semester'),
            ];
        // 检查学期状态
        $pan = semesters::Semester_situation($newCourseData);

// 检查学期信息是否存在
        if (!$pan) {
            return response()->json([
                'code' => 100,
                'message' => '未找到学期信息，无法添加课程。',
            ]);
        }
// 获取学期状态
        $status = $pan->status;

        if ($status !== 'InProgress') {
            return response()->json([
                'code' => 100,
                'message' => '当前学期已结束，不能进行添加操作。',
            ]);
        }

        // 检查是否在 `courses` 表中已存在完全相同的记录
        $existingCourse = courses::where('name', $newCourseData['name'])
            ->where('code', $newCourseData['code'])
            ->where('grade', $newCourseData['grade'])
            ->where('class_name', $newCourseData['class_name'])
            ->where('semester', $newCourseData['semester'])
            ->first();

        if ($existingCourse) {
            return response()->json([
                'code' => 100,
                'message' => '课程信息已存在，请勿重复添加。',
            ]);
        }

        // 添加课程到 `courses` 表
        $affectedRows = courses::create($newCourseData);

        if ($affectedRows) {
            // 检查 `only_courses` 表中是否已存在相同的课程名
            $existingOnlyCourse = only_courses::where('name', $newCourseData['name'])->first();

            if ($existingOnlyCourse) {
                // 如果存在相同的课程名，更新 `number_classes` 字段
                $existingOnlyCourse->increment('number_classes');
            } else {
                // 如果不存在相同的课程名，插入新记录到 `only_courses` 表
              $hand_insert=only_courses::hand_insert($newCourseData);//再加一个判断
                if(!$hand_insert){
                    return response()->json([
                        'code' => 100,
                        'message' => '选择面课程信息添加失败。',
                    ]);
                }

            }
            return response()->json([
                'code' => 200,
                'message' => '课程信息添加成功。',
            ]);
        }

        return response()->json([
            'code' => 100,
            'message' => '课程信息添加失败。',
        ]);
    }
    public function courses_apply(Request $request) // 老师申请课程
    {
        // 解析 JWT Token 并获取 payload
        $payload = JWTAuth::parseToken()->getPayload();

        // 获取教师 ID 和角色信息
        $teacher_id = $payload->get('id');
        $role = $payload->get('role');

        // 获取请求数据
        $data = [
            'only_course_id' => $request->input('id'),
            'teacher_id' => $teacher_id,
            'semester' => $request->input('semester'),
        ];

        // 检查学期状态
        $semesterInfo = semesters::Semester_situation($data);
        if (!$semesterInfo) {
            return response()->json([
                'code' => 100,
                'message' => '未找到学期信息，无法添加课程。',
            ]);
        }

        $status = $semesterInfo->first()->status; // 获取学期状态
        if ($status !== 'InProgress') {
            return response()->json([
                'code' => 100,
                'message' => '当前学期已结束，不能进行申请操作。',
            ]);
        }

        // 执行课程申请操作
        $isInserted = course_applications::insert_applications($data);

        if ($isInserted) {
            return response()->json([
                'code' => 200,
                'message' => '申请成功。',
            ]);
        }

        return response()->json([
            'code' => 100,
            'message' => '申请失败，请稍后重试。',
        ]);
    }
        public function courses_applied(Request $request)//老师查询已经申请的课程
        {
            // 解析 JWT Token 并获取 payload
            $payload = JWTAuth::parseToken()->getPayload();

            // 获取 teacher_id 信息
            $teacher_id = $payload->get('id');
            $role = $payload->get('role');
            // 查询 course_applications 表，获取对应的 course_id 和 status
            $applications = course_applications::where('teacher_id', $teacher_id)
                ->get(['only_course_id', 'status']);

            // 获取学期信息
            $semester = $request->input('semester'); // 学期

            // 根据查询到的 course_id 和学期 semester 查询 only_courses 表数据
            $courses = [];
            foreach ($applications as $application) {//循环遍历
                $course = only_courses::where('id', $application['only_course_id'])
                    ->where('semester', $semester)
                    ->first(); // 查询符合条件的课程

                if ($course) {
                    // 合并 status 和课程信息
                    $courses[] = [
                        'status' => $application->status,
                        'course' => $course
                    ];
                }
            }
            // 判断查询是否有数据，并返回对应响应
            if (!empty($courses)) {
                return response()->json([
                    'code' => 200,
                    'message' => '查询成功',
                    'teacher_id' => $teacher_id,
                    'semester' => $semester,
                    'courses' => $courses
                ]);
            } else {
                return response()->json([
                    'code' => 100,
                    'message' => '未查询到相关课程信息',
                    'teacher_id' => $teacher_id,
                    'semester' => $semester,
                    'courses' => []
                ]);
            }
        }
    public function courses_delete_application(Request $request) // 老师删除已经申请的课程
    {
        // 解析 JWT Token 并获取 payload
        $payload = JWTAuth::parseToken()->getPayload();
        $role = $payload->get('role');
        $teacher_id = $payload->get('id'); // 获取 teacher_id 信息

        // 获取请求数据
        $data = [
            'id' => $request->input('id'),
            'teacher_id' => $teacher_id,
            'semester' => $request->input('semester'),
        ];

        // 检查学期状态
        $semesterInfo = semesters::Semester_situation($data);
        if (!$semesterInfo) {
            return response()->json([
                'code' => 100,
                'message' => '未找到学期信息，无法删除申请。',
            ]);
        }

        $status = $semesterInfo->first()->status;
        if ($status !== 'InProgress') {
            return response()->json([
                'code' => 100,
                'message' => '当前学期已结束，不能进行删除操作。',
            ]);
        }

        // 查询申请记录，确保申请属于该老师
        $application = course_applications::where('id', $data['id'])
            ->where('teacher_id', $teacher_id)
            ->first();

        // 检查申请记录是否存在
        if (!$application) {
            return response()->json([
                'code' => 100,
                'message' => '申请记录不存在或无权限操作。',
            ]);
        }

        // 检查申请状态是否已通过
        if ($application->status === 'approved') {
            return response()->json([
                'code' => 100,
                'message' => '申请已通过审核，所以无法删除。',
            ]);
        }

        // 删除申请记录
        $isDeleted = course_applications::deleted_applications($teacher_id, $data);
        if ($isDeleted) {
            return response()->json([
                'code' => 200,
                'message' => '删除成功。',
            ]);
        }

        return response()->json([
            'code' => 100,
            'message' => '删除失败，请稍后重试。',
        ]);
    }
         public function courses_all(Request $request)//老师查看所有课程
         {
             //   解析 JWT Token 并获取 payload
             $payload = JWTAuth::parseToken()->getPayload();
             $role = $payload->get('role');

             $data['semester']=$request->input('semester');
             $Information=only_courses::check($data);
             if ($Information) {
                 return response()->json([
                     'code' => 200,
                     "message" => "查询成功",
                     'data'=>$Information,
                 ]);
             } else {
                 return response()->json([
                     'code' => 100,
                     "message" => "查询失败"
                 ]);
             }
         }}






