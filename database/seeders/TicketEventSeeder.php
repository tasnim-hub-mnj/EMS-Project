<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\Visitor;
use App\Models\EventTicket;
use Illuminate\Support\Str;

class TicketEventSeeder extends Seeder
{
    public function run(): void
    {
        // Fetch all events
        $events = Event::all();

        // Fetch all visitors
        $visitors = Visitor::all();

        // If no visitors or events exist, stop
        if ($events->isEmpty() || $visitors->isEmpty())
        {
            return;
        }

        foreach ($events as $event)
        {
            // Create 5 tickets per event
            for ($i = 0; $i < 5; $i++)
            {
                $visitor = $visitors->random();

                // Determine ticket status
                $status = collect(['pending', 'approved', 'rejected'])->random();

                // Generate QR only if approved
                $qr = $status === 'approved'
                    ? Str::uuid()->toString()
                    : null;

                // Determine amount (free or paid)
                $amount = $event->ticket_type === 'paid'
                    ? $event->ticket_price
                    : 0;

                EventTicket::create([
                    'visitor_id'   => $visitor->id,
                    'event_id'     => $event->id,
                    'status'       => $status,
                    'qr_code'      => $qr,
                    'amount'       => $amount,
                    'booked_at'    => now(),
                ]);
            }
        }
    }
}

