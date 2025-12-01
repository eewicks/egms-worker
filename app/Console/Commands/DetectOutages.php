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
    protected $description = 'Detect devices that went offline and record outages';

    public function handle()
    {
        $this->info("Worker running...");

        $timeout = 60; 
        $now = Carbon::now();

        $devices = Device::all();

        foreach ($devices as $device) {

            $isOffline = $device->last_seen &&
                         $device->last_seen->lt($now->subSeconds($timeout));

            // DEVICE OFFLINE → CREATE OUTAGE
            if ($isOffline && $device->status !== 'OFF') {

                $household = Household::where('name', $device->household_name)->first();
                $householdId = $household ? $household->id : null;

                $weekNumber = now()->weekOfYear;
                $isoYear = now()->isoWeekYear;

                Outage::create([
                    'device_id'    => $device->id,
                    'household_id' => $householdId,
                    'started_at'   => now(),
                    'status'       => 'active',
                    'week_number'  => $weekNumber,
                    'iso_year'     => $isoYear,
                ]);

                $device->status = 'OFF';
                $device->save();

                $this->info("[OUTAGE CREATED] Device {$device->id}");
                continue;
            }

            // DEVICE ONLINE AGAIN → CLOSE OUTAGE
            if (!$isOffline && $device->status === 'OFF') {

                $active = Outage::where('device_id', $device->id)
                    ->whereNull('ended_at')
                    ->first();

                if ($active) {
                    $active->ended_at = now();
                    $active->duration_seconds = now()->diffInSeconds($active->started_at);
                    $active->status = 'closed';
                    $active->save();

                    $this->info("[OUTAGE CLOSED] Device {$device->id}");
                }

                $device->status = 'ON';
                $device->save();
            }
        }

        $this->info("Worker cycle complete.");
        return Command::SUCCESS;
    }
}
