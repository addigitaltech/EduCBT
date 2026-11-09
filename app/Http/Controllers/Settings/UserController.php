<?php

namespace App\Http\Controllers\Settings;

use App\Domain\Access\Models\Role;
use App\Domain\Access\RoleCatalog;
use App\Domain\Identity\Actions\CreateStaffUser;
use App\Domain\Identity\Actions\ResetUserPassword;
use App\Domain\Identity\Actions\SetUserStatus;
use App\Domain\Identity\Actions\UpdateStaffUser;
use App\Domain\Identity\Enums\UserStatus;
use App\Domain\Identity\Enums\UserType;
use App\Domain\Identity\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Support\Profile\Profile;
use App\Support\Sql\Like;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::enum(UserStatus::class)],
        ]);

        $users = User::query()
            ->where('user_type', UserType::staffFor(Profile::current()))
            ->with('roles:id,name')
            ->when($filters['q'] ?? null, function ($query, string $term): void {
                $like = Like::contains($term);
                $query->where(fn ($q) => $q->whereRaw("name like ? escape '!'", [$like])->orWhereRaw("login_id like ? escape '!'", [$like]));
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('users.index', ['users' => $users, 'filters' => $filters]);
    }

    public function create(): View
    {
        return view('users.create', ['roles' => $this->assignableRoles()]);
    }

    public function store(StoreUserRequest $request, CreateStaffUser $create): RedirectResponse
    {
        [$user, $temporaryPassword] = $create->handle($request->validated(), $request->user());

        return redirect()->route('users.edit', $user)
            ->with('status', 'User added.')
            ->with('temporary_password', $temporaryPassword);
    }

    public function edit(User $user): View
    {
        $this->ensureStaff($user);

        return view('users.edit', [
            'user' => $user->load('roles:id,name'),
            'roles' => $this->assignableRoles(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, UpdateStaffUser $update): RedirectResponse
    {
        $this->ensureStaff($user);

        $update->handle($user, $request->validated(), $request->user(), true);

        return redirect()->route('users.edit', $user)->with('status', 'User saved.');
    }

    public function status(Request $request, User $user, SetUserStatus $setStatus): RedirectResponse
    {
        $this->ensureStaff($user);

        $data = $request->validate(['status' => ['required', Rule::in([UserStatus::Active->value, UserStatus::Inactive->value])]]);

        $setStatus->handle($user, UserStatus::from($data['status']), $request->user());

        return redirect()->route('users.edit', $user)->with(
            'status',
            $data['status'] === UserStatus::Active->value ? 'User activated.' : 'User deactivated. They can no longer sign in.',
        );
    }

    public function resetPassword(Request $request, User $user, ResetUserPassword $reset): RedirectResponse
    {
        $this->ensureStaff($user);

        $temporaryPassword = $reset->handle($user, $request->user());

        return redirect()->route('users.edit', $user)
            ->with('status', 'Password reset. Give the temporary password to the user; they must choose a new one when they sign in.')
            ->with('temporary_password', $temporaryPassword);
    }

    /** Only staff of this profile are managed on this screen. */
    private function ensureStaff(User $user): void
    {
        abort_unless($user->user_type === UserType::staffFor(Profile::current()), 404);
    }

    /** @return Collection<int, Role> */
    private function assignableRoles(): Collection
    {
        $profile = Profile::current();

        return Role::query()
            ->where('scope', $profile->value)
            ->orderBy('name')
            ->get()
            ->filter(fn (Role $role) => RoleCatalog::isAssignable($profile, $role->slug))
            ->values();
    }
}
