@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-ink">Notifications</h1>
                <p class="text-sm text-ink-muted mt-1">Manage your notifications and alerts</p>
            </div>
            @if ($notifications->total() > 0)
                <div class="flex gap-2">
                    <form action="{{ route('notifications.mark-all-read') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 bg-primary text-white hover:opacity-90">
                            <i class="fa-solid fa-check text-white mr-2" aria-hidden="true"></i>
                            Mark All as Read
                        </button>
                    </form>
                    <form action="{{ route('notifications.destroy-all') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 border border-border hover:bg-black/5" onclick="return confirm('Are you sure? This will delete all notifications.');">
                            <i class="fa-solid fa-trash text-ink mr-2" aria-hidden="true"></i>
                            Clear All
                        </button>
                    </form>
                </div>
            @endif
        </div>

        @if ($notifications->count() > 0)
            <div class="space-y-2">
                @foreach ($notifications as $notification)
                    <div class="p-4 rounded-lg border border-border hover:shadow-md transition-all duration-200 {{ is_null($notification->read_at) ? 'bg-teal-soft' : 'bg-white' }}">
                        <div class="flex gap-4">
                            <div class="flex-1">
                                <h3 class="font-semibold text-ink">{{ $notification->data['title'] ?? 'Notification' }}</h3>
                                <p class="text-sm text-ink-muted mt-1">{{ $notification->data['message'] ?? '' }}</p>
                                <p class="text-xs text-ink-subtle mt-3">
                                    <i class="fa-solid fa-clock mr-1" aria-hidden="true"></i>
                                    {{ $notification->created_at->format('M d, Y \a\t g:i A') }}
                                </p>
                            </div>
                            <div class="flex items-start gap-2">
                                @if (is_null($notification->read_at))
                                    <form action="{{ route('notifications.mark-read', $notification->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="p-2 rounded-lg hover:bg-black/10 transition-colors" title="Mark as read">
                                            <i class="fa-solid fa-check text-primary" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                @endif
                                <form action="{{ route('notifications.destroy', $notification->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 rounded-lg hover:bg-black/10 transition-colors" title="Delete">
                                        <i class="fa-solid fa-trash text-red-500" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $notifications->links() }}
            </div>
        @else
            <div class="rounded-lg border border-border p-12 text-center">
                <i class="fa-solid fa-bell-slash text-4xl text-ink-subtle opacity-30 mb-3" aria-hidden="true"></i>
                <p class="text-lg font-semibold text-ink-muted">No notifications</p>
                <p class="text-sm text-ink-subtle mt-1">You're all caught up! When you have new notifications, they'll appear here.</p>
            </div>
        @endif
    </div>
@endsection
