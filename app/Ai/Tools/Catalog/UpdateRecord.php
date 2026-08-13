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

class UpdateRecord implements Tool
{
    public function __construct(
        public ?User $user = null,
        public ?AiResourceCrudService $crud = null,
    ) {}

    public function description(): Stringable|string
    {
        return 'Update a record by resource key and id. Pass only fields to change inside fields object.';
    }

    public function handle(Request $request): Stringable|string
    {
        $user = $this->user ?? Auth::user();
        if (! $user instanceof User) {
            return 'Error: Not authenticated.';
        }

        $resource = (string) ($request['resource'] ?? '');
        $id = (int) ($request['id'] ?? 0);
        if ($resource === '' || $id < 1) {
            return 'Error: resource and id are required.';
        }

        $fields = $request['fields'] ?? null;
        if (! is_array($fields)) {
            $fields = collect($request->all())
                ->except(['resource', 'id', 'fields'])
                ->all();
        }

        try {
            return ($this->crud ?? app(AiResourceCrudService::class))->updateRecord($user, $resource, $id, $fields);
        } catch (Throwable $e) {
            return 'Error: '.$e->getMessage();
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'resource' => $schema->string()->min(1)->required(),
            'id' => $schema->integer()->min(1)->required(),
            'fields' => $schema->object()->required(),
        ];
    }
}
