<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSponsorEventImageRequest;
use App\Http\Requests\StoreSponsorEventInvitationRequest;
use App\Http\Requests\StoreSponsorEventProgramRequest;
use App\Http\Requests\StoreSponsorEventRequest;
use App\Http\Requests\UpdateSponsorEventRequest;
use App\Models\Exhibition;
use App\Models\SponserEventTicket;
use App\Models\SponsorEvent;
use App\Models\SponsorEventImage;
use App\Models\SponsorEventInvitation;
use App\Models\SponsorEventProgram;
use App\Models\SponsorshipBooking;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SponsorEventController extends Controller
{
    public function store(StoreSponsorEventRequest $request)
    {
        $organizer = Auth::user()->organizer;
        $validate_data = $request->validated();

        $validate_data['exhibition_id'] = $organizer->exhibition->id;

        $exhibition_start = Carbon::parse($organizer->exhibition->start_date);
        $exhibition_end   = Carbon::parse($organizer->exhibition->end_date);

        $start = Carbon::parse($validate_data['start_time']);
        $end   = Carbon::parse($validate_data['end_time']);

        // مدة الفعالية لا تتجاوز مدة المعرض
        if ($start->lt($exhibition_start) || $end->gt($exhibition_end))
        {
            return response()->json([
                'message' => 'Event duration must be within exhibition dates',
                'exhibition_start' => $exhibition_start->toDateTimeString(),
                'exhibition_end' => $exhibition_end->toDateTimeString(),
            ], 422);
        }

        $validate_data['duration_days'] = $start->diffInDays($end) + 1;
        $validate_data['total_seats'] = $validate_data['max_participants'];

        // تحديد الحالة
        if ($start->isToday())
        {
            $validate_data['status'] = 'ongoing';
        }
        elseif ($start->isFuture())
        {
            $validate_data['status'] = 'upcoming';
        }
        else
        {
            $validate_data['status'] = 'finished';
        }

        $sponsor_event = SponsorEvent::create($validate_data);

        return response()->json([
            'message' => 'Sponsor Event created successfully',
            'sponsor_event' => $sponsor_event,
        ], 201);
    }
    //===============================================================
    public function storeImages(StoreSponsorEventImageRequest $request,$sponsor_event_id)
    {
        $sponsor_event = SponsorEvent::findOrFail($sponsor_event_id);

        $validated = $request->validated();
        $validated['sponsor_event_id'] = $sponsor_event_id;
        $images = $validated['images'];

        $images_s = [];
        foreach ($images as $image)
        {
            $path = $image->store('sponsor_event_images', 'public');

            $img = SponsorEventImage::create([
                'sponsor_event_id' => $sponsor_event_id,
                'image' => $path,
            ]);

            $images_s[] = $img;
        }

        return response()->json([
            'message' => $sponsor_event->name.' '.'Images uploaded successfully',
            'images' => $images_s
        ], 201);
    }
    //===============================================================
    public function storeProgram(StoreSponsorEventProgramRequest $request, $sponsor_event_id)
    {
        $sponsor_event = SponsorEvent::findOrFail($sponsor_event_id);
        $validated = $request->validated();

        $program = SponsorEventProgram::create([
            'sponsor_event_id' => $sponsor_event_id,
            'activity' => $validated['activity'],
            'presenter' => $validated['presenter'],
            'comunication' => $validated['comunication'],
        ]);

        return response()->json([
            'message' => $sponsor_event->name.' '.'Program added successfully',
            'program' => $program
        ], 201);
    }
    //===============================================================
    public function update(UpdateSponsorEventRequest $request, $sponsor_event_id)
    {
        $sponsor_event = SponsorEvent::findOrFail($sponsor_event_id);

        // التعديل مسموح فقط إذا كانت الفعالية مسودة
        if ($sponsor_event->copy_status !== 'draft')
        {
            return response()->json([
                'message' => 'Cannot update event after publishing',
                'status' => $sponsor_event->copy_status
            ], 403);
        }

        $validated = $request->validated();

        $exhibition_start = Carbon::parse($sponsor_event->exhibition->start_date);
        $exhibition_end   = Carbon::parse($sponsor_event->exhibition->end_date);

        // إذا تم تعديل الوقت
        if (isset($validated['start_time']) && isset($validated['end_time']))
        {
            $start = Carbon::parse($validated['start_time']);
            $end   = Carbon::parse($validated['end_time']);

            if ($start->lt($exhibition_start) || $end->gt($exhibition_end))
            {
                return response()->json([
                    'message' => 'Event duration must be within exhibition dates',
                    'exhibition_start' => $exhibition_start->toDateTimeString(),
                    'exhibition_end' => $exhibition_end->toDateTimeString(),
                ], 422);
            }

            $validated['duration_days'] = $start->diffInDays($end) + 1;

            //تحديث الحالة
            if ($start->isToday())
            {
                $validated['status'] = 'ongoing';
            } elseif ($start->isFuture())
            {
                $validated['status'] = 'upcoming';
            } else
            {
                $validated['status'] = 'finished';
            }
        }

        // إذا تم تعديل عدد المقاعد
        if (isset($validated['max_participants']))
        {
            $validated['total_seats'] = $validated['max_participants'];
        }

        $sponsor_event->update($validated);

        return response()->json([
            'message' => 'Sponsor Event updated successfully',
            'sponsor_event' => $sponsor_event
        ], 200);
    }
    //===============================================================
    public function storeInvitation(StoreSponsorEventInvitationRequest $request, $sponsor_event_id)
    {
        $sponsor_event = SponsorEvent::findOrFail($sponsor_event_id);
        $validated = $request->validated();

        $invitation = SponsorEventInvitation::create([
            'sponsor_event_id' => $sponsor_event_id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'method_send' => $validated['method_send'],
            'status' => 'pending',
        ]);

        $qr = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=ticket_id:" . $invitation->id;
        $invitation->update(['qr_code' => $qr,]);

        return response()->json([
            'message' => $sponsor_event->name.' '.'Invitation added successfully',
            'invitation' => $invitation
        ], 201);
    }
    //===============================================================
    public function publish($sponsor_event_id)//نشر فعالية
    {
        $sponsor_event = SponsorEvent::findOrFail($sponsor_event_id);

        // النشر مسموح فقط إذا كانت مسودة
        if ($sponsor_event->copy_status !== 'draft')
        {
            return response()->json([
                'message' => 'Cannot publish event unless it is in draft status',
                'current_status' => $sponsor_event->copy_status
            ], 403);
        }

        // لا يمكن نشر فعالية بدأت أو انتهت
        if (Carbon::parse($sponsor_event->start_time)->lte(now()))
        {
            return response()->json([
                'message' => 'Cannot publish an sponsor event that has already started or finished',
                'sponsor_event_start_time' => $sponsor_event->start_time,
                'now' => now()->toDateTimeString(),
            ], 422);
        }

        $exhibition_start = Carbon::parse($sponsor_event->exhibition->start_date);
        $exhibition_end   = Carbon::parse($sponsor_event->exhibition->end_date);

        $start = Carbon::parse($sponsor_event->start_time);
        $end   = Carbon::parse($sponsor_event->end_time);

        // التحقق أن الفعالية ضمن مدة المعرض
        if ($start->lt($exhibition_start) || $end->gt($exhibition_end))
        {
            return response()->json([
                'message' => 'Event duration must be within exhibition dates before publishing',
                'exhibition_start' => $exhibition_start->toDateTimeString(),
                'exhibition_end' => $exhibition_end->toDateTimeString(),
            ], 422);
        }

        // نشر
        $sponsor_event->copy_status = 'active';
        $sponsor_event->publish_date = Carbon::now()
        ->locale('en')
        ->translatedFormat('l, j F Y');

        $sponsor_event->save();

        return response()->json([
            'message' => 'Sponsor Event published successfully',
            'sponsor_event' => $sponsor_event
        ], 200);
    }
    //===============================================================
    public function delete($sponsor_event_id)
    {
        $sponsor_event = SponsorEvent::findOrFail($sponsor_event_id);

        // الحذف مسموح فقط إذا كانت مسودة
        if ($sponsor_event->copy_status !== 'draft')
        {
            return response()->json([
                'message' => 'Cannot delete event unless it is in draft status',
                'current_status' => $sponsor_event->copy_status
            ], 403);
        }

        $sponsor_event->delete();

        return response()->json([
            'message' => 'Sponsor Event deleted successfully'
        ], 200);
    }
    //===============================================================
    public function getMySponsorEvents()//o
    {
        $organizer = Auth::user()->organizer;
        $my_exhibition =$organizer->exhibition;

        $sponsor_events = SponsorEvent::where('exhibition_id', $my_exhibition->id)
            ->orderBy('start_time', 'asc')
            ->get();

        $sponsor_events_data =  $sponsor_events->map(function ($sp_ev)
        {
            return
            [
                'name' => $sp_ev->name,
                'type' => $sp_ev->type,
                'copy_status' => $sp_ev->copy_status,
                'is_general_invitation' => $sp_ev->is_general_invitation,
                'description' => $sp_ev->description,
                'start_time' => Carbon::parse($sp_ev->start_time)->format('Y-m-d'),
                'place' => $sp_ev->place,
                'rate_registration' => $sp_ev->registered_count.'/'.$sp_ev->max_participants,
            ];

        });

        return response()->json([
            'sponsor_events' => $sponsor_events_data
        ], 200);
    }
    //===============================================================
    public function show($sponsor_event_id)//عرض فعالية اعلانية معينة/o
    {
        $sponsor_event = SponsorEvent::findOrFail($sponsor_event_id);

        $sponsor_event_data =
        [
            'name' => $sponsor_event->name,
            'type' => $sponsor_event->type,
            'publish_date' => $sponsor_event->publish_date,
            'start_time' => $sponsor_event->start_time,
            'end_time' => $sponsor_event->end_time,
            'place' => $sponsor_event->place,

            'rate_registration' => $sponsor_event->registered_count.'/'.$sponsor_event->max_participants,
            'booking_rate'=> $sponsor_event->max_participants > 0
                ? round(($sponsor_event->registered_count / $sponsor_event->max_participants) * 100, 2)
                : 0,

            'description' => $sponsor_event->description,
            'is_general_invitation' => $sponsor_event->is_general_invitation,
            'sponsor_event_programs' => $sponsor_event->Programs()->orderBy('id', 'asc')->get(),
            'sponsor_event_images' => $sponsor_event->sponsorEventImages,
        ];

        return response()->json([
            'sponsor_event' => $sponsor_event_data
        ], 200);
    }
    //===============================================================
    public function getStatisticsSponsorEvent($sponsor_event_id)//احصائيات فعالية اعلانية
    {
        $sponsor_event = SponsorEvent::findOrFail($sponsor_event_id);
        $tickets = SponserEventTicket::where('sponsor_event_id', $sponsor_event_id);

        // عدد التذاكر
        $approved_ticket = $tickets->clone()->where('status', 'approved')->count();
        $pending_ticket  = $tickets->clone()->where('status', 'pending')->count();
        $rejected_ticket = $tickets->clone()->where('status', 'rejected')->count();

        // الإيرادات (مجموع المبالغ للتذاكر الموافق عليها)
        $revenue = $tickets->clone()->where('status', 'approved')->sum('amount');

        $statistics =
        [
            'max_participants' => $sponsor_event->max_participants,
            'registered_count' => $sponsor_event->registered_count,
            'total_seats'      => $sponsor_event->total_seats,
            'scanned_count'    => $sponsor_event->scanned_count,
            'approved_ticket'  => $approved_ticket,
            'revenue'          => $revenue,

            'registered_rate'  => $approved_ticket > 0
                ? round(($sponsor_event->scanned_count / $approved_ticket) * 100, 2)
                : 0,
            'booking_rate' => $sponsor_event->max_participants > 0
                ? round(($sponsor_event->registered_count / $sponsor_event->max_participants) * 100, 2)
                : 0,

            'pending_ticket'   => $pending_ticket,
            'rejected_ticket'  => $rejected_ticket,
        ];

        return response()->json([
            'statistics' => $statistics
        ], 200);
    }
    //===============================================================
    //دعوات
    //===============================================================
    public function statisticsSponsorEventInvitations($sponsor_event_id)//احصائيات الدعوات في فعالية ما
    {
        $invitations = SponsorEventInvitation::where('sponsor_event_id', $sponsor_event_id);

        $confirmed_count = $invitations->clone()->where('status', 'confirmed')->count();
        $pending_count = $invitations->clone()->where('status', 'pending')->count();
        $attended_count = $invitations->clone()->where('status', 'attended')->count();
        $cancelled_count = $invitations->clone()->where('status', 'cancelled')->count();

        $statistics=
        [
            'confirmed_count'=>$confirmed_count,
            'pending_count'=>$pending_count,
            'attended_count'=>$attended_count,
            'cancelled_count'=>$cancelled_count,
        ];

        return response()->json([
            'statistics' => $statistics
        ], 200);
    }
    //===============================================================
    public function getAllInvitations($sponsor_event_id)
    {
        $invitations = SponsorEventInvitation::where('sponsor_event_id', $sponsor_event_id)->get();

        return response()->json([
            'invitations' => $invitations
        ], 200);
    }
    //===============================================================
    public function showInvitation($invitation_id)
    {
        $invitation = SponsorEventInvitation::findOrFail($invitation_id);

        $invitation_data=
        [
            'method_send' => $invitation->method_send,
            'sponsor_event_name' => $invitation->sponsorEvent->name,
            'description' => $invitation->sponsorEvent->description,
            'start_time' => Carbon::parse($invitation->sponsorEvent->start_time)->format('Y-m-d'),
            'time' => Carbon::parse($invitation->sponsorEvent->start_time)->format('h:i A').' _ '.Carbon::parse($invitation->sponsorEvent->end_time)->format('h:i A'),
            'place' => $invitation->sponsorEvent->place,
            'name' => $invitation->name,
            'email' => $invitation->email,
            'qr_code' => $invitation->qr_code,
        ];

        return response()->json([
            'invitation' => $invitation_data
        ], 200);
    }
    //===============================================================
    public function confirmInvitation($invitation_id)
    {
        $invitation = SponsorEventInvitation::findOrFail($invitation_id);

        // لا يمكن تأكيد دعوة ملغاة أو مسجلة حضور
        if (in_array($invitation->status, ['attended', 'cancelled']))
        {
            return response()->json([
                'message' => 'Cannot confirm this invitation'
            ], 403);
        }

        $invitation->status = 'confirmed';
        $invitation->save();

        return response()->json([
            'message' => 'Invitation confirmed successfully',
            'invitation' => $invitation
        ], 200);
    }
    //===============================================================
    public function attendInvitation($invitation_id)
    {
        $invitation = SponsorEventInvitation::findOrFail($invitation_id);

        if ($invitation->status !== 'confirmed')
        {
            return response()->json([
                'message' => 'Only confirmed invitations can be marked as attended'
            ], 403);
        }

        $invitation->status = 'attended';
        $invitation->attended_date = now()->format('Y-m-d h:i A');
        $invitation->save();

        return response()->json([
            'message' => 'Invitation marked as attended',
            'invitation' => $invitation
        ], 200);
    }
    //===============================================================
    public function cancelInvitation($invitation_id)
    {
        $invitation = SponsorEventInvitation::findOrFail($invitation_id);

        if ($invitation->status === 'attended')
        {
            return response()->json([
                'message' => 'Cannot cancel an attended invitation'
            ], 403);
        }

        $invitation->status = 'cancelled';
        $invitation->save();

        return response()->json([
            'message' => 'Invitation cancelled successfully',
            'invitation' => $invitation
        ], 200);
    }
    //===============================================================
    //i
    //===============================================================
    public function featurrdSponsorEventsI()//عرض الفعاليات الاعلانية المميزة للمستثمر
    {
        $invsetor_user=Auth::user()->investor;
        $sponsor_events = SponsorEvent::where('copy_status', 'active')
        ->where('type', $invsetor_user->activity_type)
        ->whereIn('status', ['upcoming', 'ongoing'])
        ->orderBy('start_date', 'asc')
        ->get();

        return response()->json([
            'sponsor_events' => $sponsor_events
        ], 200);

    }
    //===============================================================
    public function getUpcomingSponsorEvents()//للمعارض القادمة
    {
        $exhibitions_id = Exhibition::where('status', 'upcoming')
        ->pluck('id');

        $sponsor_events = SponsorEvent::whereIn('exhibition_id', $exhibitions_id)
            ->where('copy_status', 'active')
            ->orderBy('start_time', 'asc')
            ->get();

        $sponsor_events_data = $sponsor_events->map(function ($sp_ev)
        {
            return
            [
                'id' => $sp_ev->id,
                'name' => $sp_ev->name,
                'type' => $sp_ev->type,
                'exhibition_name' => $sp_ev->exhibition->name,
                'start_date' => Carbon::parse($sp_ev->start_time)->format('Y-m-d'),
                'time' => Carbon::parse($sp_ev->start_time)->format('h:i A').' _ '.Carbon::parse($sp_ev->end_time)->format('h:i A'),
                'place' => $sp_ev->place,
                'description' => $sp_ev->description,
                /*هون في مدة الادراج + عدد خيارات المشاركة + اقل مبلغ للمشاركة*/
                'images' => $sp_ev->sponsorEventImages,
                'is_favorite' => Auth::user()->favorites()
                    ->where('favoritable_id', $sp_ev->id)
                    ->where('favoritable_type', SponsorEvent::class)
                    ->exists()
            ];
        });

        return response()->json([
            'upcoming_sponsor_events' => $sponsor_events_data
        ], 200);
    }
    //===============================================================

}
