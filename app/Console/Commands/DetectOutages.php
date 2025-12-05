<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Device;
use App\Models\Outage;
use Illuminate\Support\Facades\Http;

class DetectOutages extends Command
{
    protected $signature = 'detect:outages';
    protected $description = 'Detect device outages and send SMS';

    public function handle()
    {
        $devices = Device::with('household')->get();

        foreach ($devices as $device) {

            if (!$device->household) {
                continue;
            }

            // Device is OFF = outage started
            if ($device->status === 'OFF') {

                $activeOutage = Outage::where('device_id', $device->id)
                    ->where('status', 'active')
                    ->first();

                if (!$activeOutage) {

                    Outage::create([
                        'device_id' => $device->id,
                        'household_id' => $device->household_id,
                        'started_at' => now(),
                        'status' => 'active',
                        'week_number' => now()->weekOfYear,
                        'iso_year' => now()->year,
                    ]);

                    // Prepare SMS
                    $message = "⚠️ Power Outage detected at " 
                               . $device->household->name 
                               . " in " . $device->barangay;

                    // Send SMS via Semaphore
                    Http::post('https://api.semaphore.co/api/v4/messages', [
                        'apikey' => env('SEMAPHORE_API_KEY'),
                        'number' => $device->household->contact_number,
                        'message' => $message,
                        'sendername' => env('SEMAPHORE_SENDER_NAME', 'EGMS'),
                    ]);
                }
            }

            // Device is ON = outage resolved
            if ($device->status === 'ON') {

                $activeOutage = Outage::where('device_id', $device->id)
                    ->where('status', 'active')
                    ->first();

                if ($activeOutage) {
                    $activeOutage->update([
                        'ended_at' => now(),
                        'status' => 'resolved',
                        'duration_seconds' => now()->diffInSeconds($activeOutage->started_at)
                    ]);
                }
            }
        }
    }
}
