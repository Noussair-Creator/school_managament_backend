<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reservation;
use App\Models\Material;

class RestoreMaterialQuantity extends Command
{
    protected $signature = 'materials:restore-quantity';
    protected $description = 'Restore material quantity after reservation end date.';

    public function handle()
    {
        $reservations = Reservation::where('end_time', '<=', now())
            ->where('status', '!=', 'completed')
            ->get();

        foreach ($reservations as $reservation) {
            foreach ($reservation->materials as $material) {
                // We load the latest material from database
                $freshMaterial = Material::find($material->id);

                if ($freshMaterial) {
                    $freshMaterial->quantity_available += $material->pivot->quantity_requested;
                    $freshMaterial->save();
                }
            }

            $reservation->status = 'completed';
            $reservation->save();
        }

        $this->info('Material quantities restored successfully.');
    }
}
