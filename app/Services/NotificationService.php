<?php

namespace App\Services;

use App\Models\Exhibition;
use App\Models\Notification;
use App\Models\PortalLink;
use App\Models\StaffMember;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class NotificationService
{
    public function forExhibition(
        Exhibition $exhibition,
        string $title,
        string $body,
        string $type,
        ?string $permissionKey = null,
        array $data = [],
        ?string $actionUrl = null,
        array $alternativePermissionKeys = [],
    ): void {
        $base = [
            'id' => (string) Str::uuid(),
            'exhibition_id' => $exhibition->id,
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'permission_key' => $permissionKey,
            'data' => $data,
            'action_url' => $actionUrl,
            'read' => false,
        ];

        $organizerUserId = $exhibition->organizer?->user_id;
        $recipientUserIds = [];
        if ($organizerUserId) {
            Notification::create(array_merge($base, ['user_id' => $organizerUserId]));
            $recipientUserIds[] = $organizerUserId;
        }

        $links = PortalLink::with('staff')
            ->where('exhibition_id', $exhibition->id)
            ->where('active', true)
            ->get();

        foreach ($links as $link) {
            $permissions = $link->permissions ?? [];
            $requiredPermissions = array_filter([$permissionKey, ...$alternativePermissionKeys]);
            if ($permissionKey && !array_intersect($requiredPermissions, $permissions)) {
                continue;
            }

            $userId = $link->staff?->user_id;
            if (!$userId) {
                continue;
            }

            Notification::create(array_merge($base, [
                'id' => (string) Str::uuid(),
                'user_id' => $userId,
                'portal_link_id' => $link->id,
            ]));
            $recipientUserIds[] = $userId;
        }

        $staffTeams = collect([$permissionKey, ...$alternativePermissionKeys])
            ->filter()
            ->map(fn (string $key) => str($key)->before('.')->toString())
            ->map(fn (string $prefix) => match ($prefix) {
                'admin' => 'administrative',
                'org' => 'organizational',
                'serv' => 'services',
                'ext' => 'external',
                default => null,
            })
            ->filter()
            ->unique()
            ->values();

        if ($staffTeams->isNotEmpty()) {
            $linkedStaffIds = $links->pluck('staff_id')->filter()->all();
            $staffUsers = StaffMember::query()
                ->where('exhibition_id', $exhibition->id)
                ->whereIn('team', $staffTeams->all())
                ->whereNotNull('user_id')
                ->when($linkedStaffIds, fn ($query) => $query->whereNotIn('id', $linkedStaffIds))
                ->get(['id', 'user_id']);

            foreach ($staffUsers as $staff) {
                if (in_array($staff->user_id, $recipientUserIds, true)) {
                    continue;
                }

                Notification::create(array_merge($base, [
                    'id' => (string) Str::uuid(),
                    'user_id' => $staff->user_id,
                ]));
                $recipientUserIds[] = $staff->user_id;
            }
        }

        $this->sendPush($recipientUserIds, $title, $body, $type, $data);
    }

    private function sendPush(array $userIds, string $title, string $body, string $type, array $data): void
    {
        $tokens = \App\Models\FcmToken::whereIn('user_id', array_unique($userIds))
            ->pluck('fcm_token')
            ->filter()
            ->values()
            ->all();

        if (!$tokens) {
            return;
        }

        try {
            $message = CloudMessage::new()
                ->withNotification(FirebaseNotification::create($title, $body))
                ->withData(array_merge(['type' => $type], array_map('strval', $data)));
            app('firebase.messaging')->sendMulticast($message, $tokens);
        } catch (\Throwable $exception) {
            Log::warning('Notification push delivery failed', [
                'user_ids' => array_unique($userIds),
                'exception' => $exception,
            ]);
        }
    }
}
