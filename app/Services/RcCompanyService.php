<?php

namespace App\Services;

use App\Enums\CompanyContactType;
use App\Enums\CompanyLicenseType;
use App\Enums\CompanyStatus;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Models\Company;
use App\Models\CompanyContact;
use App\Models\CompanyLicense;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class RcCompanyService extends Service
{
    public function normalizeCreditCode(string $creditCode): string
    {
        return strtoupper(trim($creditCode));
    }

    public function findByCreditCode(string $creditCode): ?Company
    {
        $normalized = $this->normalizeCreditCode($creditCode);

        return Company::query()
            ->whereRaw('UPPER(credit_code) = ?', [$normalized])
            ->first();
    }

    public function findById(int $companyId): ?Company
    {
        return Company::query()->find($companyId);
    }

    /**
     * @return Collection<int, Company>
     */
    public function searchByName(string $name, int $limit = 20): Collection
    {
        $keyword = trim($name);

        return Company::query()
            ->where('name', 'like', '%'.$keyword.'%')
            ->orderBy('name')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  array{name: string, credit_code: string, contact_phone: string}  $companyData
     */
    public function findOrCreateFromInvite(array $companyData): Company
    {
        $existing = $this->findByCreditCode($companyData['credit_code']);

        if ($existing instanceof Company) {
            return $existing;
        }

        return DB::transaction(function () use ($companyData): Company {
            $company = CompanyService::make()->create([
                'name' => $companyData['name'],
                'credit_code' => $this->normalizeCreditCode($companyData['credit_code']),
                'contact_phone' => $companyData['contact_phone'],
            ]);

            CompanyProfileService::make()->ensureForCompany($company);

            return $company->refresh();
        });
    }

    /**
     * @return array{exists: bool, company: ?Company}
     */
    public function lookup(string $creditCode): array
    {
        $company = $this->findByCreditCode($creditCode);

        if ($company instanceof Company) {
            $company->load([
                'profile',
                'licenses' => fn ($query) => $query->orderBy('sort')->orderBy('id'),
                'contacts' => fn ($query) => $query->orderBy('sort')->orderBy('id'),
            ]);
        }

        return [
            'exists' => $company instanceof Company,
            'company' => $company,
        ];
    }

    public function resolveRecruiterIdentity(User $user): ?UserIdentity
    {
        $responsible = $user->token()?->responsible;

        if ($responsible instanceof UserIdentity && $responsible->identity_type === RcIdentityType::Recruiter) {
            return $responsible;
        }

        return $user->identities()
            ->where('identity_type', RcIdentityType::Recruiter)
            ->first();
    }

    public function userAlreadyBoundCompanyMessage(User $user, Company $company): ?string
    {
        $alreadyBound = $user->identities()
            ->where('identity_type', RcIdentityType::Recruiter)
            ->where('organization_type', 'company')
            ->where('organization_id', $company->id)
            ->exists();

        return $alreadyBound ? '您已绑定该企业。' : null;
    }

    /**
     * 为绑定/注册企业准备可用的招聘方身份行：未绑定则复用当前行，已绑定则新建一行。
     */
    public function prepareRecruiterIdentityForCompanyBind(User $user): UserIdentity
    {
        $identity = $this->resolveRecruiterIdentity($user);

        if (! $identity instanceof UserIdentity) {
            throw new \InvalidArgumentException('Recruiter identity is required.');
        }

        if (! $identity->organization_id) {
            return $identity;
        }

        return $user->identities()->create([
            'identity_type' => RcIdentityType::Recruiter,
            'identity_name' => $identity->identity_name ?? RcIdentityType::Recruiter->getLabel() ?? '招聘方',
            'is_default' => 0,
            'status' => RcIdentityStatus::Enabled,
        ]);
    }

    public function bind(UserIdentity $identity, Company $company, string $jobTitle): UserIdentity
    {
        return DB::transaction(function () use ($identity, $company, $jobTitle): UserIdentity {
            CompanyProfileService::make()->ensureForCompany($company);

            $identity->organization()->associate($company);
            $identity->fill([
                'organization_name' => $company->name,
                'job_title' => $jobTitle,
            ])->save();

            return $identity->refresh();
        });
    }

    /**
     * @param  array{name: string, credit_code: string, legal_person: string, contact_phone: string, address?: string|null}  $companyData
     * @return array{company: Company, identity: UserIdentity}
     */
    public function registerAndBind(
        UserIdentity $identity,
        array $companyData,
        string $jobTitle,
        ?string $licensesFilePath = null,
    ): array {
        return DB::transaction(function () use ($identity, $companyData, $jobTitle, $licensesFilePath): array {
            $company = CompanyService::make()->create([
                'name' => $companyData['name'],
                'credit_code' => $this->normalizeCreditCode($companyData['credit_code']),
                'legal_person' => $companyData['legal_person'],
                'contact_phone' => $companyData['contact_phone'],
                'address' => $companyData['address'] ?? null,
            ]);

            $this->seedCompanyProfile($company, $companyData, $licensesFilePath);

            $identity = $this->bind($identity, $company, $jobTitle);

            return [
                'company' => $company->refresh()->load([
                    'profile',
                    'licenses' => fn ($query) => $query->orderBy('sort')->orderBy('id'),
                    'contacts' => fn ($query) => $query->orderBy('sort')->orderBy('id'),
                ]),
                'identity' => $identity,
            ];
        });
    }

    public function companyBindableMessage(Company $company): ?string
    {
        if ($company->status === CompanyStatus::Disabled) {
            return '该企业已被禁用，无法绑定。';
        }

        return null;
    }

    /**
     * 根据注册表单自动写入企业证件与联系人（前端仅传主体字段与营业执照路径）。
     *
     * @param  array{name: string, credit_code: string, legal_person: string, contact_phone: string, address?: string|null}  $companyData
     */
    public function seedCompanyProfile(Company $company, array $companyData, ?string $licensesFilePath = null): void
    {
        $this->seedBusinessLicense($company, $companyData, $licensesFilePath);
        $this->seedDefaultContacts($company, $companyData);
    }

    /**
     * @param  array{credit_code: string}  $companyData
     */
    private function seedBusinessLicense(Company $company, array $companyData, ?string $licensesFilePath): void
    {
        $file = $this->parseUploadPath($licensesFilePath);

        CompanyLicense::query()->create([
            'company_id' => $company->id,
            'license_type' => CompanyLicenseType::BusinessLicense,
            'name' => CompanyLicenseType::BusinessLicense->getLabel() ?? '营业执照',
            'license_no' => $this->normalizeCreditCode((string) $companyData['credit_code']),
            'issuer' => null,
            'issue_date' => null,
            'expire_date' => null,
            'file_url' => $file['file_url'],
            'file_name' => $file['file_name'],
            'file_ext' => $file['file_ext'],
            'is_primary' => 1,
            'sort' => 0,
            'remark' => null,
        ]);
    }

    /**
     * @param  array{legal_person: string, contact_phone: string, address?: string|null}  $companyData
     */
    private function seedDefaultContacts(Company $company, array $companyData): void
    {
        $legalPerson = trim((string) $companyData['legal_person']);
        $contactPhone = trim((string) $companyData['contact_phone']);
        $address = filled($companyData['address'] ?? null) ? (string) $companyData['address'] : null;

        CompanyContact::query()->create([
            'company_id' => $company->id,
            'contact_type' => CompanyContactType::LegalPerson,
            'name' => $legalPerson !== '' ? $legalPerson : '法定代表人',
            'id_card' => null,
            'phone' => $contactPhone !== '' ? $contactPhone : null,
            'email' => null,
            'position' => '法定代表人',
            'share_ratio' => null,
            'address' => $address,
            'is_primary' => 1,
            'sort' => 0,
            'remark' => null,
        ]);

        CompanyContact::query()->create([
            'company_id' => $company->id,
            'contact_type' => CompanyContactType::Contact,
            'name' => $legalPerson !== '' ? $legalPerson : '企业联系人',
            'id_card' => null,
            'phone' => $contactPhone !== '' ? $contactPhone : null,
            'email' => null,
            'position' => '企业联系人',
            'share_ratio' => null,
            'address' => $address,
            'is_primary' => 0,
            'sort' => 1,
            'remark' => null,
        ]);
    }

    /**
     * @return array{file_url: ?string, file_name: ?string, file_ext: ?string}
     */
    private function parseUploadPath(?string $path): array
    {
        if (blank($path)) {
            return [
                'file_url' => null,
                'file_name' => null,
                'file_ext' => null,
            ];
        }

        $normalized = ltrim(trim($path), '/');
        $extension = pathinfo($normalized, PATHINFO_EXTENSION);

        return [
            'file_url' => $normalized,
            'file_name' => basename($normalized),
            'file_ext' => $extension !== '' ? $extension : null,
        ];
    }
}
