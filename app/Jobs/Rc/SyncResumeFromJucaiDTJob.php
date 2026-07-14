<?php

namespace App\Jobs\Rc;

use App\Enums\RcEmploymentType;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Enums\RcJobEmploymentType;
use App\Enums\RcResumeJobStatus;
use App\Enums\RcResumeSourceType;
use App\Enums\UserGender;
use App\Exceptions\BadRequestException;
use App\Libs\Facades\JucaiDT;
use App\Models\Rc\Industry;
use App\Models\Rc\Position;
use App\Models\Rc\Resume;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use App\Services\MetaService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SyncResumeFromJucaiDTJob implements ShouldQueue
{
    use Queueable;

    protected array $areaMaps;

    /**
     * Create a new job instance.
     */
    public function __construct(protected array $data = [])
    {
        $this->areaMaps = array_flip(MetaService::make()->getAreaNameMap());
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            foreach ($this->data as $item) {
                if (Resume::where(['ext_source' => 'JucaiDT', 'resume_id' => $item['resume_id'] ?? 0])->exists()) {
                    continue;
                }
                $params = [
                    'resume_id' => $item['resume_id'] ?? 0
                ];
                $response = JucaiDT::resume()->detail($params);
                if (isset($response['code'], $response['data']) && (int)$response['code'] === 1 && $response['data']) {
                    $data = $response['data'];
                    // 注册用户-绑定求职者身份-创建简历
                    $user = $this->resolvedUser($data['base']);
                    $this->resolvedJobseekerIdentity($user);
                    $resume = $this->resolvedResume($user, $data['base']);

                    $this->syncEducations($resume, $data['edu_list']);
                    $this->syncWorks($resume, $data['work_list']);
                    $this->syncCertificates($resume, $data['cert_list']);
                    $this->syncLanguages($resume, $data['lang_list']);
                    $this->syncProjects($resume, $data['project_list']);
                    $this->syncTrainings($resume, $data['training_list']);
                    $this->syncIntentions($resume, $data);
                }
                usleep(100000); // Sleep for 100ms between requests
            }
        } catch (\Exception $e) {
            Log::error('Error occurred while syncing resume from JucaiDT: ' . $e->getMessage());
        }
    }

    public function resolvedUser(array $data): User
    {
        try {
            $gender = match ((int)$data['gender']) {
                1 => UserGender::Male,
                2 => UserGender::Female,
                default => UserGender::Unknown
            };
            $phone = $data['phone'] ?? '';
            $phone = empty($phone) ? ($data['mobile'] ?? '') : $phone;
            if (blank($phone)) {
                throw new BadRequestException("Failed to resolve user: phone is required.");
            }
            /** @var User $user */
            $user = User::firstOrCreate([
                'phone' => $phone,
            ], [
                'name' => $data['real_name'] ?? '',
                'nickname' => $data['real_name'] ?? '',
                'email' => $data['email'] ?? '',
                'gender' => $gender,
                'extra' => [
                    'jucai_dt' => [
                        'resume_id' => $data['resume_id'] ?? ''
                    ],
                ]
            ]);
            return $user;
        } catch (\Exception $exception) {
            throw new BadRequestException(sprintf("Failed to resolve user resume id: %s, Message: %s", $data['resume_id'] ?? '', $exception->getMessage()));
        }
    }

    public function resolvedJobseekerIdentity(User $user): UserIdentity
    {
        try {
            $isDefault = $user->identities()->count() === 0;
            /** @var UserIdentity $userIdentity */
            $userIdentity = $user->identities()->firstOrCreate([
                'identity_type' => RcIdentityType::JobSeeker,
            ], [
                'identity_name' => RcIdentityType::JobSeeker->getLabel(),
                'identity_status' => RcIdentityStatus::Enabled,
                'is_default' => $isDefault
            ]);
            return $userIdentity;
        } catch (\Exception $exception) {
            throw new BadRequestException(sprintf("Failed to resolve jobseeker identity for user id: %s, Message: %s", $user->id, $exception->getMessage()));
        }
    }

    public function resolvedResume(User $user, array $data): Resume
    {
        try {
            $service = MetaService::make();
            $nation = $service->JucaiDTDictReflect('nation', (int)($data['nationality'] ?? 0));

            // 解析出生日期，优先使用完整日期，其次使用出生年份（若仅有年份，则取当年开始日）
            $birth_date = null;
            if (!empty($data['birthday'])) {
                $birth_date = Carbon::parse($data['birthday']);
            } elseif (!empty($data['birthdayyear'])) {
                $year = (string)$data['birthdayyear'];
                if (preg_match('/^\d{4}$/', $year)) {
                    // 只有年份，设为该年年初（可根据需求调整为年中或年末）
                    $birth_date = Carbon::createFromFormat('Y', $year)->startOfYear();
                } else {
                    // 尝试解析其它可解析格式
                    $birth_date = Carbon::parse($year);
                }
            }

            $marital_status = $service->JucaiDTDictReflect('marital_status', (int)($data['marital_status'] ?? 0));
            $political_status = $service->JucaiDTDictReflect('politics', (int)($data['political_status'] ?? 0));
            $work_start_date = $data['startworktime'] ? Carbon::parse($data['startworktime']) : null;
            $work_years = $service->JucaiDTDictReflect('experience', (int)($data['work_years'] ?? 0));
            $highest_education_level = $service->JucaiDTDictReflect('education', (int)($data['highest_education'] ?? 0));
            [$expected_salary_min, $expected_salary_max] = $this->normalizeDesiredSalary((int)($data['desired_salary'] ?? 0));
            /** @var Resume $resume */
            $resume = Resume::firstOrCreate([
                'user_id' => $user->id,
                'ext_source' => 'JucaiDT',
                'ext_id' => $data['resume_id'] ?? '',
            ], [
                'full_name' => $data['real_name'] ?? '',
                'gender' => $user->gender,
                'id_card' => $data['id_card'] ?? '',
                'nation' => $nation ?? '',
                'birth_date' => $birth_date,
                'age' => $birth_date?->age,
                'marital_status' => $marital_status,
                'political_status' => $political_status,
                'native_place' => $this->matchAreaNameFromJucaiDistrict($data['placeid'] ?? 0),
                'work_start_date' => $work_start_date,
                'work_years' => $work_years,
                'recruit_source' => 'JucaiDT',
                'highest_education_level' => $highest_education_level,
                'expected_salary_min' => $expected_salary_min,
                'expected_salary_max' => $expected_salary_max,
                'current_residence_detail' => $data['address'] ?? '',
                'phone' => $data['phone'] ?? '',
                'email' => $data['email'] ?? '',
                'source_type' => RcResumeSourceType::External,
                'parsed_data' => $data,
            ]);
            return $resume;
        } catch (\Exception $exception) {
            throw new BadRequestException(sprintf("Failed to resolve resume for user id: %s, Message: %s", $user->id, $exception->getMessage()));
        }
    }

    public function syncEducations(Resume $resume, array $data): void
    {
        try {
            $service = MetaService::make();
            foreach ($data as $edu) {
                $is_current = false;
                if (isset($edu['timestart'], $edu['timeend'])) {
                    $is_current = !empty($edu['timestart']) && empty($edu['timeend']);
                }
                $degree = $service->JucaiDTDictReflect('education', (int)($edu['education'] ?? 0));
                $resume->educations()->firstOrCreate([
                    'resume_id' => $resume->id,
                    'user_id' => $resume->user_id,
                    'degree' => $degree,
                    'school_name' => $edu['school_name'] ?? '',
                ], [
                    'major' => $edu['major'] ?? '',
                    'start_date' => Carbon::parse($edu['timestart'] ?? null),
                    'end_date' => Carbon::parse($edu['timeend'] ?? null),
                    'is_current' => $is_current,
                ]);
            }
        } catch (\Exception $exception) {
            throw new BadRequestException(sprintf("Failed to sync educations for resume id: %s, Message: %s", $resume->id, $exception->getMessage()));
        }
    }

    public function syncWorks(Resume $resume, array $data): void
    {
        try {
            foreach ($data as $work) {
                $is_current = false;
                if (isset($work['timestart'], $work['timeend'])) {
                    $is_current = !empty($work['timestart']) && empty($work['timeend']);
                }
                $resume->works()->firstOrCreate([
                    'resume_id' => $resume->id,
                    'user_id' => $resume->user_id,
                    'company_name' => $work['company_name'] ?? '',
                ], [
                    'position' => $work['position'] ?? '',
                    'start_date' => Carbon::parse($work['timestart'] ?? null),
                    'end_date' => Carbon::parse($work['timeend'] ?? null),
                    'is_current' => $is_current,
                    'description' => $work['jobdescription'] ?? '',
                ]);
            }
        } catch (\Exception $exception) {
            throw new BadRequestException(sprintf("Failed to sync works for resume id: %s, Message: %s", $resume->id, $exception->getMessage()));
        }
    }

    public function syncCertificates(Resume $resume, array $data): void
    {
        try {
            foreach ($data as $certificate) {
                $resume->certificates()->firstOrCreate([
                    'resume_id' => $resume->id,
                    'user_id' => $resume->user_id,
                    'name' => $certificate['name'] ?? '',
                ], [
                    'issuer' => $certificate['agency'] ?? '',
                    'issue_date' => Carbon::parse($certificate['gettime'] ?? null),
                    'expire_date' => Carbon::parse($certificate['timeend'] ?? null),
                ]);
            }
        } catch (\Exception $exception) {
            throw new BadRequestException(sprintf("Failed to sync certificates for resume id: %s, Message: %s", $resume->id, $exception->getMessage()));
        }
    }

    public function syncLanguages(Resume $resume, array $data): void
    {
        try {
            foreach ($data as $language) {
                $resume->languages()->firstOrCreate([
                    'resume_id' => $resume->id,
                    'user_id' => $resume->user_id,
                    'name' => $language['name'] ?? '',
                ], [
                    'proficiency' => $language['proficiency'] ?? '',
                ]);
            }
        } catch (\Exception $exception) {
            throw new BadRequestException(sprintf("Failed to sync languages for resume id: %s, Message: %s", $resume->id, $exception->getMessage()));
        }

    }

    public function syncProjects(Resume $resume, array $data): void
    {
        try {
            foreach ($data as $project) {
                $is_current = false;
                if (isset($project['timestart'], $project['timeend'])) {
                    $is_current = !empty($project['timestart']) && empty($project['timeend']);
                }
                $resume->projects()->firstOrCreate([
                    'resume_id' => $resume->id,
                    'user_id' => $resume->user_id,
                    'project_name' => $project['name'] ?? '',
                ], [
                    'role' => $project['role'] ?? '',
                    'start_date' => Carbon::parse($project['timestart'] ?? null),
                    'end_date' => Carbon::parse($project['timeend'] ?? null),
                    'is_current' => $is_current,
                    'description' => $project['description'] ?? '',
                ]);
            }
        } catch (\Exception $exception) {
            throw new BadRequestException(sprintf("Failed to sync projects for resume id: %s, Message: %s", $resume->id, $exception->getMessage()));
        }
    }

    public function syncTrainings(Resume $resume, array $data): void
    {
        try {
            foreach ($data as $training) {
                $resume->trainings()->firstOrCreate([
                    'resume_id' => $resume->id,
                    'user_id' => $resume->user_id,
                    'institution_name' => $training['organization'] ?? '',
                    'course_name' => $training['course'] ?? '',
                ], [
                    'start_date' => Carbon::parse($training['timestart'] ?? null),
                    'end_date' => Carbon::parse($training['timeend'] ?? null),
                    'description' => $training['description'] ?? '',
                ]);
            }
        } catch (\Exception $exception) {
            throw new BadRequestException(sprintf("Failed to sync trainings for resume id: %s, Message: %s", $resume->id, $exception->getMessage()));
        }
    }

    public function syncIntentions(Resume $resume, array $data): void
    {
        try {
            $service = MetaService::make();
            $base = $data['base'];
            $job_status = $service->JucaiDTDictReflect('current_status', (int)($base['current_status'] ?? 0));
            $nature = $service->JucaiDTDictReflect('nature', (int)($base['nature'] ?? 0));
            $employment_type = match ($nature) {
                RcJobEmploymentType::PartTime => RcEmploymentType::PartTime,
                RcJobEmploymentType::Internship => RcEmploymentType::Internship,
                RcJobEmploymentType::FullTime => RcEmploymentType::FullTime,
            };
            $expected_city_code = $this->matchAreaCodeFromJucaiDistrict($base['desiredregionid'] ?? 0);
            $expected_position_ids = $this->matchExpectedPositionId($data['position_list']);
            $expected_position_id = count($expected_position_ids) ? $expected_position_ids[0] : null;
            $expected_industry_codes = $this->matchExpectedIndustryCodes($data['industry_list']);
            [$expected_salary_min, $expected_salary_max] = $this->normalizeDesiredSalary((int)($base['desired_salary'] ?? 0));
            $resume->intentions()->updateOrCreate([
                'resume_id' => $resume->id,
                'user_id' => $resume->user_id,
            ], [
                'job_status' => $job_status ?? RcResumeJobStatus::ActivelyLooking,
                'employment_type' => $employment_type,
                'expected_city_code' => $expected_city_code ?? null,
                'expected_industry_codes' => $expected_industry_codes,
                'expected_position_id' => $expected_position_id,
                'salary_min' => $expected_salary_min,
                'salary_max' => $expected_salary_max,
                'available_date' => !empty($base['join_time']) ? Carbon::parse($base['join_time']) : null,
            ]);
        } catch (\Exception $exception) {
            throw new BadRequestException(sprintf("Failed to sync intentions for resume id: %s, Message: %s", $resume->id, $exception->getMessage()));
        }
    }

    private function normalizeDesiredSalary(int $desiredSalary): array
    {
        return match ($desiredSalary) {
            61 => [1800, 3000],
            306 => [3000, 4500],
            307 => [4500, 6000],
            318 => [6000, 8000],
            319 => [8000, 10000],
            320 => [10000, 15000],
            467 => [15000, 20000],
            468 => [20000, null],
            default => [null, null],
        };
    }

    private function matchExpectedPositionId(array $positionList): array
    {
        $positionIds = [];
        foreach ($positionList as $position) {
            if (blank($position['name'] ?? null)) {
                continue;
            }
            if ($hit = Position::search($position['name'])->first()) {
                $positionIds[] = $hit->id;
            }
        }
        return $positionIds;
    }

    private function matchExpectedIndustryCodes(array $industryList): array
    {
        $industryCodes = [];
        foreach ($industryList as $industry) {
            if (blank($industry['name'] ?? null)) {
                continue;
            }
            if ($hit = Industry::search($industry['name'])->first()) {
                $industryCodes[] = $hit->code;
            }
        }
        return $industryCodes;
    }

    private function matchAreaCodeFromJucaiDistrict(int $id): string
    {
        $areaName = $this->matchAreaNameFromJucaiDistrict($id);
        if (empty($areaName)) {
            return '';
        }
        return $this->areaMaps[$areaName] ?? '';
    }

    private function matchAreaNameFromJucaiDistrict(int $id)
    {
        if (empty($id)) {
            return '';
        }
        if(Schema::hasTable('ext_jucai_dt_ksdistrict')) {
            return DB::table('ext_jucai_dt_ksdistrict')->where('id', $id)->value('name') ?? '';
        }
        return '';
    }
}
