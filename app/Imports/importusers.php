<?php

namespace App\Imports;

use App\Models\Users;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Maatwebsite\Excel\Concerns\ToCollection;

class importusers implements ToCollection
{
    private $existingUsernames; // 缓存已有用户名

    public function __construct()
    {
        // 从数据库中获取已存在的用户名
        $this->existingUsernames = Users::pluck('username')->toArray();
    }

    public function collection(Collection $rows)
    {
        $headers = $rows->first(); // 提取标题行
        $generatedUsernames = []; // 存储本次导入新生成的用户名

        foreach ($rows->skip(1) as $row) {
            $username = $this->sanitizeValue($this->getColumnValue($headers, $row, '用户名'));

            // 如果用户名为空，跳过该行
            if (empty($username)) {
                continue;
            }

            // 确保用户名唯一
            $username = $this->makeUniqueUsername($username, $generatedUsernames);

            // 处理其他字段并加密密码
            $password = $this->sanitizeValue($this->getColumnValue($headers, $row, '密码'));
            $encryptedPassword = Crypt::encrypt($password);

            $role = $this->sanitizeValue($this->getColumnValue($headers, $row, '角色'));
            $name = $this->sanitizeValue($this->getColumnValue($headers, $row, '姓名'));
            $department = $this->sanitizeValue($this->getColumnValue($headers, $row, '系别'));

            // 插入数据
            Users::create([
                'username' => $username,
                'password' => $encryptedPassword,
                'role' => $role,
                'name' => $name,
                'department' => $department,
            ]);

            // 更新缓存
            $this->existingUsernames[] = $username;
            $generatedUsernames[] = $username;
        }
    }

    private function getColumnValue($headers, $row, $columnName)
    {
        $index = array_search($columnName, $headers->toArray());
        return $index !== false ? $row[$index] : null;
    }

    private function makeUniqueUsername(string $username, array &$generatedUsernames): string
    {
        $originalUsername = $username;
        $suffix = 1;

        // 确保用户名在数据库和本次导入中都唯一
        while (in_array($username, $this->existingUsernames) || in_array($username, $generatedUsernames)) {
            $username = $originalUsername . '_' . $suffix;
            $suffix++;
        }

        return $username;
    }

    private function sanitizeValue($value): string
    {
        return is_null($value) ? '' : trim($value);
    }
}
