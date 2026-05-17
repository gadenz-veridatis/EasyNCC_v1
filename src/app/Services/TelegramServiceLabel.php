<?php

namespace App\Services;

use App\Models\Service;
use Carbon\Carbon;

class TelegramServiceLabel
{
    /**
     * Build a human-readable label for a service, for use in Telegram messages.
     * Format: #123 — 14:30 Mario Rossi (Fiumicino → Termini)
     * If the date is not today: #123 — 15/04 14:30 Mario Rossi (Fiumicino → Termini)
     */
    public static function get(Service $service): string
    {
        $parts = ["#{$service->id}"];

        // Date/time
        if ($service->pickup_datetime) {
            $pickup = Carbon::parse($service->pickup_datetime);
            $isToday = $pickup->isToday();
            $datePart = $isToday ? $pickup->format('H:i') : $pickup->format('d/m H:i');
            $parts[] = $datePart;
        }

        // First passenger name
        if (!$service->relationLoaded('passengers')) {
            $service->load('passengers');
        }
        if ($service->passengers && $service->passengers->isNotEmpty()) {
            $passenger = $service->passengers->first();
            $name = trim(($passenger->name ?? '') . ' ' . ($passenger->surname ?? ''));
            if ($name) {
                $parts[] = $name;
            }
        }

        // Pickup → Dropoff locations (short names)
        $pickup = trim($service->pickup_location ?? '');
        $dropoff = trim($service->dropoff_location ?? '');
        if ($pickup && $dropoff) {
            $parts[] = "({$pickup} → {$dropoff})";
        } elseif ($pickup) {
            $parts[] = "({$pickup})";
        } elseif ($dropoff) {
            $parts[] = "(→ {$dropoff})";
        }

        return implode(' ', $parts);
    }

    /**
     * Build an HTML-formatted label for Telegram messages (bold ID).
     */
    public static function getHtml(Service $service): string
    {
        $parts = ["<b>#{$service->id}</b>"];

        if ($service->pickup_datetime) {
            $pickup = Carbon::parse($service->pickup_datetime);
            $isToday = $pickup->isToday();
            $datePart = $isToday ? $pickup->format('H:i') : $pickup->format('d/m H:i');
            $parts[] = $datePart;
        }

        if (!$service->relationLoaded('passengers')) {
            $service->load('passengers');
        }
        if ($service->passengers && $service->passengers->isNotEmpty()) {
            $passenger = $service->passengers->first();
            $name = trim(($passenger->name ?? '') . ' ' . ($passenger->surname ?? ''));
            if ($name) {
                $parts[] = $name;
            }
        }

        $pickup = trim($service->pickup_location ?? '');
        $dropoff = trim($service->dropoff_location ?? '');
        if ($pickup && $dropoff) {
            $parts[] = "({$pickup} → {$dropoff})";
        } elseif ($pickup) {
            $parts[] = "({$pickup})";
        } elseif ($dropoff) {
            $parts[] = "(→ {$dropoff})";
        }

        return implode(' ', $parts);
    }
}
