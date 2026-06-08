<?php

declare(strict_types=1);

namespace App\Policies\Oa;

use App\Models\Oa\AttendanceSchedule;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AttendanceSchedulePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AttendanceSchedule');
    }

    public function view(AuthUser $authUser, AttendanceSchedule $attendanceSchedule): bool
    {
        return $authUser->can('View:AttendanceSchedule');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AttendanceSchedule');
    }

    public function update(AuthUser $authUser, AttendanceSchedule $attendanceSchedule): bool
    {
        return $authUser->can('Update:AttendanceSchedule');
    }

    public function delete(AuthUser $authUser, AttendanceSchedule $attendanceSchedule): bool
    {
        return $authUser->can('Delete:AttendanceSchedule');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AttendanceSchedule');
    }

    public function restore(AuthUser $authUser, AttendanceSchedule $attendanceSchedule): bool
    {
        return $authUser->can('Restore:AttendanceSchedule');
    }

    public function forceDelete(AuthUser $authUser, AttendanceSchedule $attendanceSchedule): bool
    {
        return $authUser->can('ForceDelete:AttendanceSchedule');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AttendanceSchedule');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AttendanceSchedule');
    }

    public function replicate(AuthUser $authUser, AttendanceSchedule $attendanceSchedule): bool
    {
        return $authUser->can('Replicate:AttendanceSchedule');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AttendanceSchedule');
    }
}
