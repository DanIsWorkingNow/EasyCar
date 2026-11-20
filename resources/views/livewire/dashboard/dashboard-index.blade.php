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

    @php($ops = app(\App\Services\Rentals\RentalOperationsService::class)->summary($branchId))
    <a href="{{ route('ops.board', $branchId ? ['branch_id' => $branchId] : []) }}" class="card mb-6 flex flex-wrap items-center gap-x-6 gap-y-3 px-5 py-4 transition hover:border-white/20">
        <span class="flex items-center gap-2 font-semibold"><x-icon name="car" class="text-brand" /> Today's operations</span>
        <span class="text-sm text-mute"><span class="font-semibold text-match">{{ $ops['pickups_today'] }}</span> pick-ups</span>
        <span class="text-sm text-mute"><span class="font-semibold text-amber-300">{{ $ops['returns_today'] }}</span> returns due</span>
        <span class="text-sm text-mute"><span class="font-semibold text-sky-300">{{ $ops['on_trip'] + $ops['returns_today'] + $ops['overdue'] }}</span> on trip</span>
        <span class="text-sm text-mute"><span class="font-semibold {{ $ops['overdue'] ? 'text-red-400' : 'text-white' }}">{{ $ops['overdue'] }}</span> overdue</span>
        <span class="ml-auto inline-flex items-center gap-1 text-sm font-semibold text-white/80">Open board <x-icon name="arrow-right" class="size-4" /></span>
    </a>

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
