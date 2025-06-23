<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        try {
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 20);
            $search = $request->get('search');
            $role = $request->get('role');
            $active = $request->get('active');

            $query = User::query();

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            if ($role) {
                $query->where('role', $role);
            }

            if ($active !== null) {
                $query->where('is_active', $active === 'true');
            }

            $users = $query->orderBy('created_at', 'desc')
                          ->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'users' => $users->items(),
                    'pagination' => [
                        'page' => $users->currentPage(),
                        'limit' => $users->perPage(),
                        'total' => $users->total(),
                        'pages' => $users->lastPage(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while fetching users',
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $user = User::with(['orders' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            }])->find($id);

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'user' => $user,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while fetching user',
            ], 500);
        }
    }

    public function updateSelf(Request $request, User $user)
    {
        // Vérifie que l'utilisateur authentifié ne modifie que son propre profil
        if ($request->user()->id !== $user->id) {
            abort(403, 'Vous ne pouvez modifier que votre propre profil.');
        }

        // Validation des données - exclure explicitement les champs sensibles
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,'.$user->id,
            'phone' => 'sometimes|nullable|string|max:20',
            'address' => 'sometimes|nullable|array',
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        // Filtrer les champs autorisés pour les utilisateurs normaux
        $allowedFields = ['name', 'email', 'phone', 'address'];
        $updateData = array_intersect_key($validated, array_flip($allowedFields));

        // Si le mot de passe est fourni, le hasher
        if (isset($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        // Mettre à jour l'utilisateur
        $user->update($updateData);

        return response()->json([
            'status' => 'success',
            'message' => 'Profil mis à jour avec succès',
            'data' => [
                'user' => $user->fresh()
            ]
        ]);
    }

    public function update(Request $request, $id)
    {
        $currentUser = auth()->user();
        $isAdmin = $currentUser && $currentUser->role === 'admin';
        
        // Validation différente selon si c'est un admin ou un utilisateur normal
        $validationRules = [
            'name' => 'sometimes|string|max:50',
            'phone' => 'sometimes|nullable|string|max:20',
            'address' => 'sometimes|nullable|array',
        ];

        // Si c'est un admin, permettre la modification du rôle et du statut
        if ($isAdmin) {
            $validationRules['role'] = 'sometimes|in:admin,customer';
            $validationRules['is_active'] = 'sometimes|boolean';
        }

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found',
                ], 404);
            }

            // Check if user can update this profile
            if (auth()->id() !== (int)$id && !$isAdmin) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Access denied',
                ], 403);
            }

            // Champs de base que tout le monde peut modifier
            $allowedFields = ['name', 'phone', 'address'];
        
            // Si c'est un admin, permettre la modification du rôle et du statut
            if ($isAdmin) {
                $allowedFields = array_merge($allowedFields, ['role', 'is_active']);
            }

            $updateData = $request->only($allowedFields);
            $user->update($updateData);

            return response()->json([
                'status' => 'success',
                'message' => 'User updated successfully',
                'data' => [
                    'user' => $user->fresh(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while updating user',
            ], 500);
        }
    }

    public function getProfile()
    {
        try {
            $user = auth()->user();
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'user' => $user,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while fetching profile',
            ], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:50',
            'phone' => 'sometimes|string|max:20',
            'address' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        try {
            $user = auth()->user();
            
            // Seuls les champs autorisés peuvent être modifiés
            $allowedFields = ['name', 'phone', 'address'];
            $updateData = $request->only($allowedFields);
            
            $user->update($updateData);

            return response()->json([
                'status' => 'success',
                'message' => 'Profile updated successfully',
                'data' => [
                    'user' => $user->fresh(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while updating profile',
            ], 500);
        }
    }

    public function getOrders(Request $request)
    {
        try {
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 10);
            $status = $request->get('status');

            $user = auth()->user();
            $query = Order::where('user_id', $user->id)
                         ->with(['items.product:id,title,images']);

            if ($status) {
                $query->where('status', $status);
            }

            $orders = $query->orderBy('created_at', 'desc')
                           ->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'orders' => $orders->items(),
                    'pagination' => [
                        'page' => $orders->currentPage(),
                        'limit' => $orders->perPage(),
                        'total' => $orders->total(),
                        'pages' => $orders->lastPage(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while fetching orders',
            ], 500);
        }
    }

    public function getOrder($id)
    {
        try {
            $user = auth()->user();
            $order = Order::where('user_id', $user->id)
                         ->where('id', $id)
                         ->with(['items.product:id,title,images'])
                         ->first();

            if (!$order) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'order' => $order,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while fetching order',
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found',
                ], 404);
            }

            $user->is_active = $request->is_active;
            $user->save();

            return response()->json([
                'status' => 'success',
                'message' => 'User status updated successfully',
                'data' => [
                    'user' => $user,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while updating user status',
            ], 500);
        }
    }
}
