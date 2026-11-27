<?php

namespace Database\Seeders;

use App\Models\DonationSlot;
use App\Models\Store;
use App\Models\TapTryCollectSlot;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Jadwal layanan di toko. Slot donasi dan slot Tap Try and Collect dibuat
 * terpisah: jamnya berbeda, kuotanya berbeda, dan tabelnya pun berbeda.
 *
 * Jam slot mengikuti jam buka toko masing-masing, bukan daftar jam tetap,
 * supaya tidak pernah ada jadwal di luar jam operasional.
 */
class SlotSeeder extends Seeder
{
    public function run(): void
    {
        $hari = (int) config('colorbox.slot.hari_ke_depan', 14);
        $kuotaTtc = (int) config('colorbox.slot.kuota_ttc', 4);
        $kuotaDonasi = (int) config('colorbox.slot.kuota_donasi', 5);
        $donasiMulai = (int) config('colorbox.slot.donasi_jam_pertama', 0);
        $donasiJumlah = (int) config('colorbox.slot.donasi_jumlah_slot', 8);
        $cadangan = config('colorbox.slot.jam_ttc_cadangan', ['buka' => '10:00', 'tutup' => '22:00']);

        foreach (Store::where('is_active', true)->get() as $toko) {
            $jamToko = $this->jamToko($toko, $cadangan);

            if (empty($jamToko)) {
                continue;
            }

            // Donasi memakai sebagian jam awal saja
            $jamDonasi = array_slice($jamToko, $donasiMulai, $donasiJumlah);

            for ($i = 0; $i < $hari; $i++) {
                $tanggal = now()->addDays($i)->toDateString();

                if ($toko->has_ttc) {
                    foreach ($jamToko as $jam) {
                        $this->buatSlot(TapTryCollectSlot::class, $toko->id, $tanggal, $jam, $kuotaTtc);
                    }
                }

                if ($toko->has_donation) {
                    foreach ($jamDonasi as $jam) {
                        $this->buatSlot(DonationSlot::class, $toko->id, $tanggal, $jam, $kuotaDonasi);
                    }
                }
            }
        }
    }

    /**
     * Daftar jam mulai untuk satu toko, per satu jam, dari jam buka sampai
     * satu jam sebelum tutup. Toko buka 10.00 dan tutup 22.00 menghasilkan
     * slot 10.00 sampai 21.00, dan slot terakhir berakhir tepat pukul 22.00.
     */
    private function jamToko(Store $toko, array $cadangan): array
    {
        $buka = Carbon::parse($toko->open_time ?: $cadangan['buka']);
        $tutup = Carbon::parse($toko->close_time ?: $cadangan['tutup']);

        if ($tutup->lte($buka)) {
            return [];
        }

        $jam = [];
        $jalan = $buka->copy();

        while ($jalan->copy()->addHour()->lte($tutup)) {
            $jam[] = $jalan->format('H:i');
            $jalan->addHour();
        }

        return $jam;
    }

    private function buatSlot(string $kelas, int $tokoId, string $tanggal, string $jam, int $kuota): void
    {
        $kelas::updateOrCreate(
            ['store_id' => $tokoId, 'date' => $tanggal, 'start_time' => $jam.':00'],
            [
                'end_time' => $this->jamBerikutnya($jam),
                'quota' => $kuota,
                'booked_count' => 0,
                'status' => 'aktif',
            ]
        );
    }

    private function jamBerikutnya(string $jam): string
    {
        [$j, $m] = explode(':', $jam);

        return str_pad((string) ((int) $j + 1), 2, '0', STR_PAD_LEFT).':'.$m.':00';
    }
}
