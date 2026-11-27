<?php

namespace Database\Seeders;

use App\Models\DonationSlot;
use App\Models\Store;
use App\Models\TapTryCollectSlot;
use Illuminate\Database\Seeder;

/**
 * Jadwal layanan di toko. Slot donasi dan slot Tap Try and Collect dibuat
 * terpisah: jamnya berbeda, kuotanya berbeda, dan tabelnya pun berbeda.
 */
class SlotSeeder extends Seeder
{
    public function run(): void
    {
        $hari = (int) config('colorbox.slot.hari_ke_depan', 14);
        $jamTtc = config('colorbox.slot.jam_ttc');
        $jamDonasi = config('colorbox.slot.jam_donasi');
        $kuotaTtc = (int) config('colorbox.slot.kuota_ttc', 4);
        $kuotaDonasi = (int) config('colorbox.slot.kuota_donasi', 5);

        foreach (Store::where('is_active', true)->get() as $toko) {
            for ($i = 0; $i < $hari; $i++) {
                $tanggal = now()->addDays($i)->toDateString();

                if ($toko->has_ttc) {
                    foreach ($jamTtc as $jam) {
                        TapTryCollectSlot::updateOrCreate(
                            ['store_id' => $toko->id, 'date' => $tanggal, 'start_time' => $jam.':00'],
                            [
                                'end_time' => $this->jamBerikutnya($jam),
                                'quota' => $kuotaTtc,
                                'booked_count' => 0,
                                'status' => 'aktif',
                            ]
                        );
                    }
                }

                if ($toko->has_donation) {
                    foreach ($jamDonasi as $jam) {
                        DonationSlot::updateOrCreate(
                            ['store_id' => $toko->id, 'date' => $tanggal, 'start_time' => $jam.':00'],
                            [
                                'end_time' => $this->jamBerikutnya($jam),
                                'quota' => $kuotaDonasi,
                                'booked_count' => 0,
                                'status' => 'aktif',
                            ]
                        );
                    }
                }
            }
        }
    }

    private function jamBerikutnya(string $jam): string
    {
        [$j, $m] = explode(':', $jam);

        return str_pad((string) ((int) $j + 1), 2, '0', STR_PAD_LEFT).':'.$m.':00';
    }
}
