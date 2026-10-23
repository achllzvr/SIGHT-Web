<?php

namespace Tests\Feature;

use App\Models\ChildInventory;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChildInventoryTest extends TestCase
{
    private function fixture(): array
    {
        $fixture = $this->createGuardianWithChild();
        Sanctum::actingAs($fixture['childUser']);
        return $fixture;
    }

    private function url(array $fixture, string $suffix = ''): string
    {
        return '/api/mobile/child/' . $fixture['childProfile']->child_id . '/inventory' . $suffix;
    }

    public function test_fetch_returns_default_frog_and_equipped(): void
    {
        $fixture = $this->fixture();
        $this->getJson($this->url($fixture))
            ->assertOk()
            ->assertJsonPath('data.equipped_skin_key', 'skin_frog_green')
            ->assertJsonPath('data.owned_item_keys', ['skin_frog_green']);
    }

    public function test_purchase_bear_and_duplicate_is_idempotent(): void
    {
        $fixture = $this->fixture();
        $this->postJson($this->url($fixture, '/purchase'), ['item_key' => 'skin_bear_brown'])
            ->assertOk()
            ->assertJsonPath('data.owned_item_keys', ['skin_frog_green', 'skin_bear_brown']);

        $this->postJson($this->url($fixture, '/purchase'), ['item_key' => 'skin_bear_brown'])->assertOk();
        $this->assertSame(1, ChildInventory::where('item_key', 'skin_bear_brown')->count());
    }

    public function test_purchase_unknown_item_rejected(): void
    {
        $fixture = $this->fixture();
        $this->postJson($this->url($fixture, '/purchase'), ['item_key' => 'skin_rainbow'])->assertStatus(422);
    }

    public function test_equip_unowned_rejected_and_owned_ok(): void
    {
        $fixture = $this->fixture();
        $this->putJson($this->url($fixture, '/equip'), ['item_key' => 'skin_bear_brown'])->assertStatus(422);

        $this->postJson($this->url($fixture, '/purchase'), ['item_key' => 'skin_bear_brown'])->assertOk();
        $this->putJson($this->url($fixture, '/equip'), ['item_key' => 'skin_bear_brown'])
            ->assertOk()
            ->assertJsonPath('data.equipped_skin_key', 'skin_bear_brown');
        $this->assertSame('skin_bear_brown', $fixture['childProfile']->fresh()->equipped_skin_key);
    }

    public function test_equip_default_frog_always_ok(): void
    {
        $fixture = $this->fixture();
        $this->putJson($this->url($fixture, '/equip'), ['item_key' => 'skin_frog_green'])
            ->assertOk()
            ->assertJsonPath('data.equipped_skin_key', 'skin_frog_green');
    }

    public function test_unlinked_user_forbidden(): void
    {
        $fixture = $this->createGuardianWithChild();
        $other = $this->createGuardianWithChild();
        Sanctum::actingAs($other['childUser']);
        $this->getJson($this->url($fixture))->assertForbidden();
    }

    public function test_linked_guardian_can_fetch(): void
    {
        $fixture = $this->createGuardianWithChild();
        Sanctum::actingAs($fixture['guardianUser']);
        $this->getJson($this->url($fixture))->assertOk();
    }
}
