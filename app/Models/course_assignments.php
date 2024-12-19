<?php
namespace App\Models;

use App\Models\Courses;
use App\Models\Users;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

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
    protected $fillable = [
        'course_id',
        'teacher_id',
        'assigned_at',
        'head_id'
    ];

    // 日期格式转换
    protected $casts = [
        'assigned_at' => 'datetime',
    ];

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

    // 查询学期的所有课程安排
    public static function coursesAssignmentsSearch($semester)
    {
        try {
            $courses = Courses::where('semester', $semester)->get();

            // 获取与这些课程相关的所有安排记录，避免循环内查询
            $assignments = self::with(['teacher', 'head'])
                ->whereIn('course_id', $courses->pluck('id'))
                ->get()
                ->keyBy('course_id');

            return $courses->map(function ($course) use ($assignments) {
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
                    'teacher' => $assignment->teacher->name ?? null,
                    'head' => $assignment->head->name ?? null,
                ];
            });
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    // 按课程名称和班级名称查询安排
    public static function assignmentsSearch($data)
    {
        try {
            $splitData = explode(' ', $data['ask']);
            if (count($splitData) != 2) {
                return '参数格式错误，必须包含课程名称和课程ID';
            }

            $courseName = $splitData[0];
            $courseClass = $splitData[1];

            // 查询条件
            $query = Courses::where('semester', $data['semester']);
            if ($courseName) {
                $query->where('name', 'like', '%' . $courseName . '%');
            }
            if ($courseClass) {
                $query->where('class_name', 'like', '%' . $courseClass . '%');
            }

            $courses = $query->get();

            $assignments = self::with(['teacher', 'head'])
                ->whereIn('course_id', $courses->pluck('id'))
                ->get()
                ->keyBy('course_id');

            return $courses->map(function ($course) use ($assignments) {
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
                    'teacher' => $assignment->teacher->name ?? null,
                    'teacher_id' => $assignment->teacher->id ?? null,
                    'teacher_department' => $assignment->teacher->department ?? null,
                    'head' => $assignment->head->name ?? null,
                ];
            });
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    // 更新教师ID
    public static function reviseTeacherId($data)
    {
        try {
            return self::where('course_id', $data['id'])
                ->update(['teacher_id' => $data['new_id']]);
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    // 创建教师分配
    public static function createTeacherId($data)
    {
        try {
            return self::insert([
                'teacher_id' => $data['new_id'],
                'course_id' => $data['id'],
            ]);
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    // 获取教师教授的不同课程种类数量
    public static function getTeacherCourseCount($id)
    {
        try {
            $courseAssignments = self::where('teacher_id', $id)->get();
            $courseNames = $courseAssignments->map(function ($assignment) {
                return Courses::find($assignment->course_id)->name;
            })->toArray();

            return count(array_unique($courseNames));
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    // 删除课程分配
    public static function deleteCourseAssignments($id)
    {
        try {
            return self::where('course_id', $id)->delete();
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    // 确认教师授课
    public static function assignTeacher(int $courseId, int $teacherId)
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

        if (!$assignment->wasRecentlyCreated) {
            $assignment->teacher_id = $teacherId;
            $assignment->assigned_at = now();
            return $assignment->save();
        }

        return $assignment;
    }

    // 确认课程负责人
    public static function confirmHead(int $courseId, int $headId)
    {
        $assignment = self::firstOrCreate(
            ['course_id' => $courseId],
            ['head_id' => $headId, 'assigned_at' => now()]
        );

        if (!$assignment->wasRecentlyCreated) {
            $assignment->head_id = $headId;
            $assignment->assigned_at = now();
            $assignment->save();
        }

        $user = Users::find($headId);
        if ($user) {
            $user->role = 'head';
            $user->save();
        }

        return $assignment;
    }

    // 获取课程教师和负责人姓名
    public static function getTeacherAndHead(int $courseId)
    {
        return self::join('users as teachers', 'course_assignments.teacher_id', '=', 'teachers.id')
            ->join('users as heads', 'course_assignments.head_id', '=', 'heads.id')
            ->where('course_assignments.course_id', $courseId)
            ->select('teachers.name as teacher_name', 'heads.name as head_name')
            ->first();
    }

    // 教师查看自己教授的课程信息
    public static function getCourse(string $semester, int $teacherId)
    {
        return self::join('courses', 'course_assignments.course_id', '=', 'courses.id')
            ->join('semesters', 'courses.semester', '=', 'semesters.semester')
            ->where('course_assignments.teacher_id', $teacherId)
            ->where('semesters.semester', $semester)
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
                'courses.department',
            ]);
    }
}
