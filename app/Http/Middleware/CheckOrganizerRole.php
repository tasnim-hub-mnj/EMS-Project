<?php

namespace App\Http\Middleware;

use App\Models\Booth;
use App\Models\BoothBooking;
use App\Models\PortalLink;
use App\Models\SponserEventTicket;
use App\Models\SponsorEvent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckOrganizerRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if ($user?->role === 'organizer') {
            return $next($request);
        }

        if ($user?->role === 'staff' && $user->staff) {
            $exhibitionId = $this->resolveExhibitionId($request);

            $portalQuery = PortalLink::query()
                ->where('staff_id', $user->staff->id)
                ->where('active', true)
                ->when($exhibitionId, fn ($query) => $query->where('exhibition_id', $exhibitionId));

            $portalToken = $request->header('X-Portal-Token');
            if ($portalToken) {
                $portalQuery->where('token', $portalToken);
            }

            $portal = $portalQuery->first();
            $requiredPermission = $portal ? $this->requiredPermission($request, $portal->role) : null;

            if ($portal && (!$requiredPermission || in_array($requiredPermission, $portal->permissions ?? [], true))) {
                return $next($request);
            }
        }

        return response()->json([
            'message' => 'Unauthenticated, you are not authorized for this organizer resource'
        ], 403);
    }

    private function resolveExhibitionId(Request $request): ?string
    {
        $exhibitionId = $request->query('exhibition_id') ?? $request->route('exhibitionId');
        if ($exhibitionId) return (string) $exhibitionId;

        $path = $request->path();
        $resourceId = $request->route('id') ?? $request->route('eventId') ?? $request->route('ticketId');

        if (str_contains($path, '/organizer/exhibitions/')) return (string) $resourceId;
        if (str_contains($path, '/organizer/events/') && $resourceId) {
            return (string) SponsorEvent::whereKey($resourceId)->value('exhibition_id');
        }
        if (str_contains($path, '/organizer/booths/') && $resourceId) {
            return (string) Booth::whereKey($resourceId)->value('exhibition_id');
        }
        if (str_contains($path, '/organizer/bookings/') && $resourceId) {
            return (string) BoothBooking::whereKey($resourceId)->whereHas('booth')->with('booth')->first()?->booth?->exhibition_id;
        }
        if (str_contains($path, '/organizer/tickets/') && $resourceId) {
            return (string) SponserEventTicket::whereKey($resourceId)->with('sponsorEvent')->first()?->sponsorEvent?->exhibition_id;
        }

        return null;
    }

    private function requiredPermission(Request $request, string $role): ?string
    {
        $path = $request->path();

        if ($role === 'administrative' && $request->isMethod('GET') && (
            str_contains($path, '/organizer/booths')
            || str_contains($path, '/organizer/bookings')
            || str_contains($path, '/organizer/events')
            || str_contains($path, '/sponsors')
            || str_contains($path, '/sponsorship-requests')
        )) {
            return 'admin.reports';
        }

        if (str_contains($path, '/organizer/reports/')) return 'admin.reports';
        if (str_contains($path, '/organizer/exhibitions/') && (str_contains($path, '/map') || str_contains($path, '/sections'))) {
            return match ($role) {
                'administrative' => 'admin.map',
                'organizational' => 'org.map',
                default => '__denied__',
            };
        }
        if (str_contains($path, '/organizer/booths')) return 'org.booths';
        if (str_contains($path, '/organizer/bookings') || str_contains($path, '/bookings')) return 'org.bookings';
        if (str_contains($path, '/organizer/events') || str_contains($path, '/organizer/tickets')) return 'org.events';
        if (str_contains($path, '/sponsors') || str_contains($path, '/sponsorship-requests') || str_contains($path, '/event-sponsorship-requests')) return 'org.sponsors';
        if (str_contains($path, '/staff/tasks')) {
            return match ($role) {
                'administrative' => 'admin.staff',
                'organizational' => 'org.tasks',
                'services' => 'serv.tasks',
                'external' => 'ext.tasks',
                default => '__denied__',
            };
        }
        if (str_contains($path, '/staff/portal-staff')) {
            return match ($role) {
                'organizational' => 'org.tasks',
                default => '__denied__',
            };
        }
        if (str_contains($path, '/staff/attendance')) return 'admin.attendance';
        if (str_contains($path, '/staff/payroll')) return 'admin.payroll';
        if (str_contains($path, '/staff/external')) return 'admin.external';
        if (str_contains($path, '/staff/portal-links')) {
            return match ($role) {
                'administrative' => 'admin.links',
                'organizational' => 'org.links',
                default => '__denied__',
            };
        }
        if (preg_match('#(?:^|/)staff(?:/|$)#', $path)) return 'admin.staff';

        return null;
    }
}
