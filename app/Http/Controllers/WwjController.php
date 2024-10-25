<?php

namespace App\Http\Controllers;
use App\Exports\ResearchStarExport;
use App\Exports\SoftwareStarExport;
use App\Models\admins;
use App\Models\company_stars;
use App\Models\competition_stars;
use App\Models\paper_stars;
use App\Models\research_stars;
use App\Models\software_stars;
use Illuminate\Routing\Controller;
use App\Models\science_star_registrations;
use App\Models\students;
use App\Mail\VerificationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Exports\CompetitionStarExport;
use App\Exports\PaperStarExport;
use Maatwebsite\Excel\Facades\Excel;

class WwjController extends Controller
{

    //管理员查询
    public function getStudentsByCompetition(Request $request, $competitionId)
    {
        // 获取管理员信息
        $admin = admins::find($request->user()->id);
        if (!$admin) {
            return response()->json(['error' => 'Admin not found'], 404);
        }


        // 查询本专业的学生
        $students = students::where('major', $admin->major)->get();

        // 根据学生ID查询对应竞赛的信息
        $competitionStudents = [];
        foreach ($students as $student) {
            $competitionInfo = null;

            // 根据竞赛ID动态获取相应的竞赛表信息
            switch ($competitionId) {
                case 'company_stars':
                    $competitionInfo = company_stars::where('student_id', $student->id)->first();
                    break;
                case 'competition_stars':
                    $competitionInfo = competition_stars::where('student_id', $student->id)->first();
                    break;
                case 'research_stars':
                    $competitionInfo = research_stars::where('student_id', $student->id)->first();
                    break;
                case 'software_stars':
                    $competitionInfo = software_stars::where('student_id', $student->id)->first();
                    break;
                case 'paper_stars':
                    $competitionInfo= paper_stars::where('student_id', $student->id)->first();
                    break;
            }

            if ($competitionInfo) {
                $competitionStudents[] = [
                    'students' => $student,
                    'competition_info' => $competitionInfo,
                ];
            }
            return response()->json([
                'competitionStudents' => $competitionStudents,
                'student_count' => count($students),
            ]);

        }
        return response()->json($competitionStudents);
    }
    //邮箱发送接口
    public function sendVerificationCode(Request $request)
    {
        //使用 Laravel 的验证器 Validator 来检查用户提供的邮箱地址是否符合规定格式（必须存在且为有效的邮箱格式）
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        //失败处理
        if ($validator->fails()) {
            return json_fail(['status' => 'fail', 'message' => '邮箱格式不正确', 'code' => 400]);
        }
        //随机数生成，从中随机选取六个数字
        $code = rand(100000, 999999); // 生成随机验证码
        $encodedCode = base64_encode($code); // 加密验证码
        Session::put('verification_code', $encodedCode); // 在会话层存储加密后的验证码
        Session::put('email', $request->email);  //在会话层存储用户邮箱

        try {
            Mail::to($request->email)->send(new VerificationCode($code)); // 发送未加密的验证码
            return json_success(['status' => 'success', 'message' => '验证码已发送', 'code' => 200]);//判断
        } catch (\Exception $e) {
            return json_fail(['status' => 'fail', 'message' => '邮件发送失败: ' . $e->getMessage(), 'code' => 500]);
        }
    }


//    public function Wwjregister(Request $request)
//    {
//        $sessionCode = Session::get('verification_code'); //在会话层获取验证码
//        $sessionEmail = Session::get('email');
//        $decodedSessionCode = base64_decode($sessionCode);  //将验证码解密
//        //判断验证码是否匹配
//        if ($request->verification_code != $decodedSessionCode || $request->email != $sessionEmail) {
//            return json_fail(['status' => 'fail', 'message' => '验证码错误或邮箱不匹配', 'code' => 400]);
//        }
//
//        $registeredInfo = [
//            'account' => $request->account,
//            'grade' => $request->grade,
//            'major' => $request->major,
//            'class' => $request->class,
//            'name' => $request->name,
//            'password' => Hash::make($request->password), //对密码进行哈希加密
//            'email' => $request->email,
//        ];
//        //查询账号，进行判断
//        $count = students::where('account', $request->account)->count();
//
//        if ($count > 0) {
//            return json_fail(['status' => 'fail', 'message' => '该用户信息已经被注册过了', 'code' => 101]);
//        }
//
//        try {
//            //在数据库中插入相应信息
//            $student = students::create($registeredInfo);
//            return json_success(['status' => 'success', 'message' => '注册成功!', 'data' => $student->id, 'code' => 200]);
//        } catch (\Exception $e) {
//            return json_fail(['status' => 'fail', 'message' => '注册失败: ' . $e->getMessage(), 'code' => 100]);
//        }
//    }

    //学生注册接口
    public function WdwStudentRegister(Request $request){
        $user['account'] = $request['account'];
        $user['grade'] = $request['grade'];
        $user['major'] = $request['major'];
        $user['class'] = $request['class'];
        $user['name'] = $request['name'];
        $user['password'] = $request['password'];
        $user['password_confirmation'] = $request['password_confirmation'];
        $user['email'] = $request['email'];
        $account = $user['account'];
        $inputCode = $request->input('verification_code'); // 用户输入的验证码
        //验证验证码是否一致
        $sessionCode = session('verification_code');
        $sessionCode = base64_decode($sessionCode);
        if ($inputCode != $sessionCode) {
            return json_fail('注册失败，验证码不正确', null, 102);
        }

        if( $user['password'] != $user['password_confirmation']){
            return json_fail('注册失败，两次输入的密码不一致',$user,100);
        }

        $count = students::WdwUserCheckNumber($account);
        if($count == 0){
            $data = students::WdwcreateUser($user);
            if(is_error($data) == true){
                return json_fail('注册失败,添加数据的时候有问题',$data,100);
            }else{
                return json_success('注册成功！',$data,200);
            }
        }else{
            return json_fail('注册失败，该用户信息已经被注册过了',null,100);
        }
    }

    public function forgotPassword(Request $request)
    {
        // 获取新密码
        $new_password = $request->password;

        // 查找学生
        $student = students::where('account', $request->account);

        if (!$student) {
            return json_fail(['status' => 'fail', 'message' => '该学生未注册', 'code' => 404]);
        }

        // 验证验证码
        $sessionCode = Session::get('verification_code');
        $sessionEmail = Session::get('email');
        $decodedSessionCode = base64_decode($sessionCode);

        if ($request->verification_code != $decodedSessionCode || $request->email != $sessionEmail) {
            return json_fail(['status' => 'fail', 'message' => '验证码错误或邮箱不匹配', 'code' => 400]);
        }

        try {
            // 更新密码
            students::where('account', $request->account)->where('email', $request->email)->update(['password' => Hash::make($new_password)]);
            return json_success(['status' => 'success', 'message' => '密码重置成功', 'code' => 200]);
        } catch (\Exception $e) {
            return json_fail(['status' => 'fail', 'message' => '密码重置失败: ' . $e->getMessage(), 'code' => 500]);
        }
    }

    //导出竞赛之星
    public function exportCompetitionStar()
    {
        return Excel::download(new CompetitionStarExport, '竞赛之星.xlsx');
    }

    //导出双创之星
    public function exportCompanyStar()
    {
        return Excel::download(new CompetitionStarExport(), '双创之星.xlsx');
    }

    //导出科技之星
    public function exportPaperStar()
    {
        return Excel::download(new PaperStarExport, '科研之星-论文.xlsx');
    }

    public function exportSoftwareStar()
    {
        return Excel::download(new SoftwareStarExport,'科研之星—软著。xlsx');
    }

    public function exportResearchStar()
    {
        return Excel::download(new ResearchStarExport,'科研之星-科研.xlsx');
    }

    public function ViewCompanyStar(Request $request)
    {
        try {


        // 直接查询 admins 表获取管理员信息
        $adminId = $request->input('admin_id'); // 假设你通过请求传递 admin_id
        $admin = DB::table('admins')->where('id', $adminId)->first();  // 获取管理员的信息
//        $adminMajor = admins::with('major')->where('id', $adminId)->get();
        $adminMajor = $admin->major;
        // 检查管理员是否存在
        if (!$admin) {
            return response()->json([
                'code' => 1,
                'msg' => '管理员不存在',
                'data' => null
            ]);
        }

        if (!$adminMajor) {
            return response()->json([
                'code' => 1,
                'msg'=>'管理员专业不存在',
                'data' => null
            ]);
        }
//        // 确认管理员信息和 major 是否存在
//        if (!isset($admin->major)) {
//            return response()->json([
//                'code' => 1,
//                'msg' => '管理员专业信息不存在',
//                'data' => null
//            ]);
//        }

        // 根据管理员的专业查询公司星的申报信息
        $companyStars = DB::table('company_stars')
            ->leftJoin('students', 'company_stars.student_id', '=', 'students.id')
            ->where('students.major', '=', $adminMajor)  // 根据管理员的专业查询
            ->select(
                'company_stars.*',
                'students.name as student_name',
                'students.major as student_major',
                'students.email as student_email'
            )
            ->get([
                'name',
                'account',
                'class',
                'major',
                'company_name',
                'company_type',
                'applicant_rank',
                'registration_time',
                'materials',
                'status',
                'reject_reason',
            ]);

        // 检查是否查询到数据
        if ($companyStars->isEmpty()) {
            return response()->json([
                'code' => 1,
                'msg' => '没有找到相关数据',
                'data' => null
            ]);
        }

        return response()->json([
            'code' => 0,
            'msg' => '查询成功',
            'data' => $companyStars
        ]);
        }catch (\Exception $e){
            return response()->json(['code'=>500,'msg'=>$e->getMessage()]);
        }
    }

    //删除双创之星报名
    public function deleteCompanyStar(Request $request)
    {
        //从请求体中获取参数
        $id = $request->input('student_id');
        if (!$id){
            return response()->json([
                'code' => 1,
                'message'=>'为传递student_id参数'
            ]);
        }
        //检查是否存在报名信息
        $companyStar = DB::table('company_stars')->where('student_id',$id)->first();
        if (!$companyStar) {
            return response()->json([
                'code' => 1,
                'message'=>'未找到报名信息',
                'data' => null
            ]);
        }
        //删除
        try {
            DB::table('company_stars')->where('id',$id)->delete();
            return response()->json([
                'code' => 0,
                'message' => '删除成功',
                'data' => null
            ]);
        }catch (\Exception $e){
            return response()->json(['code'=>500,
                'msg'=>'删除失败'.$e->getMessage(),
                'data' => null
            ]);
        }
    }
}
