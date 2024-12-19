<?php

namespace App\Imports;

use App\Models\courses; // 确保模型名称与实际一致
use App\Models\only_courses; // 导入 only_courses 模型
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class importcourses implements ToCollection
{
    /**
     * 将 Excel 的每一行转化为 Collection。
     *
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        // 提取标题行
        $headers = $rows->first();

        foreach ($rows->skip(1) as $row) {
            $name = $this->getColumnValue($headers, $row, '课程名称');

            // 如果课程名称为空，跳过该行
            if (empty($name)) {
                continue;
            }

            // 存储课程到 courses 表
            $course = courses::create([
                'name' => $name,
                'code' => $this->getColumnValue($headers, $row, '课程代码'),
                'category' => $this->getColumnValue($headers, $row, '课程类别'),
                'nature' => $this->getColumnValue($headers, $row, '课程性质'),
                'credit' => $this->getColumnValue($headers, $row, '学分'),
                'hours' => $this->getColumnValue($headers, $row, '总学时'),
                'grade' => $this->getColumnValue($headers, $row, '年级'),
                'class_name' => $this->getColumnValue($headers, $row, '班级'),
                'class_size' => $this->getColumnValue($headers, $row, '班级人数'),
                'department' => $this->getColumnValue($headers, $row, '专业'),
                'semester' => $this->getColumnValue($headers, $row, '学期'),
            ]);

            // 存储到 only_courses 表
            $category = $this->getColumnValue($headers, $row, '课程类别');
            $nature = $this->getColumnValue($headers, $row, '课程性质');
            $credit = $this->getColumnValue($headers, $row, '学分');
            $hours = $this->getColumnValue($headers, $row, '总学时');
            $semester = $this->getColumnValue($headers, $row, '学期');
            $className = $this->getColumnValue($headers, $row, '班级');

            // 检查是否已经存在该课程（按课程名称去重）
            $existingCourse = only_courses::where('name', $name)->first();

            if ($existingCourse) {
                // 如果已存在该课程，我们需要更新班级数量
                // 获取所有该课程名称的班级数量
                $classCount = courses::where('name', $name)->count();

                // 更新 only_courses 表中的 number_classes
                $existingCourse->update(['number_classes' => $classCount]);
            } else {
                // 如果不存在该课程，插入新的课程
                $classCount = courses::where('name', $name)->count(); // 获取该课程的班级数量

                only_courses::create([
                    'name' => $name,
                    'category' => $category,
                    'nature' => $nature,
                    'credit' => $credit,
                    'hours' => $hours,
                    'semester' => $semester,
                    'number_classes' => $classCount, // 初始化为该课程的班级数量
                ]);
            }
        }
    }

    /**
     * 根据列名查找对应值。
     *
     * @param Collection $headers
     * @param Collection $row
     * @param string $columnName
     * @return mixed
     */
    private function getColumnValue($headers, $row, $columnName)
    {
        $index = array_search($columnName, $headers->toArray()); // 查找标题的索引
        return $index !== false ? $row[$index] : null; // 返回对应值，若未找到则返回 null
    }
}
