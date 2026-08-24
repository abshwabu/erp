<?php

declare(strict_types=1);

use App\Modules\Core\Models\Tenant;
use App\Modules\Core\Models\User;
use Database\Seeders\TenantRoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

it('handles documents lifecycle: upload, listing, filtering, metadata retrieval, and deletion', function (): void {
    Storage::fake('local');

    $tenant = Tenant::create([
        'name'   => 'Documents Test Tenant',
        'slug'   => 'doctest',
        'status' => 'active',
    ]);

    $data = $tenant->run(function () use ($tenant) {
        (new TenantRoleSeeder())->run();

        $admin = User::create([
            'tenant_id' => $tenant->getKey(),
            'name'      => 'Doc Admin',
            'email'     => 'admin@doc.test',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $admin->assignRole('Admin');

        return ['admin' => $admin];
    });

    $loginResponse = $this->postJson('http://doctest.localhost/api/auth/login', [
        'email'    => 'admin@doc.test',
        'password' => 'password123',
    ]);
    $token = $loginResponse->json('access_token');
    $headers = ['Authorization' => "Bearer {$token}"];

    // 1. Upload a Document
    $file = UploadedFile::fake()->create('Vendor_Contract_2026.pdf', 500, 'application/pdf');

    $uploadResponse = $this->withHeaders($headers)->post('http://doctest.localhost/api/documents', [
        'name'        => 'Master Services Agreement',
        'folder'      => 'contracts',
        'description' => 'Signed vendor contract with supplier',
        'tags'        => ['vendor', 'legal', '2026'],
        'file'        => $file,
    ]);
    $uploadResponse->assertStatus(201);
    $docId = $uploadResponse->json('data.id');
    expect($docId)->not->toBeNull();
    expect($uploadResponse->json('data.file_name'))->toBe('Vendor_Contract_2026.pdf');
    expect($uploadResponse->json('data.folder'))->toBe('contracts');
    expect($uploadResponse->json('data.mime_type'))->toBe('application/pdf');

    // 2. List documents
    $listResponse = $this->withHeaders($headers)->getJson('http://doctest.localhost/api/documents');
    $listResponse->assertStatus(200);
    expect($listResponse->json('data.data'))->toHaveCount(1);

    // 3. Filter by folder
    $filterResponse = $this->withHeaders($headers)->getJson('http://doctest.localhost/api/documents?folder=contracts');
    $filterResponse->assertStatus(200);
    expect($filterResponse->json('data.data'))->toHaveCount(1);

    $emptyFolderResponse = $this->withHeaders($headers)->getJson('http://doctest.localhost/api/documents?folder=invoices');
    $emptyFolderResponse->assertStatus(200);
    expect($emptyFolderResponse->json('data.data'))->toHaveCount(0);

    // 4. Retrieve document details
    $detailResponse = $this->withHeaders($headers)->getJson("http://doctest.localhost/api/documents/{$docId}");
    $detailResponse->assertStatus(200);
    expect($detailResponse->json('data.name'))->toBe('Master Services Agreement');
    expect($detailResponse->json('data.uploader.id'))->toBe($data['admin']->id);

    // 5. Delete document
    $deleteResponse = $this->withHeaders($headers)->deleteJson("http://doctest.localhost/api/documents/{$docId}");
    $deleteResponse->assertStatus(204);

    $listAfterDelete = $this->withHeaders($headers)->getJson('http://doctest.localhost/api/documents');
    $listAfterDelete->assertStatus(200);
    expect($listAfterDelete->json('data.data'))->toHaveCount(0);
});
