<?php

namespace App\Livewire\Pengaturan;

use App\Models\Setting;
use Livewire\Component;

class PengaturanForm extends Component
{
    public string $poinPerBotol = "10";
    public string $depotLat    = "-7.275";
    public string $depotLng    = "112.790";
    public string $depotNama   = "Pos Pengangkutan Pusat";
    public string $tspThreshold = "80";

    public function mount(): void
    {
        $this->poinPerBotol  = Setting::get("poin_per_botol", "10");
        $this->depotLat      = Setting::get("depot_lat", "-7.275");
        $this->depotLng      = Setting::get("depot_lng", "112.790");
        $this->depotNama     = Setting::get("depot_nama", "Pos Pengangkutan Pusat");
        $this->tspThreshold  = Setting::get("tsp_threshold", "80");
    }

    public function save(): void
    {
        $this->validate([
            "poinPerBotol"  => "required|integer|min:1|max:1000",
            "depotLat"      => "required|numeric|between:-90,90",
            "depotLng"      => "required|numeric|between:-180,180",
            "depotNama"     => "required|string|max:100",
            "tspThreshold"  => "required|integer|min:1|max:100",
        ]);

        Setting::set("poin_per_botol", $this->poinPerBotol);
        Setting::set("depot_lat",      $this->depotLat);
        Setting::set("depot_lng",      $this->depotLng);
        Setting::set("depot_nama",     $this->depotNama);
        Setting::set("tsp_threshold",  $this->tspThreshold);

        session()->flash("success", "Pengaturan berhasil disimpan.");
    }

    public function render()
    {
        return view("livewire.pengaturan.pengaturan-form");
    }
}
