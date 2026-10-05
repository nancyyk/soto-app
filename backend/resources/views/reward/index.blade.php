@extends('layouts.app')
@section('title', 'Manajemen Reward')
@section('content')
<div x-data="{ activeTab: 'rewards' }" class="space-y-6">
    <!-- Header & Tabs -->
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-6" aria-label="Tabs">
            <button 
                @click="activeTab = 'rewards'"
                :class="activeTab === 'rewards' 
                    ? 'border-emerald-500 text-emerald-600 font-semibold' 
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium'"
                class="whitespace-nowrap py-3 px-1 border-b-2 text-sm flex items-center gap-2 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H4.5a1.5 1.5 0 01-1.5-1.5v-8.25M21 11.25H3m18 0l-1.5-4.5H4.5L3 11.25m9-8.25v8.25M9.75 3a2.25 2.25 0 00-2.25 2.25c0 1.243 1.007 2.25 2.25 2.25h2.25V5.25A2.25 2.25 0 009.75 3zm4.5 0a2.25 2.25 0 012.25 2.25c0 1.243-1.007 2.25-2.25 2.25h-2.25V5.25A2.25 2.25 0 0114.25 3z" />
                </svg>
                Katalog Reward
            </button>
            <button 
                @click="activeTab = 'redemptions'"
                :class="activeTab === 'redemptions' 
                    ? 'border-emerald-500 text-emerald-600 font-semibold' 
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium'"
                class="whitespace-nowrap py-3 px-1 border-b-2 text-sm flex items-center gap-2 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.25V3.75A1.125 1.125 0 0013.125 2.625h-8.25A1.125 1.125 0 003.75 3.75v10.5M16.5 7.5h-2.25" />
                </svg>
                Riwayat Penukaran
            </button>
        </nav>
    </div>

    <!-- Tab 1: Reward Management Table -->
    <div x-show="activeTab === 'rewards'">
        <livewire:reward.reward-table />
    </div>

    <!-- Tab 2: Reward Redemptions Table -->
    <div x-show="activeTab === 'redemptions'" x-cloak>
        <livewire:reward.reward-redemptions-table />
    </div>
</div>
@endsection
