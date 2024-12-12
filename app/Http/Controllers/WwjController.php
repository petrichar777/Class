<?php

namespace App\Http\Controllers;

use App\Models\course_assignments;
use App\Models\courses;
use App\Models\semesters;
use App\Models\teacher_semester_stats;
use App\Models\users;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WwjController extends Controller
{
    //查询老师信息（重置密码）
    public function getTeacher()
    {
        //调用模型中的方法
        $teachers = users::getTeachers();
        if (!$teachers) {
            return json_fail('未找到符合条件的老师', null, 404, 404);
        }
        return response()->json([
            'status' => 'success',
            'data' => $teachers
        ]);
    }
    //查看已通过申请的授课老师
    public function getApprovedTeachers(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|integer|exists:courses,id'
        ]);
        $courseId = $validated['course_id'];
        $teachers = users::getApprovedTeachers($courseId);
        // 判断结果是否为空
        if ($teachers->isEmpty()) {
            // 返回错误响应
            return json_fail('未找到符合条件的老师', null, 404, 404);
        }
        // 返回成功响应
        return json_success('查询成功', $teachers);
    }

    //确认该课程正式授课教师
    public function assignTeacher(Request $request){
        $validated = $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
            'teacher_id' => 'required|integer|exists:users,id',
            'semester' =>'required|string|exists:semesters,semester'
        ]);
        $teacher = users::where('id', $validated['teacher_id'])
            ->first();
        if (!$teacher) {
            return json_fail('未找到符合条件的老师', null, 404, 404);
        }
        $assignment = course_assignments::assignTeacher($validated['course_id'], $validated['teacher_id']);
        if (!$assignment) {
            return json_fail('该教师可能未通过申请', null, 400, 400);
        }
        $teacherSemesterStats = teacher_semester_stats::SelectCourseByTeacher($validated['teacher_id'],$validated['semester']);
        if (!$teacherSemesterStats) {
            return json_fail('该教师可能未通过申请', null, 400, 400);
        }
        $updated = teacher_semester_stats::AddCourseByTeacherId($validated['teacher_id'],$validated['semester'],$validated['course_id']);
        return json_success('课程分配成功', $assignment);
    }

    //查看负责人
    public function getHead(){
        $heads = users::getHead();
        if (!$heads) {
            return json_fail('未找到符合条件的负责人', null, 404, 404);
        }
        return json_success('查询成功', $heads);
    }

    //确认该课程的负责人
    public function confirmHead(Request $request){
        $validated = $request->validate([
            'course_id' => 'required|integer|exists:course_assignments,course_id',
            'head_id' =>'required|integer|exists:users,id',
        ]);
        $head = users::where('id', $validated['head_id'])
            ->first();
        if(!$head){
            return json_fail('未找到符合条件的负责人', null, 404, 404);
        }
        //更新
        $updated = course_assignments::confirmHead($validated['course_id'],$validated['head_id']);
        return json_success('成功设置该课程负责人', $updated);
    }
    //按学期查询课程信息
    public function selectCourse(Request $request)
    {
        $validated = $request->validate([
            'semester' => 'required|string|exists:teacher_semester_stats,semester',
        ]);
        $status = semesters::checkCourse($validated['semester']);
        if($status === '该学期数据不存在'){
            return json_fail('该学期数据不存在', null, 404, 404);
        }
        $courses = courses::getCourseInfo($validated['semester']);
        if($status === '选课已结束'){
            foreach($courses as &$course){
                $courseAssigment = course_assignments::getTeacherAndHead($course->id);
                if ($courseAssigment){
                    $course->teacher_name = $courseAssigment->teacher_name;
                    $course->head_name = $courseAssigment->head_name;
                }else{
                    $course->teacher_name = null;
                    $course->head_name = null;
                }
            }
        }
        return json_success('查询成功', [
            'courses' => $courses,
            'status' => $status
        ],200);
    }
    //教师查看正式授课信息
    public function getCourseByTeacher(Request $request)
    {
        $validated = $request->validate([
            'teacher_id' => 'required|integer|exists:users,id',
            'semester' => 'required|string|exists:semesters,semester',
        ]);
        $courses = course_assignments::getCourse($validated['semester'],$validated['teacher_id']);
        if ($courses->isEmpty()) {
            return json_fail('该教师没有正式的授课课程', null, 404, 404);
        }
        return json_success('查询成功', $courses);
    }
    //结束选课
    public function endCourse(Request $request)
    {
        $validated = $request->validate([
            'semester' => 'required|string|exists:semesters,semester',
        ]);
        $status = semesters::endCourse($validated['semester']);
        if ($status === '该学期数据不存在'){
            return json_fail('该学期数据不存在', null, 404, 404);
        }else
        return json_success('成功结束选课', $status);
    }
}
