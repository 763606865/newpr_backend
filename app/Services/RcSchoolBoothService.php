<?php

namespace App\Services;

use App\Models\Rc\SchoolBooth;
use App\Models\Rc\SchoolBoothArea;
use App\Models\School;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class RcSchoolBoothService extends Service
{
    /**
     * @return LengthAwarePaginator<int, SchoolBooth>
     */
    public function paginateForSchool(School $school, int $perPage, array $filters = []): LengthAwarePaginator
    {
        $query = SchoolBooth::query()
            ->withCount('areas')
            ->when(
                filled($school->school_code),
                fn ($builder) => $builder->where('school_code', $school->school_code),
                fn ($builder) => $builder->whereRaw('1 = 0'),
            )
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        if (filled($filters['status'] ?? null)) {
            $query->where('status', (int) $filters['status']);
        }

        if (filled($filters['keyword'] ?? null)) {
            $keyword = (string) $filters['keyword'];
            $query->where('name', 'like', '%'.$keyword.'%');
        }

        return $query->paginate($perPage);
    }

    public function findForSchool(School $school, int $boothId): ?SchoolBooth
    {
        return SchoolBooth::query()
            ->with(['areas' => fn ($query) => $query->ordered()])
            ->when(
                filled($school->school_code),
                fn ($builder) => $builder->where('school_code', $school->school_code),
                fn ($builder) => $builder->whereRaw('1 = 0'),
            )
            ->find($boothId);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForSchool(School $school, array $data): SchoolBooth
    {
        return SchoolBooth::query()->create([
            ...Arr::only($data, [
                'province_code',
                'city_code',
                'district_code',
                'address',
                'name',
                'image',
                'area_size',
                'max_people',
                'description',
                'rule',
                'status',
                'extra',
            ]),
            'school_code' => $school->school_code,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SchoolBooth $booth, array $data): SchoolBooth
    {
        $booth->fill(Arr::only($data, [
            'province_code',
            'city_code',
            'district_code',
            'address',
            'name',
            'image',
            'area_size',
            'max_people',
            'description',
            'rule',
            'status',
            'extra',
        ]))->save();

        return $booth->refresh();
    }

    public function delete(SchoolBooth $booth): void
    {
        $booth->delete();
    }

    public function findAreaForBooth(SchoolBooth $booth, int $areaId): ?SchoolBoothArea
    {
        return SchoolBoothArea::query()
            ->where('booth_id', $booth->id)
            ->find($areaId);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createArea(SchoolBooth $booth, array $data): SchoolBoothArea
    {
        return DB::transaction(function () use ($booth, $data): SchoolBoothArea {
            $area = SchoolBoothArea::query()->create([
                'booth_id' => $booth->id,
                ...Arr::only($data, [
                    'code',
                    'name',
                    'area_size',
                    'max_people',
                    'map_image',
                    'start_no',
                    'end_no',
                    'max_company_count',
                    'extra',
                    'sort',
                ]),
                'total_booth_count' => $this->calculateBoothCount(
                    (int) $data['start_no'],
                    (int) $data['end_no'],
                ),
            ]);

            $this->syncBoothTotalCount($booth);

            return $area->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateArea(SchoolBoothArea $area, array $data): SchoolBoothArea
    {
        return DB::transaction(function () use ($area, $data): SchoolBoothArea {
            $area->fill(Arr::only($data, [
                'code',
                'name',
                'area_size',
                'max_people',
                'map_image',
                'start_no',
                'end_no',
                'max_company_count',
                'extra',
                'sort',
            ]));

            if (array_key_exists('start_no', $data) || array_key_exists('end_no', $data)) {
                $area->total_booth_count = $this->calculateBoothCount(
                    (int) $area->start_no,
                    (int) $area->end_no,
                );
            }

            $area->save();
            $this->syncBoothTotalCount($area->booth);

            return $area->refresh();
        });
    }

    public function deleteArea(SchoolBoothArea $area): void
    {
        DB::transaction(function () use ($area): void {
            $booth = $area->booth;
            $area->delete();
            $this->syncBoothTotalCount($booth);
        });
    }

    public function calculateBoothCount(int $startNo, int $endNo): int
    {
        return max(0, $endNo - $startNo + 1);
    }

    public function syncBoothTotalCount(SchoolBooth $booth): void
    {
        $total = (int) SchoolBoothArea::query()
            ->where('booth_id', $booth->id)
            ->sum('total_booth_count');

        $booth->update(['total_booth_count' => $total]);
    }
}
