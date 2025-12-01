<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Device;
use App\Models\Outage;
use App\Models\Household;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class DetectOutages extends Command
{
    protected $signature = 'detect:outages';
    protected $description = 'Detect device outages and send SMS';

    public function handle()
    {
        $timeout = 60; // seconds without heartbeat to consider OFF
        $now = Carbon::now();

        $devices = Device::all();

        foreach ($devices as $device) {

            $isOffline = $device->last_seen &&
                         $device->last_seen->lt($now->subSeconds($timeout));

            // ------------------------
            // DEVICE OFFLINE
            // ------------------------
            if ($isOffline && $device->status !== 'OFF') {

                $household = Household::where('name', $device->household_name)->first();
                $householdId = $household ? $household->id : null;

                Outage::create([
                    'device_id'    => $device->id,
                    'household_id' => $householdId,
                    'started_at'   => now(),
                    'status'       => 'active',
                    'week_number'  => now()->weekOfYear,
                    'iso_year'     => now()->isoWeekYear
                ]);

                $device->update(['status' => 'OFF']);

                if ($device->contact_number) {
                    $this->sendSMS(
                        $device->contact_number,
                        "⚠️ SOLECO Alert: Power outage detected for {$device->household_name}."
                    );
                }

                continue;
            }

            // ------------------------
            // DEVICE ONLINE (RECOVERED)
            // ------------------------
            if (!$isOffline && $device->status === 'OFF') {

                $outage = Outage::where('device_id', $device->id)
                    ->whereNull('ended_at')
                    ->first();

                if ($outage) {
                    $outage->update([
                        'ended_at' => now(),
                        'duration_seconds' => now()->diffInSeconds($outage->started_at),
                        'status' => 'closed'
                    ]);
                }

                $device->update(['status' => 'ON']);
            }
        }

        return Command::SUCCESS;
    }

    private function sendSMS($number, $message)
    {
        Http::asForm()->post("https://api.semaphore.co/api/v4/messages", [
            "apikey"     => env('SEMAPHORE_API_KEY'),
            "number"     => $number,
            "message"    => $message,
            "sendername" => env('SEMAPHORE_SENDER_NAME', 'SOLECO')
        ]);
    }
}
