<?php

namespace App\Livewire\Pengaturan;

use App\Models\Machine;
use App\Models\Setting;
use Livewire\Component;

class PengaturanForm extends Component
{
    public string $depotLat = '-7.2750';

    public string $depotLng = '112.7900';

    public string $depotNama = 'Pos Pengangkutan Pusat ITS';

    /** 'custom' atau machine_id */
    public string $depotSource = 'pos_its';

    public string $tspThreshold = '80';

    public array $machines = [];

    public function mount(): void
    {
        $this->depotLat     = Setting::get('depot_lat', '-7.2750');
        $this->depotLng     = Setting::get('depot_lng', '112.7900');
        $this->depotNama    = Setting::get('depot_nama', 'Pos Pengangkutan Pusat ITS');
        $this->tspThreshold = Setting::get('tsp_threshold', '80');

        $this->machines = Machine::select('id', 'nama_lokasi', 'latitude', 'longitude')
            ->orderBy('id')
            ->get()
            ->toArray();

        // Tentukan nilai depotSource berdasarkan nama
        if ($this->depotNama === 'Pos Pengangkutan Pusat ITS' || $this->depotNama === 'Pos Pengangkutan Pusat') {
            $this->depotSource = 'pos_its';
        } elseif ($this->depotNama === 'Bank Sampah Induk Surabaya') {
            $this->depotSource = 'bank_sampah';
        } elseif ($this->depotNama === 'Kantor Dinas Lingkungan Hidup (DLH)') {
            $this->depotSource = 'dlh';
        } else {
            $matched = collect($this->machines)->first(fn ($m) => $m['nama_lokasi'] === $this->depotNama);
            $this->depotSource = $matched ? 'machine_' . $matched['id'] : 'custom';
        }
    }

    public function updatedDepotSource(string $value): void
    {
        if ($value === 'pos_its') {
            $this->depotNama = 'Pos Pengangkutan Pusat ITS';
            $this->depotLat  = '-7.2750';
            $this->depotLng  = '112.7900';
            return;
        }

        if ($value === 'bank_sampah') {
            $this->depotNama = 'Bank Sampah Induk Surabaya';
            $this->depotLat  = '-7.2820';
            $this->depotLng  = '112.7980';
            return;
        }

        if ($value === 'dlh') {
            $this->depotNama = 'Kantor Dinas Lingkungan Hidup (DLH)';
            $this->depotLat  = '-7.2650';
            $this->depotLng  = '112.7520';
            return;
        }

        if (str_starts_with($value, 'machine_')) {
            $machineId = (int) str_replace('machine_', '', $value);
            $machine = collect($this->machines)->firstWhere('id', $machineId);
            if ($machine) {
                $this->depotNama = $machine['nama_lokasi'];
                $this->depotLat  = (string) $machine['latitude'];
                $this->depotLng  = (string) $machine['longitude'];
            }
            return;
        }
    }

    public function resetToDefaultDepot(): void
    {
        $this->depotSource = 'pos_its';
        $this->depotNama = 'Pos Pengangkutan Pusat ITS';
        $this->depotLat = '-7.2750';
        $this->depotLng = '112.7900';
    }

    public function save(): void
    {
        $this->validate([
            'depotLat'     => 'required|numeric|between:-90,90',
            'depotLng'     => 'required|numeric|between:-180,180',
            'depotNama'    => 'required|string|max:100',
            'tspThreshold' => 'required|integer|min:1|max:100',
        ]);

        Setting::set('depot_lat', $this->depotLat);
        Setting::set('depot_lng', $this->depotLng);
        Setting::set('depot_nama', $this->depotNama);
        Setting::set('tsp_threshold', $this->tspThreshold);

        session()->flash('success', 'Pengaturan berhasil disimpan.');
    }

    public function render()
    {
        return view('livewire.pengaturan.pengaturan-form');
    }
}
