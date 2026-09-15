<?php

namespace App\Services\Assistant;

use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final readonly class AssistantContext
{
    public function __construct(
        public int $userId,
        public string $role,
        public ?string $routeName,
        public string $path,
        public string $panel,
        public ?int $companyId = null,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isActive()) {
            throw new AccessDeniedHttpException('دسترسی به دستیار مجاز نیست.');
        }

        $role = (string) $user->role?->name;
        $supportedRoles = config('assistant.supported_roles', []);

        if (! in_array($role, $supportedRoles, true)) {
            throw new AccessDeniedHttpException('دسترسی به دستیار برای این نقش مجاز نیست.');
        }

        $companyId = null;

        if ($role === 'company') {
            $resolvedCompanyId = $user->company?->getKey() ?? $user->getAttribute('company_id');
            $companyId = $resolvedCompanyId ? (int) $resolvedCompanyId : null;
        }

        return new self(
            userId: (int) $user->getKey(),
            role: $role,
            routeName: $request->route()?->getName(),
            path: $request->path(),
            panel: match ($role) {
                'admin' => 'admin',
                'association' => 'association',
                'company' => 'company',
            },
            companyId: $companyId,
        );
    }
}
