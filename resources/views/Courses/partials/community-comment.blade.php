<div class="comment {{ $comment->user->role === 'teacher' ? 'teacher-comment' : '' }}">
    <div class="comment-head">
        <span class="comment-author">{{ $comment->user->name }}</span>
        @if ($comment->user->role === 'teacher')
            <span class="teacher-badge sm">المدرّس</span>
        @endif
        <span class="meta">{{ $comment->created_at->diffForHumans() }}</span>
    </div>
    <div class="comment-body">{{ $comment->body }}</div>
</div>
