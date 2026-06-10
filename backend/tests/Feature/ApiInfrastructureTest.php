<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\BaseController;
use App\Models\User;
use App\Traits\QueryFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;
use App\Http\Resources\BaseResource;

// Dummy model and controller for testing
class DummyModel extends Model
{
    use QueryFilter;
    protected $table = 'users'; // Use an existing table
    protected $guarded = [];
}

class DummyResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}

class DummyController extends BaseController
{
    public function index()
    {
        $query = DummyModel::query()->filterByRequest(request());
        return $this->paginatedResponse($query, DummyResource::class);
    }

    public function store()
    {
        return $this->createdResponse(['id' => 1, 'name' => 'Test']);
    }

    public function error()
    {
        return $this->errorResponse('Bad Request', 400);
    }
}

class ApiInfrastructureTest extends TestCase
{
    protected $tenantUrl;

    protected function setUp(): void
    {
        parent::setUp();
        
        $tenant = $this->createTenant(['slug' => 'test-api']);
        $this->tenantUrl = 'http://test-api.localhost';

        Route::get('/api/dummy', [DummyController::class, 'index'])->middleware(['api']);
        Route::post('/api/dummy', [DummyController::class, 'store'])->middleware(['api', 'audit']);
        Route::get('/api/dummy/error', [DummyController::class, 'error'])->middleware(['api']);
        
        Route::get('/api/abort/404', function () {
            abort(404);
        })->middleware(['api']);

        Route::post('/api/validation-error', function (Request $request) {
            $request->validate(['name' => 'required']);
        })->middleware(['api']);
    }

    public function test_force_json_response_middleware()
    {
        $response = $this->get($this->tenantUrl . '/api/abort/404');
        
        $response->assertStatus(404)
                 ->assertHeader('Content-Type', 'application/json');
    }

    public function test_validation_exception_format()
    {
        $response = $this->postJson($this->tenantUrl . '/api/validation-error', []);

        $response->assertStatus(422)
                 ->assertJsonStructure([
                     'errors' => [
                         '*' => ['status', 'source' => ['pointer'], 'title', 'detail']
                     ]
                 ]);
    }

    public function test_query_filter_trait()
    {
        $tenant = \App\Modules\Core\Models\Tenant::where('slug', 'test-api')->first();
        $tenant->run(function () use ($tenant) {
            User::forceCreate([
                'id' => \Illuminate\Support\Str::uuid(),
                'tenant_id' => $tenant->getKey(),
                'name' => 'Alpha',
                'email' => 'alpha@test.com',
                'password' => 'password',
                'is_active' => true,
            ]);
            User::forceCreate([
                'id' => \Illuminate\Support\Str::uuid(),
                'tenant_id' => $tenant->getKey(),
                'name' => 'Beta',
                'email' => 'beta@test.com',
                'password' => 'password',
                'is_active' => true,
            ]);
            User::forceCreate([
                'id' => \Illuminate\Support\Str::uuid(),
                'tenant_id' => $tenant->getKey(),
                'name' => 'Gamma',
                'email' => 'gamma@test.com',
                'password' => 'password',
                'is_active' => true,
            ]);
        });

        // Test sorting
        $response = $this->getJson($this->tenantUrl . '/api/dummy?sort=-name');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals('Gamma', $data[0]['name']);
        
        // Test filtering
        $response = $this->getJson($this->tenantUrl . '/api/dummy?filter[name]=Beta');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Beta', $response->json('data.0.name'));
    }

    public function test_base_controller_responses()
    {
        $response = $this->postJson($this->tenantUrl . '/api/dummy');
        $response->assertStatus(201)
                 ->assertJson(['data' => ['id' => 1, 'name' => 'Test']]);

        $response = $this->getJson($this->tenantUrl . '/api/dummy/error');
        $response->assertStatus(400)
                 ->assertJson(['message' => 'Bad Request']);
    }

    public function test_audit_log_middleware()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->postJson($this->tenantUrl . '/api/dummy', [
            'name' => 'Secret',
            'password' => 'hidden_password'
        ]);

        $response->assertStatus(201);

        $tenant = \App\Modules\Core\Models\Tenant::where('slug', 'test-api')->first();
        $tenant->run(function () {
            $activity = Activity::latest()->first();
            
            $this->assertNotNull($activity);
            $this->assertEquals('api_request', $activity->log_name);
            $this->assertEquals('POST', $activity->getExtraProperty('method'));
            
            $payload = $activity->getExtraProperty('payload');
            $this->assertArrayHasKey('name', $payload);
            $this->assertArrayNotHasKey('password', $payload);
        });
    }
}
