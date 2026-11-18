<div wire:poll.15s="$refresh">
    <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="eyebrow">{{ $isAdmin ? 'All branches' : (auth()->user()->branch?->name ?? 'Your branch') }}</p>
            <h1 class="display mt-2 text-5xl">{{ $isAdmin ? 'Business overview' : 'Branch overview' }}</h1>
            <p class="mt-1 flex items-center gap-2 text-sm text-subtle"><span class="size-2 animate-pulse rounded-full bg-match"></span> Live · refreshes every 15 seconds</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($isAdmin)
                <select wire:change="setBranch($event.target.value ? $event.target.value : null)" class="input w-auto" aria-label="Branch">
                    <option value="">All branches</option>
                    @foreach ($this->branches as $branch)
                        <option value="{{ $branch->id }}" @selected($branchId == $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            @endif
            <div class="inline-flex rounded-md bg-ink-700 p-1 ring-1 ring-white/10" role="group" aria-label="Period">
                @foreach ([7, 30, 90] as $days)
                    <button type="button" wire:click="setPeriod({{ $days }})" aria-pressed="{{ $period === $days ? 'true' : 'false' }}"
                            class="rounded px-3 py-1.5 text-sm font-semibold transition {{ $period === $days ? 'bg-white text-black' : 'text-mute hover:text-white' }}">{{ $days }}d</button>
                @endforeach
            </div>
        </div>
    </div>

    <livewire:dashboard.kpi-cards :branch-id="$branchId" :period="$period" :key="'kpi-'.$branchId.'-'.$period" />

    <div class="mt-6 grid gap-6 {{ $isAdmin ? 'xl:grid-cols-[1.5fr_1fr]' : '' }}">
        <livewire:dashboard.booking-trend-chart :branch-id="$branchId" :period="$period" :key="'trend-'.$branchId.'-'.$period" />
        @if ($isAdmin)
            <livewire:dashboard.branch-comparison-table :period="$period" :key="'branches-'.$period" />
        @endif
    </div>

    <div class="mt-6">
        <livewire:dashboard.pending-approval-queue :branch-id="$branchId" :key="'queue-'.$branchId" />
    </div>
</div>
