<?php

namespace App\Http\Controllers;

use App\Models\courses;
use App\Models\only_courses;
use App\Models\teacher_semester_stats;
use App\Models\users;
use Illuminate\Http\Request;
use App\Models\course_applications;
class WkxController extends Controller
{
    public function teacher_search_courses(Request $request)
    {
        $name= $request->input('name');
        $semester= $request->input('semester');
        $teacher_id=$request->input('id');

        $search=course_applications::see_statue($teacher_id);
        $data = only_courses::teacher_search__courses($semester,$name);
        // 遍历课程数据并添加状态
        if ($data->isEmpty()) {
            return json_fail('查询失败', null, 100);
        }
        // 遍历课程数据并添加状态
        foreach ($data as $course) {
            // 如果教师已申请该课程，则状态为"已选择"
            if ($search->contains($course->id)) {
                $course->statue = "已选择";
            } else {
                $course->statue = "未选择";
            }
        }
        return json_success('查询成功', $data, 200);
    }


    public function admins_delete_users(Request $request)
    {
        $id=$request->input('id');
        $teacher_id=$request->input('teacher_id');
        $data2=course_applications::delete_teacher($teacher_id);
        $data1 = users::delete_people($id);
        if ($data1 == 0 && $data2 == 0) {
            return json_fail('用户删除失败', null, 100);
        } else {
            return json_success('用户删除成功', $data2, 200);
        }
    }

    public function look_teachers_summary(Request $request)
    {
        $semester=$request->input('semester');
        $department=$request->input('department');
        $id=$request->input('id');

        $role=users::see_role($id);
        if ($role->contains('super_admin')){
            if ($department==null){
                $data=users::see_data1();
            }else {
                $data = users::see_data($department);
            }
        }elseif ($role->contains('admin')) {
            $data = users::see_datas($id);
        }
        $ids = collect($data)->pluck('id');//获取集合id
        $new_data=teacher_semester_stats::new_data($ids,$semester);

        if (count($data) !== count($new_data)) {
            return response()->json(['error' => '两个集合长度不一致，无法合并'], 400);
        }
        $mergedCollection = [];
        foreach ($data as $index => $item1) {
            $item2 = $new_data[$index]; // 获取第二个集合对应索引的元素
            $mergedCollection[] = array_merge($item1, $item2); // 合并两个数组
        }


        if (is_error( $mergedCollection) == true) {
            return json_fail('查询失败', null, 100);
        } else {
            return json_success('查询成功', $mergedCollection, 200);
        }
    }

    public function courses_audit(Request $request)
    {
        $course_id=$request->input('course_id');
        $teacher_id=$request->input('teacher_id');
        $status = $request->input('status');
        $data=course_applications::make_status($course_id,$teacher_id,$status);
        if (is_error($data) == true) {
            return json_fail('操作失败', null, 100);
        } else {
            return json_success('审核通过', $status, 200);
        }
    }
    public static function admin_see_courses_application(Request $request)
    {
        $id=$request->input('id');
        $role=users::see_role($id);
        if ($role->contains('super_admin')){
            $data = course_applications::see_data();
        }else if($role->contains('admin')){
            $data1=users::see_datas1($id);
            $data = course_applications::see_datas($data1);
        }
        $only_course_id = collect($data)->pluck('only_course_id');
        $teacher_id = collect($data)->pluck('teacher_id');

        $see_course=only_courses::see_course($only_course_id);
        $see_name=users::see_name($teacher_id);

        if (count($data) !== count($see_course) || count($data) !== count($see_name)) {
            return response()->json(['error' => '三个集合长度不一致，无法合并'], 400);
        }
        $mergedCollection = []; // 初始化合并后的数组
        foreach ($data as $index => $item1) {
            // 从第二个和第三个集合中获取对应索引的元素
            $item2 = $see_course[$index];
            $item3 = $see_name[$index];

            // 合并数组
            $mergedCollection[] = array_merge($item1, $item2, $item3);
        }

        if (is_error( $mergedCollection) == true) {
            return json_fail('查询失败', null, 100);
        } else {
            return json_success('查询成功', $mergedCollection, 200);
        }
    }
}
