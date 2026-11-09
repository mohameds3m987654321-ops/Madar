<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Models\Course;
use App\Models\Scopes\PublishedCoursesScope;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(SearchRequest $request): View
    {
        $filters = $request->validated();
        $query = trim($filters['q'] ?? '');
        $type = $filters['type'];
        $results = null;

        if ($query !== '') {
            $results = match ($type) {
                'courses' => $this->searchCourses($query, $request->user()),
                'teachers' => $this->searchTeachers($query),
                'users' => $this->searchUsers($query),
            };
        }

        return view('search.index', [
            'query' => $query,
            'type' => $type,
            'results' => $results,
        ]);
    }

    private function searchCourses(string $query, User $user): LengthAwarePaginator
    {
        $courses = Course::query()
            ->with('teacher:id,name')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');

        if ($user->role === 'student') {
            $courses->withGlobalScope(PublishedCoursesScope::class, new PublishedCoursesScope());
        } elseif ($user->role === 'teacher') {
            $courses->where(function (Builder $builder) use ($user) {
                $builder->where('status', 'published')
                    ->orWhere('teacher_id', $user->id);
            });
        }

        return $courses
            ->where(function (Builder $builder) use ($query) {
                $builder->where('title', 'like', '%' . $query . '%')
                    ->orWhere('description', 'like', '%' . $query . '%')
                    ->orWhereHas('teacher', fn (Builder $teacher) => $teacher
                        ->where('name', 'like', '%' . $query . '%'));
            })
            ->orderBy('title')
            ->paginate(12)
            ->withQueryString();
    }

    private function searchTeachers(string $query): LengthAwarePaginator
    {
        return User::query()
            ->where('role', 'teacher')
            ->where('name', 'like', '%' . $query . '%')
            ->with(['courses' => fn (HasMany $courses) => $courses
                ->where('status', 'published')
                ->select(['id', 'teacher_id', 'title'])])
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();
    }

    private function searchUsers(string $query): LengthAwarePaginator
    {
        return User::query()
            ->where(function (Builder $builder) use ($query) {
                $builder->where('name', 'like', '%' . $query . '%')
                    ->orWhere('email', 'like', '%' . $query . '%');
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();
    }
}
