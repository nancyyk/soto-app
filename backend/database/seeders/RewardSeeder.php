<?php

namespace Database\Seeders;

use App\Models\Reward;
use Illuminate\Database\Seeder;

class RewardSeeder extends Seeder
{
    public function run(): void
    {
        $rewards = [
            [
                'kode' => 'VCR-001',
                'nama' => 'Voucher Belanja Rp 10.000',
                'deskripsi' => 'Voucher belanja minimarket dapat digunakan di seluruh gerai rekanan SOTO.',
                'poin' => 50,
                'stok' => 20,
                'kategori' => 'Voucher',
                'gambar' => 'rewards/voucher1.png',
                'is_active' => true,
            ],
            [
                'kode' => 'PLS-001',
                'nama' => 'Pulsa All Operator Rp 10.000',
                'deskripsi' => 'Pengisian pulsa reguler untuk Telkomsel, Indosat, XL, Tri, dan Smartfren.',
                'poin' => 60,
                'stok' => 15,
                'kategori' => 'Pulsa',
                'gambar' => 'rewards/pulsa1.png',
                'is_active' => true,
            ],
            [
                'kode' => 'EWL-001',
                'nama' => 'Saldo OVO Rp 20.000',
                'deskripsi' => 'Top up saldo OVO Cash langsung ke nomor akun dompet digital Anda.',
                'poin' => 100,
                'stok' => 10,
                'kategori' => 'E-Wallet',
                'gambar' => 'rewards/ovo1.png',
                'is_active' => true,
            ],
            [
                'kode' => 'MER-001',
                'nama' => 'Tumbler Ramah Lingkungan SOTO',
                'deskripsi' => 'Tumbler stainless steel eksklusif SOTO 500ml untuk mendukung gaya hidup bebas plastik.',
                'poin' => 150,
                'stok' => 8,
                'kategori' => 'Merchandise',
                'gambar' => 'rewards/tumbler1.png',
                'is_active' => true,
            ],
        ];

        foreach ($rewards as $reward) {
            Reward::updateOrCreate(
                ['kode' => $reward['kode']],
                $reward
            );
        }
    }
}
