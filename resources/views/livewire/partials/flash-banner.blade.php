@if ($flash ?? null)
    <div class="flex items-center gap-2.5 rounded-control bg-success-tint px-4 py-2.5 text-sm font-semibold text-success">
        <i class="fa-solid fa-circle-check"></i> {{ $flash }}
    </div>
@endif
