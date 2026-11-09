<?php

namespace App\Http\Controllers\Settings;

use App\Domain\Access\Actions\CreateRole;
use App\Domain\Access\Actions\DeleteRole;
use App\Domain\Access\Actions\UpdateRolePermissions;
use App\Domain\Access\Models\Role;
use App\Domain\Access\PermissionRegistry;
use App\Domain\Access\RoleCatalog;
use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequest;
use App\Support\Profile\Profile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::query()
            ->where('scope', Profile::current()->value)
            ->withCount(['users', 'permissions'])
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get();

        return view('roles.index', ['roles' => $roles]);
    }

    public function create(Request $request): View
    {
        return view('roles.create', ['groups' => $this->permissionGroups(), 'yours' => $request->user()->permissionKeys()]);
    }

    public function store(RoleRequest $request, CreateRole $create): RedirectResponse
    {
        $role = $create->handle(
            (string) $request->input('name'),
            $request->input('description'),
            (array) $request->input('permissions', []),
            $request->user(),
        );

        return redirect()->route('roles.edit', $role)->with('status', 'Role created.');
    }

    public function edit(Request $request, Role $role): View
    {
        $this->ensureProfileRole($role);

        return view('roles.edit', [
            'role' => $role,
            'locked' => RoleCatalog::isLocked(Profile::current(), $role->slug),
            'groups' => $this->permissionGroups(),
            'granted' => $role->permissions()->pluck('permissions.key')->all(),
            'yours' => $request->user()->permissionKeys(),
            'userCount' => $role->users()->count(),
        ]);
    }

    public function update(RoleRequest $request, Role $role, UpdateRolePermissions $update): RedirectResponse
    {
        $this->ensureProfileRole($role);

        $update->handle($role, (array) $request->input('permissions', []), $request->user());

        return redirect()->route('roles.edit', $role)->with('status', 'Role saved.');
    }

    public function destroy(Request $request, Role $role, DeleteRole $delete): RedirectResponse
    {
        $this->ensureProfileRole($role);

        $delete->handle($role, $request->user());

        return redirect()->route('roles.index')->with('status', 'Role deleted.');
    }

    private function ensureProfileRole(Role $role): void
    {
        abort_unless($role->scope === Profile::current()->value, 404);
    }

    /**
     * @return array<string, array<string, string>> group name => [permission key => description]
     */
    private function permissionGroups(): array
    {
        $groups = [];

        foreach (PermissionRegistry::forProfile(Profile::current()) as $key => $definition) {
            $groups[$definition['group']][$key] = $definition['description'];
        }

        return $groups;
    }
}
