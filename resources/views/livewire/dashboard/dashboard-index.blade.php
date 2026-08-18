<div wire:poll.15s="$refresh" class="max-w-7xl mx-auto p-6">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-gray-900">
            {{ $isAdmin ? 'Admin' : 'Staff' }} Booking Dashboard
        </h1>

        <div class="flex flex-wrap items-center gap-3">
            @if ($isAdmin)
                <select wire:change="setBranch($event.target.value ? $event.target.value : null)"
                        class="border-gray-300 rounded-md shadow-sm text-sm">
                    <option value="">All Branches</option>
                    @foreach ($this->branches as $branch)
                        <option value="{{ $branch->id }}" @selected($branchId == $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            @endif

            <div class="flex rounded-md shadow-sm" role="group">
                @foreach ([7, 30, 90] as $days)
                    <button type="button" wire:click="setPeriod({{ $days }})"
                            class="px-3 py-1.5 text-sm border {{ $period === $days ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700' }}">
                        {{ $days }}d
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Each child receives the parent's current scope as props and re-renders
         independently — Livewire diffs only what actually changed. --}}
    <livewire:dashboard.kpi-cards :branch-id="$branchId" :period="$period" :key="'kpi-'.$branchId.'-'.$period" />

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <livewire:dashboard.booking-trend-chart :branch-id="$branchId" :period="$period" :key="'trend-'.$branchId.'-'.$period" />

        @if ($isAdmin)
            <livewire:dashboard.branch-comparison-table :period="$period" :key="'branches-'.$period" />
        @endif
    </div>

    <div class="mt-6">
        <livewire:dashboard.pending-approval-queue :branch-id="$branchId" :key="'queue-'.$branchId" />
    </div>
</div>
