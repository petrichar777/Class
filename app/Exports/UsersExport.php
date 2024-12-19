<?php

namespace App\Exports;

use App\Models\Users;
use Illuminate\Support\Facades\Crypt;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UsersExport implements FromCollection, WithHeadings
{
    protected $department;

    // 构造函数接收 department 参数
    public function __construct($department)
    {
        $this->department = $department;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // 如果 department 有值，进行筛选，否则导出所有数据
        $usersQuery = Users::select('username', 'password', 'role', 'name', 'department');

        // 如果传入了 department，按部门过滤
        if ($this->department) {
            $usersQuery->where('department', $this->department);
        }

        // 获取数据
        $users = $usersQuery->get();

        // 解密密码
        $decryptedUsers = $users->map(function ($user) {
            try {
                $user->password = Crypt::decrypt($user->password); // 解密密码
            } catch (\Exception $e) {
                $user->password = '解密失败'; // 解密失败的标记
            }
            return [
                'username' => $user->username,
                'password' => $user->password,
                'role' => $user->role,
                'name' => $user->name,
                'department' => $user->department,
            ];
        });

        // 确保返回的是 Collection 对象
        return collect($decryptedUsers);
    }

    public function headings(): array
    {
        return ['用户名', '密码', '角色', '姓名', '系别'];
    }
}

