<?php

namespace App\Filament\Resources\Im\SystemUsers\Tables;

use App\Models\ImSystemUser;
use App\Models\Rc\UserIm;
use App\Services\IMService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Throwable;

class SystemUsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('code')->label('编码')->searchable()->copyable(),
                TextColumn::make('name')->label('名称')->searchable(),
                TextColumn::make('provider')->label('服务商')->badge()->searchable(),
                TextColumn::make('app_code')->label('应用')->placeholder('-')->searchable(),
                TextColumn::make('external_user_id')->label('外部用户ID')->searchable()->copyable(),
                TextColumn::make('im_user_id')->label('IM用户ID')->placeholder('-')->searchable()->copyable(),
                IconColumn::make('is_active')->label('启用')->boolean(),
                TextColumn::make('updated_at')->label('更新时间')->dateTime()->sortable(),
                TextColumn::make('created_at')->label('创建时间')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('provider')
                    ->label('IM 服务商')
                    ->options(fn (): array => self::providerOptions()),
                SelectFilter::make('is_active')
                    ->label('启用状态')
                    ->options([
                        '1' => '启用',
                        '0' => '停用',
                    ]),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                self::sendSystemNoticeAction(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function providerOptions(): array
    {
        return [
            'custom' => 'custom',
            'tencent' => 'tencent',
            'rongcloud' => 'rongcloud',
            'easemob' => 'easemob',
        ];
    }

    private static function sendSystemNoticeAction(): Action
    {
        return Action::make('sendSystemNotice')
            ->label('发送系统通知')
            ->icon('heroicon-o-paper-airplane')
            ->modalHeading('发送系统通知')
            ->modalSubmitActionLabel('发送')
            ->visible(fn (ImSystemUser $record): bool => $record->is_active)
            ->form([
                Select::make('user_im_id')
                    ->label('接收用户')
                    ->required()
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => self::searchUserImOptions($search))
                    ->getOptionLabelUsing(fn (mixed $value): ?string => self::userImOptionLabel((int) $value)),
                TextInput::make('notice_type')
                    ->label('通知类型')
                    ->required()
                    ->maxLength(64)
                    ->placeholder('例如 approval_created'),
                TextInput::make('title')
                    ->label('标题')
                    ->required()
                    ->maxLength(100),
                Textarea::make('summary')
                    ->label('摘要')
                    ->required()
                    ->rows(3)
                    ->maxLength(500),
                TextInput::make('biz_id')
                    ->label('业务ID')
                    ->maxLength(128)
                    ->placeholder('例如 approval_12345'),
                TextInput::make('action_url')
                    ->label('跳转地址')
                    ->maxLength(255)
                    ->placeholder('例如 /oa/approvals/12345'),
                TextInput::make('client_msg_id')
                    ->label('客户端消息ID')
                    ->maxLength(128)
                    ->helperText('用于 IM 服务端幂等；不填则由 IM 服务端自行处理。'),
            ])
            ->action(function (ImSystemUser $record, array $data): void {
                $userIm = UserIm::query()->find((int) $data['user_im_id']);

                if (! $userIm instanceof UserIm || blank($userIm->external_user_id)) {
                    Notification::make()
                        ->title('接收用户 IM 不存在')
                        ->danger()
                        ->send();

                    return;
                }

                try {
                    IMService::make()->sendSystemNotice($userIm->external_user_id, [
                        'notice_type' => $data['notice_type'],
                        'title' => $data['title'],
                        'summary' => $data['summary'],
                        'biz_id' => $data['biz_id'] ?? null,
                        'action_url' => $data['action_url'] ?? null,
                        'client_msg_id' => $data['client_msg_id'] ?? null,
                    ], $record);
                } catch (Throwable $throwable) {
                    Notification::make()
                        ->title('发送失败')
                        ->body($throwable->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('系统通知已发送')
                    ->success()
                    ->send();
            });
    }

    /**
     * @return array<int, string>
     */
    private static function searchUserImOptions(string $search): array
    {
        return UserIm::query()
            ->with(['user', 'userIdentity'])
            ->whereNotNull('external_user_id')
            ->where(function ($query) use ($search): void {
                $query
                    ->where('external_user_id', 'like', "%{$search}%")
                    ->orWhere('im_user_id', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('nickname', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%"))
                    ->orWhereHas('userIdentity', fn ($identityQuery) => $identityQuery->where('identity_name', 'like', "%{$search}%"));
            })
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (UserIm $userIm): array => [$userIm->id => self::formatUserImOptionLabel($userIm)])
            ->all();
    }

    private static function userImOptionLabel(int $userImId): ?string
    {
        $userIm = UserIm::query()
            ->with(['user', 'userIdentity'])
            ->find($userImId);

        return $userIm instanceof UserIm ? self::formatUserImOptionLabel($userIm) : null;
    }

    private static function formatUserImOptionLabel(UserIm $userIm): string
    {
        $name = $userIm->user?->nickname
            ?: $userIm->user?->name
            ?: $userIm->userIdentity?->identity_name
            ?: '用户#'.$userIm->user_id;
        $identityLabel = $userIm->identity_type?->getLabel() ?? '未知身份';

        return sprintf('%s / %s / %s', $name, $identityLabel, $userIm->external_user_id);
    }
}
