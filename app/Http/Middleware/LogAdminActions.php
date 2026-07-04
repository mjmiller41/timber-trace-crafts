<?php

namespace App\Http\Middleware;

use App\Models\AdminAuditLog;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LogAdminActions
{
    /**
     * Record state-changing admin requests to the audit log. Read requests
     * (GET/HEAD/OPTIONS) are never recorded. A logging failure must never
     * break the admin action itself, so persistence is best-effort.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (config('admin.audit.enabled') && $this->shouldLog($request)) {
            $this->record($request, $response);
        }

        return $response;
    }

    private function shouldLog(Request $request): bool
    {
        return ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);
    }

    private function record(Request $request, Response $response): void
    {
        try {
            [$subjectType, $subjectId] = $this->resolveSubject($request);
            $user = $request->user();

            AdminAuditLog::create([
                'user_id' => $user?->id,
                'actor_email' => $user?->email,
                'method' => $request->method(),
                'route_name' => $request->route()?->getName(),
                'path' => '/'.ltrim($request->path(), '/'),
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'status_code' => $response->getStatusCode(),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 1000),
                'properties' => $this->safeInput($request),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Admin audit log write failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Identify the primary route subject (an Eloquent model bound to the route,
     * otherwise the first scalar route parameter).
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function resolveSubject(Request $request): array
    {
        foreach ($request->route()?->parameters() ?? [] as $param) {
            if ($param instanceof Model) {
                return [class_basename($param), (string) $param->getKey()];
            }
        }

        foreach ($request->route()?->parameters() ?? [] as $key => $param) {
            if (is_scalar($param)) {
                return [$key, (string) $param];
            }
        }

        return [null, null];
    }

    /**
     * Request payload minus secrets, capped so the audit row stays small.
     *
     * @return array<string, mixed>
     */
    private function safeInput(Request $request): array
    {
        $redact = ['password', 'password_confirmation', 'current_password', 'token', '_token', 'secret', 'code', 'otp', 'g-recaptcha-response'];

        $input = collect($request->except($redact))
            ->map(fn ($value) => is_scalar($value) ? Str::limit((string) $value, 200, '…') : $value)
            ->take(40)
            ->all();

        return $input;
    }
}
