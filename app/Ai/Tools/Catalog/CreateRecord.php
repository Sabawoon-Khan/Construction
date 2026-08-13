<?php

namespace App\Ai\Tools\Catalog;

use App\Models\User;
use App\Services\Ai\AiResourceCrudService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

class CreateRecord implements Tool
{
    public function __construct(
        public ?User $user = null,
        public ?AiResourceCrudService $crud = null,
    ) {}

    public function description(): Stringable|string
    {
        return 'Create a record in a resource. Pass resource key plus fields object (e.g. field_reports: title/body/location/status; users: name/email/password/roles[]; roles: name/permissions[]; lookup_items: group/name). Returns an Open link when available.';
    }

    public function handle(Request $request): Stringable|string
    {
        $user = $this->user ?? Auth::user();
        if (! $user instanceof User) {
            return 'Error: Not authenticated.';
        }

        $resource = (string) ($request['resource'] ?? '');
        if ($resource === '') {
            return 'Error: resource is required.';
        }

        $fields = $request['fields'] ?? null;
        if (! is_array($fields)) {
            // Allow flat args besides resource
            $fields = collect($request->all())
                ->except(['resource', 'fields'])
                ->all();
        }

        if ($fields === []) {
            return 'Error: fields are required.';
        }

        try {
            return ($this->crud ?? app(AiResourceCrudService::class))->createRecord($user, $resource, $fields);
        } catch (Throwable $e) {
            return 'Error: '.$e->getMessage();
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'resource' => $schema->string()->min(1)->required(),
            'fields' => $schema->object()->required(),
        ];
    }
}
