<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::orderBy('name')->get();

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        return view('users.create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::create([
            'name'     => $request->input('name'),
            'email'    => $request->input('email'),
            'password' => $request->input('password'),
            'is_admin' => $request->boolean('is_admin'),
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuário criado com sucesso.');
    }

    public function edit(User $user): View
    {
        return view('users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = [
            'name'     => $request->input('name'),
            'email'    => $request->input('email'),
            'is_admin' => $request->boolean('is_admin'),
        ];

        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }

        // Prevent removing own admin status
        if ($user->id === auth()->id()) {
            unset($data['is_admin']);
        }

        $user->update($data);

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuário atualizado com sucesso.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()
                ->route('users.index')
                ->with('error', 'Você não pode excluir sua própria conta.');
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuário removido com sucesso.');
    }
}
