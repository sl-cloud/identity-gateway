<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use App\Services\JwtService;
use App\Services\TokenRevocationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Passport\Token;

class TokenController extends Controller
{
    public function __construct(
        protected JwtService $jwtService,
        protected TokenRevocationService $revocationService,
        protected AuditService $auditService
    ) {}

    /**
     * List all tokens for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $tokens = Token::where('user_id', $user->id)
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->with('client')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($token) => [
                'id' => $token->id,
                'scopes' => $token->scopes,
                'expires_at' => $token->expires_at,
                'created_at' => $token->created_at,
                'client' => $token->client ? [
                    'id' => $token->client->id,
                    'name' => $token->client->name,
                ] : null,
            ]);

        return Inertia::render('Dashboard/Tokens/Index', [
            'tokens' => $tokens,
        ]);
    }

    /**
     * Inspect a JWT token.
     */
    public function inspect(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $token = $validated['token'];

        // Decode without verification for inspection
        $decoded = $this->jwtService->decode($token);

        if (! $decoded) {
            return response()->json([
                'error' => 'Invalid token format',
                'valid' => false,
            ], 422);
        }

        // Try to verify signature
        try {
            $verifiedPayload = $this->jwtService->verify($token);
            $isValid = true;
            $isExpired = false;
            $isRevoked = false;
            $verificationError = null;
        } catch (\Exception $e) {
            $isValid = false;
            $isExpired = str_contains($e->getMessage(), 'expired');
            $isRevoked = str_contains($e->getMessage(), 'revoked');
            $verificationError = $e->getMessage();
        }

        // Log the inspection
        $jti = $decoded->payload->jti ?? null;
        $this->auditService->logTokenInspected(
            $request->user(),
            $isValid,
            $jti,
            $request
        );

        return response()->json([
            'valid' => $isValid,
            'expired' => $isExpired,
            'revoked' => $isRevoked,
            'error' => $verificationError,
            'header' => $decoded->header,
            'payload' => $decoded->payload,
        ]);
    }

    /**
     * Revoke a token.
     */
    public function destroy(Request $request, string $tokenId)
    {
        $user = $request->user();

        $token = Token::where('id', $tokenId)
            ->where('user_id', $user->id)
            ->where('revoked', false)
            ->firstOrFail();

        // Revoke the token in the database directly since we don't have the JWT string
        $token->revoke();

        // Extract JTI if possible for audit log
        $jti = null;
        if (isset($token->jwt_id)) {
            $jti = $token->jwt_id;
        }

        // Log the revocation
        $this->auditService->logTokenRevoked(
            $user,
            $jti ?? $tokenId,
            $token->client_id,
            $request
        );

        return redirect()
            ->route('dashboard.tokens.index')
            ->with('success', 'Token revoked successfully.');
    }
}
