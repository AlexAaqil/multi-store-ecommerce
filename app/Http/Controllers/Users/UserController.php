<?php

namespace App\Http\Controllers\Users;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Enums\UserRoles;
use App\Http\Resources\UserResource;
use App\Http\Requests\Users\UserRequest;
use Exception;

class UserController extends Controller
{
    private function getRoleCounts(): array
    {
        $counts = DB::table('users')
            ->select('role', DB::raw('count(*) as count'))
            ->groupBy('role')
            ->get();

        $result = [];

        foreach ($counts as $item) {
            $role = UserRoles::tryFrom((int) $item->role);

            if ($role) {
                $result[$role->value] = [
                    'count' => $item->count,
                    'label' => $role->label(),
                    'value' => $role->value,
                ];
            }
        }

        // Sort by label but preserve keys
        uasort($result, function ($a, $b) {
            return $a['label'] <=> $b['label'];
        });

        return $result;
    }

    public function index(Request $request)
    {
        $role_counts = $this->getRoleCounts();

        $query = User::query();

        // Only apply filter if search parameter exists and is not empty
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Only apply filter if role parameter exists and is not empty
        if ($request->filled('role')) {
            $query->filterByRole($request->role);
        }

        $query->orderByRolePriority();

        $users = $query->paginate(20);

        return inertia('users/Index', [
            'users' => UserResource::collection($users),
            'role_counts' => $role_counts,
            'filters' => $request->only(['search', 'role'])
        ]);
    }

    public function create()
    {
        return inertia('users/Create');
    }

    public function store(UserRequest $request)
    {
        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'status' => 1,
            ]);

            DB::commit();

            return redirect()
                ->route('users.index')
                ->with([
                    'message' => 'User created successfully',
                    'type' => 'success'
                ]);
        } catch (Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with([
                    'message' => 'Failed to create user: ' . $e->getMessage(),
                    'type' => 'error'
                ]);
        }
    }

    public function edit(User $user)
    {
        return inertia('users/Edit', [
            'user' => new UserResource($user)
        ]);
    }

    public function update(UserRequest $request, User $user)
    {
        try {
            DB::beginTransaction();

            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'role' => $request->role,
                'status' => $request->status,
            ]);

            if ($request->password) {
                $user->update([
                    'password' => Hash::make($request->password),
                ]);
            }

            DB::commit();

            return redirect()
                ->route('users.index')
                ->with([
                    'message' => 'User updated successfully',
                    'type' => 'success'
                ]);
        } catch (Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with([
                    'message' => 'Failed to update user: ' . $e->getMessage(),
                    'type' => 'error'
                ]);
        }
    }

    public function destroy(User $user)
    {
        try {
            if (Auth::id() === $user->id) {
                return back()->with([
                    'message' => 'You cannot delete your own account.',
                    'type' => 'error'
                ]);
            }

            $user->delete();

            return redirect()
                ->route('users.index')
                ->with([
                    'message' => 'User deleted successfully',
                    'type' => 'success'
                ]);
        } catch (Exception $e) {
            return back()
                ->with([
                    'message' => 'Failed to delete user: ' . $e->getMessage(),
                    'type' => 'error'
                ]);
        }
    }
}
