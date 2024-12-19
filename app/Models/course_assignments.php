<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use function Symfony\Component\Translation\t;


class course_assignments extends Model
{
    use HasFactory;


    // 数据库表名
    protected $table = 'course_assignments';

    // 主键
    protected $primaryKey = 'id';

    // 自动维护时间戳
    public $timestamps = true;

    // 可填充字段
    protected $guarded = [];

    /**
     * 关联课程表
     * 每条课程分配记录都属于一个课程
     */
    public function course()
    {
        return $this->belongsTo(Courses::class, 'course_id', 'id');
    }

    /**
     * 关联教师（用户表）
     * 每条课程分配记录都有一个教师
     */
    public function teacher()
    {
        return $this->belongsTo(Users::class, 'teacher_id', 'id');
    }

    /**
     * 关联负责人（用户表）
     * 每条课程分配记录都有一个负责人
     */
    public function head()
    {
        return $this->belongsTo(Users::class, 'head_id', 'id');
    }

    public static function courses_assignments_search($semester)
    {
        try {
            // 查询 courses 表中符合条件的所有课程
            $courses = courses::where('semester', $semester)->get();

            // 获取与这些课程相关的所有安排记录，避免循环内查询
            $assignments = course_assignments::with(['teacher', 'head'])
                ->whereIn('course_id', $courses->pluck('id'))
                ->get()
                ->keyBy('course_id'); // 以 course_id 为键方便查找

            // 遍历课程，组合数据
            $result = $courses->map(function ($course) use ($assignments) {
                $assignment = $assignments->get($course->id);

                return [
                    'id' => $course->id,
                    'semester' => $course->semester,
                    'name' => $course->name,
                    'code' => $course->code,
                    'category' => $course->category,
                    'nature' => $course->nature,
                    'credit' => $course->credit,
                    'hours' => $course->hours,
                    'grade' => $course->grade,
                    'class_name' => $course->class_name,
                    'class_size' => $course->class_size,
                    'department' => $course->department,
                    'teacher' => $assignment->teacher->name ?? null, // 如果有安排，则返回 teacher
                    'head' => $assignment->head->name ?? null,       // 如果有安排，则返回 head
                ];
            });

            return $result;
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    public static function assignments_search($data)
    {
        try {
            // 基本的查询条件是按学期筛选
            $query = courses::where('semester', $data['semester']);

            // 使用空格拆分传入的字符串
            $splitData = explode(' ', $data['ask']);

            if (count($splitData) != 2) {
                return '参数格式错误，必须包含课程名称和课程ID';
            }

            // 获取拆分后的课程名称和课程ID
            $courseName = $splitData[0];
            $courseClass = $splitData[1];

            // 根据传入的 name 字段进行筛选
            if (isset($courseName)) {
                $query->where('name', 'like', '%' . $courseName . '%'); // 模糊匹配课程名称
            }

            // 根据传入的 class 字段进行筛选
            if (isset($courseClass)) {
                $query->where('class_name', 'like', '%' . $courseClass . '%'); // 模糊匹配班级名称
            }

            // 获取符合条件的课程
            $courses = $query->get();

            // 获取与这些课程相关的所有安排记录，避免循环内查询
            $assignments = course_assignments::with(['teacher', 'head'])
                ->whereIn('course_id', $courses->pluck('id'))
                ->get()
                ->keyBy('course_id'); // 以 course_id 为键方便查找

            // 遍历课程，组合数据
            $result = $courses->map(function ($course) use ($assignments) {
                $assignment = $assignments->get($course->id);

                return [
                    'id' => $course->id,
                    'semester'=> $course->semester,
                    'name' => $course->name,
                    'code' => $course->code,
                    'category' => $course->category,
                    'nature' => $course->nature,
                    'credit' => $course->credit,
                    'hours' => $course->hours,
                    'grade' => $course->grade,
                    'class_name' => $course->class_name,
                    'class_size' => $course->class_size,
                    'department' => $course->department,
                    'teacher' => $assignment->teacher->name ?? null, // 如果有安排，则返回 teacher
                    'teacher_id' => $assignment->teacher->id ?? null,
                    'teacher_department' => $assignment->teacher->department ?? null,
                    'head' => $assignment->head->name ?? null,       // 如果有安排，则返回 head
                ];
            });

            return $result;
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    public static function revise_teacher_id($data)
    {
        try {
            // 根据 id 查找对应记录，并更新 teacher_id
            $result = course_assignments::where('course_id', $data['id'])
                ->update(['teacher_id' => $data['new_id']]);

            // 返回更新的结果，更新成功时返回受影响的行数
            return $result;
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    public static function create_teacher_id($data)
    {
        try {
            $result = course_assignments::insert([
                'teacher_id' => $data['new_id'],
                'course_id' => $data['id'],
            ]);
            return $result;
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    // 获取教师教授的不同课程种类的数量
    public static function getTeacherCourseCount($id)
    {
        try {
            // 获取该教师教授的所有课程信息
            $courseAssignments = course_assignments::where('teacher_id', $id)->get();

            // 存储所有课程的 name
            $courseNames = [];

            // 遍历查找对应的课程名称
            foreach ($courseAssignments as $assignment) {
                // 根据 course_id 查找对应课程的名称
                $course = courses::find($assignment->course_id);
                if ($course) {
                    // 将课程名称添加到数组中
                    $courseNames[] = $course->name;
                }
            }

            // 去除重复的课程名称，获取不同种类的课程名称
            $uniqueCourseNames = array_unique($courseNames);

            // 返回不同的课程种类数量
            return count($uniqueCourseNames);
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    public static function deleteCourse_assignments($id)
    {
        try {
            $result = course_assignments::where('course_id',$id)
                ->delete();
            return $result;
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }




    protected $table = 'course_assignments';
    protected $fillable = [
        'course_id',
        'teacher_id',
        'assigned_at',
        'head_id'
    ];
    public $timestamps = false;
    protected $casts = [
        'assigned_at' => 'datetime',
    ];
    //确认该课程正式授课教师
    public static function assignTeacher(int $courseId,int $teacherId)
    {
        $applicationExists = DB::table('course_applications')
            ->where('course_id', $courseId)
            ->where('teacher_id', $teacherId)
            ->where('status', 'approved')
            ->exists();
        if (!$applicationExists) {
            return false;
        }
        $assignment = self::firstOrCreate(
            ['course_id' => $courseId],
            ['teacher_id' => $teacherId, 'assigned_at' => now()]
        );
        // 如果记录存在且需要更新
        if ($assignment->wasRecentlyCreated === false) {
            $assignment->teacher_id = $teacherId;
            $assignment->assigned_at = now();
            return $assignment->save();
        }
        return $assignment;
    }

//    //确认该课程的负责人
//    public static function confirmHead(int $courseId, int $headId)
//    {
//        $assignment = self::firstOrCreate(
//            ['course_id' => $courseId], // 根据课程 ID 查找记录
//            ['head_id' => $headId, 'assigned_at' => now()] // 如果记录不存在，插入数据
//        );
//        if (!$assignment) {
//            return false;
//        }
//        // 如果记录存在且需要更新
//        if ($assignment->wasRecentlyCreated === false) {
//            $assignment->head_id = $headId;
//            $assignment->assigned_at = now();
//            return $assignment->save();
//        }
//        return $assignment;
//    }

    //确认该课程的负责人
    public static function confirmHead(int $courseId, int $headId)
    {
        $assignment = self::firstOrCreate(
            ['course_id' => $courseId],
            ['head_id' => $headId, 'assigned_at' => now()]
        );
        if ($assignment->wasRecentlyCreated === false) {
            $assignment->head_id = $headId;
            $assignment->assigned_at = now();
            $assignment->save();
        }
        $user = users::find($headId);
        if ($user) {
            $user->role = 'head';
            $user->save();
        }
        return $assignment;
    }
    //查询授课老师和负责人姓名
    public static function getTeacherAndHead(int $courseId){
        return self::join('users as teachers', 'course_assignments.teacher_id', '=', 'teachers.id')
            ->join('users as heads', 'course_assignments.head_id', '=', 'heads.id')
            ->where('course_assignments.course_id', $courseId)
            ->select('teachers.name as teacher_name', 'heads.name as head_name')
            ->first();
    }
    //教师查看正式授课的课程信息
    public static function getCourse(String $semester,int $teacherId)
    {
        return self::join('courses', 'course_assignments.course_id', '=', 'courses.id')
            ->join('semesters','courses.semester','=','semesters.semester')
            ->where('course_assignments.teacher_id',$teacherId)
            ->where('semesters.semester',$semester)
            ->get([
                'courses.id',
                'courses.name',
                'courses.code',
                'courses.category',
                'courses.nature',
                'courses.credit',
                'courses.hours',
                'courses.semester',
                'courses.class_name',
                'courses.class_size',
                'courses.department'
            ]);
    }

}
