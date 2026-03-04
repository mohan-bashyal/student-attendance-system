<?php

namespace Database\Seeders;

use App\Models\ClassTeacherAssignment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Database\Seeder;

class AlphaSchoolAcademicSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::query()->where('code', 'ALPHA')->first();

        if (! $school) {
            return;
        }

        $classes = $this->seedClasses($school->id);
        $sections = $this->seedSections($school->id);
        $teachers = $this->seedTeachers($school->id);

        $this->seedClassTeacherAssignments($school->id, $classes, $sections, $teachers);
        $this->seedStudents($school->id, $classes, $sections);
    }

    private function seedClasses(int $schoolId): array
    {
        $classNames = ['Nursery'];
        for ($grade = 1; $grade <= 10; $grade++) {
            $classNames[] = "Grade {$grade}";
        }

        $classes = [];
        foreach ($classNames as $index => $name) {
            $classes[$name] = SchoolClass::query()->updateOrCreate(
                ['school_id' => $schoolId, 'name' => $name],
                ['display_order' => $index + 1]
            );
        }

        return $classes;
    }

    private function seedSections(int $schoolId): array
    {
        $sectionNames = ['A', 'B', 'C'];
        $sections = [];

        foreach ($sectionNames as $name) {
            $sections[$name] = Section::query()->updateOrCreate(
                ['school_id' => $schoolId, 'name' => $name],
                []
            );
        }

        return $sections;
    }

    private function seedTeachers(int $schoolId): array
    {
        $teacherList = [
            ['name' => 'Teacher One', 'email' => 'teacher@alpha.test', 'phone' => '9801000000'],
            ['name' => 'Anita Sharma', 'email' => 'anita.sharma@alpha.school', 'phone' => '9801000001'],
            ['name' => 'Bikash Koirala', 'email' => 'bikash.koirala@alpha.school', 'phone' => '9801000002'],
            ['name' => 'Chandani Thapa', 'email' => 'chandani.thapa@alpha.school', 'phone' => '9801000003'],
            ['name' => 'Deepak Adhikari', 'email' => 'deepak.adhikari@alpha.school', 'phone' => '9801000004'],
            ['name' => 'Elina Bhandari', 'email' => 'elina.bhandari@alpha.school', 'phone' => '9801000005'],
            ['name' => 'Prakash Shrestha', 'email' => 'prakash.shrestha@alpha.school', 'phone' => '9801000006'],
            ['name' => 'Sushma Gautam', 'email' => 'sushma.gautam@alpha.school', 'phone' => '9801000007'],
            ['name' => 'Ramesh Bhattarai', 'email' => 'ramesh.bhattarai@alpha.school', 'phone' => '9801000008'],
            ['name' => 'Nisha KC', 'email' => 'nisha.kc@alpha.school', 'phone' => '9801000009'],
            ['name' => 'Kamal Paudel', 'email' => 'kamal.paudel@alpha.school', 'phone' => '9801000010'],
            ['name' => 'Sarita Regmi', 'email' => 'sarita.regmi@alpha.school', 'phone' => '9801000011'],
            ['name' => 'Rabin Joshi', 'email' => 'rabin.joshi@alpha.school', 'phone' => '9801000012'],
        ];

        $teachers = [];
        foreach ($teacherList as $teacher) {
            $teachers[$teacher['name']] = Teacher::query()->updateOrCreate(
                ['email' => $teacher['email']],
                [
                    'school_id' => $schoolId,
                    'name' => $teacher['name'],
                    'phone' => $teacher['phone'],
                    'has_attendance_access' => true,
                ]
            );
        }

        return $teachers;
    }

    private function seedClassTeacherAssignments(int $schoolId, array $classes, array $sections, array $teachers): void
    {
        $map = [
            ['class' => 'Nursery', 'section' => 'A', 'teacher' => 'Teacher One'],
            ['class' => 'Nursery', 'section' => 'B', 'teacher' => 'Anita Sharma'],
            ['class' => 'Grade 1', 'section' => 'A', 'teacher' => 'Teacher One'],
            ['class' => 'Grade 1', 'section' => 'B', 'teacher' => 'Bikash Koirala'],
            ['class' => 'Grade 1', 'section' => 'C', 'teacher' => 'Chandani Thapa'],
            ['class' => 'Grade 2', 'section' => 'A', 'teacher' => 'Deepak Adhikari'],
            ['class' => 'Grade 2', 'section' => 'B', 'teacher' => 'Elina Bhandari'],
            ['class' => 'Grade 2', 'section' => 'C', 'teacher' => 'Prakash Shrestha'],
            ['class' => 'Grade 3', 'section' => 'A', 'teacher' => 'Sushma Gautam'],
            ['class' => 'Grade 3', 'section' => 'B', 'teacher' => 'Ramesh Bhattarai'],
            ['class' => 'Grade 3', 'section' => 'C', 'teacher' => 'Nisha KC'],
            ['class' => 'Grade 4', 'section' => 'A', 'teacher' => 'Kamal Paudel'],
            ['class' => 'Grade 4', 'section' => 'B', 'teacher' => 'Sarita Regmi'],
            ['class' => 'Grade 4', 'section' => 'C', 'teacher' => 'Rabin Joshi'],
        ];

        foreach ($map as $item) {
            if (! isset($classes[$item['class']], $sections[$item['section']], $teachers[$item['teacher']])) {
                continue;
            }

            ClassTeacherAssignment::query()->updateOrCreate(
                [
                    'school_id' => $schoolId,
                    'school_class_id' => $classes[$item['class']]->id,
                    'section_id' => $sections[$item['section']]->id,
                ],
                [
                    'teacher_id' => $teachers[$item['teacher']]->id,
                ]
            );
        }
    }

    private function seedStudents(int $schoolId, array $classes, array $sections): void
    {
        $firstNames = [
            'Aarav', 'Aarya', 'Sanjana', 'Saurav', 'Prisha', 'Ritvik', 'Niruta', 'Ishan', 'Kabita', 'Rohan',
            'Samriddhi', 'Sajan', 'Asmita', 'Nabin', 'Anushka', 'Bibek', 'Kriti', 'Aman', 'Sambhav', 'Nisha',
        ];
        $lastNames = [
            'Sharma', 'Shrestha', 'Karki', 'Rai', 'Gurung', 'Adhikari', 'Pandey', 'Thapa', 'Bista', 'Poudel',
            'Acharya', 'Bhattarai', 'KC', 'Tamang', 'Maharjan',
        ];

        $classOrder = array_keys($classes);

        foreach ($classOrder as $classIndex => $className) {
            $class = $classes[$className];
            $activeSections = $className === 'Nursery' ? ['A', 'B'] : ['A', 'B', 'C'];

            foreach ($activeSections as $sectionName) {
                for ($roll = 1; $roll <= 4; $roll++) {
                    $nameIndex = ($classIndex * 5 + $roll) % count($firstNames);
                    $lastIndex = ($classIndex * 3 + $roll) % count($lastNames);
                    $fullName = $firstNames[$nameIndex].' '.$lastNames[$lastIndex];

                    $studentCode = sprintf('ALP-%02d%s-%02d', $classIndex + 1, $sectionName, $roll);
                    $email = strtolower(str_replace(' ', '.', $fullName)).'.'.($classIndex + 1).$sectionName.$roll.'@student.alpha.school';

                    Student::query()->updateOrCreate(
                        ['student_id' => $studentCode],
                        [
                            'school_id' => $schoolId,
                            'school_class_id' => $class->id,
                            'section_id' => $sections[$sectionName]->id,
                            'name' => $fullName,
                            'email' => $email,
                            'phone' => '98120'.str_pad((string) ($classIndex * 10 + $roll), 5, '0', STR_PAD_LEFT),
                            'gender' => $roll % 2 === 0 ? 'Female' : 'Male',
                            'date_of_birth' => now()->subYears(5 + $classIndex)->subDays($roll * 12)->toDateString(),
                        ]
                    );
                }
            }
        }
    }

}
