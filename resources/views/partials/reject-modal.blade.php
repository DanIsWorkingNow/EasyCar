<x-modal name="reject-booking" title="Reject booking">
    <form method="POST" :action="payload.action" class="space-y-5">
        @csrf @method('PATCH')
        <p class="text-sm text-mute">The customer will be emailed this reason for <span class="font-mono text-white" x-text="payload.reference"></span>.</p>
        <p class="rounded-lg bg-sky-500/10 px-4 py-3 text-sm text-sky-200 ring-1 ring-sky-500/25" x-show="payload.paid">This booking is paid — rejecting it refunds the customer automatically.</p>
        <div>
            <label for="rejection_reason" class="label">Reason <span class="text-brand">*</span></label>
            <textarea id="rejection_reason" name="rejection_reason" rows="3" required maxlength="500" class="input" placeholder="e.g. The car is booked for servicing on these dates."></textarea>
        </div>
        <div class="flex justify-end gap-2">
            <button type="button" class="btn btn-quiet" @click="open = false">Cancel</button>
            <button class="btn btn-danger">Reject booking</button>
        </div>
    </form>
</x-modal>
