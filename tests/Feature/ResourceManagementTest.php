<?php

use App\Models\Resource;
use App\Models\School;
use App\Models\User;

function adminUser(): User
{
    return User::factory()->admin()->create(['email_verified_at' => now()]);
}

function studentUser(): User
{
    return User::factory()->create(['email_verified_at' => now(), 'user_type' => 'user']);
}

it('forbids non-admins from the resource admin area', function () {
    $student = studentUser();

    $this->actingAs($student)->get(route('admin.resources.index'))->assertForbidden();
    $this->actingAs($student)->post(route('admin.resources.store'), [])->assertForbidden();
});

it('lets an admin view the resource center', function () {
    $admin = adminUser();

    Resource::factory()->create(['title' => 'UPCAT (University of the Philippines)']);

    $this->actingAs($admin)->get(route('admin.resources.index'))
        ->assertOk()
        ->assertSee('Resource Center')
        ->assertSee('UPCAT');
});

it('lets an admin create a scholarship with dates and schools', function () {
    $admin = adminUser();
    $school = School::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.resources.store'), [
        'type' => Resource::TYPE_SCHOLARSHIP,
        'title' => 'DOST-SEI Undergraduate S&T Scholarship',
        'grantor' => 'DOST',
        'coverage' => 'Full tuition + stipend',
        'criteria' => 'Top 5% of class',
        'opens_at' => '2026-01-01',
        'closes_at' => '2026-03-31',
        'is_published' => '1',
        'sort_order' => 1,
        'school_ids' => [$school->id],
    ]);

    $response->assertRedirect(route('admin.resources.index', ['type' => Resource::TYPE_SCHOLARSHIP]));

    $resource = Resource::query()->where('title', 'DOST-SEI Undergraduate S&T Scholarship')->first();

    expect($resource)->not->toBeNull()
        ->and($resource->type)->toBe(Resource::TYPE_SCHOLARSHIP)
        ->and($resource->is_published)->toBeTrue()
        ->and($resource->opens_at->format('Y-m-d'))->toBe('2026-01-01')
        ->and($resource->closes_at->format('Y-m-d'))->toBe('2026-03-31')
        ->and($resource->schools->pluck('id')->all())->toBe([$school->id]);
});

it('validates required fields when creating a resource', function () {
    $admin = adminUser();

    $this->actingAs($admin)->post(route('admin.resources.store'), [
        'type' => Resource::TYPE_CET,
        'title' => '',
    ])->assertSessionHasErrors('title');
});

it('lets an admin update a resource and its schools', function () {
    $admin = adminUser();
    $resource = Resource::factory()->scholarship()->create(['title' => 'Old Title']);
    $school = School::factory()->create();

    $this->actingAs($admin)->put(route('admin.resources.update', $resource), [
        'type' => Resource::TYPE_SCHOLARSHIP,
        'title' => 'New Title',
        'grantor' => 'CHED',
        'is_published' => '0',
        'school_ids' => [$school->id],
    ])->assertRedirect(route('admin.resources.index', ['type' => Resource::TYPE_SCHOLARSHIP]));

    $resource->refresh();

    expect($resource->title)->toBe('New Title')
        ->and($resource->is_published)->toBeFalse()
        ->and($resource->schools->pluck('id')->all())->toBe([$school->id]);
});

it('lets an admin delete a resource', function () {
    $admin = adminUser();
    $resource = Resource::factory()->create();

    $this->actingAs($admin)->delete(route('admin.resources.destroy', $resource))
        ->assertRedirect();

    expect(Resource::query()->find($resource->id))->toBeNull();
});

it('shows only published resources on the public resource center', function () {
    Resource::factory()->create(['title' => 'Published CET', 'is_published' => true]);
    Resource::factory()->create(['title' => 'Draft CET', 'is_published' => false]);

    $this->get(route('resources.index'))
        ->assertOk()
        ->assertSee('Published CET')
        ->assertDontSee('Draft CET');
});

it('renders resource pages with a valid alpine payload (no uncompiled blade braces)', function () {
    $admin = adminUser();
    Resource::factory()->scholarship()->create(['title' => 'Payload Scholarship']);

    $adminPage = $this->actingAs($admin)->get(route('admin.resources.index'))->assertOk()->getContent();
    expect($adminPage)->toContain('resourceManager(')
        ->and($adminPage)->not->toContain('{{');

    $publicPage = $this->get(route('resources.index'))->assertOk()->getContent();
    expect($publicPage)->toContain('resource-detail')
        ->and($publicPage)->not->toContain('{{');
});
