<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Api\UserPropertyRequest;
use App\Models\PropertyRequestStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyRequestStatusTest extends TestCase
{
    public function test_update_status_for_all_five_statuses()
    {
        // Get user 1430
        $user = User::find(1430);
        $this->assertNotNull($user, 'User 1430 not found');
        
        // Get property request 151
        $pr = UserPropertyRequest::find(151);
        $this->assertNotNull($pr, 'Property request 151 not found');
        $this->assertEquals(1430, $pr->user_id, 'Property request does not belong to user 1430');
        
        // Store original status
        $originalStatus = $pr->status_id;
        
        // Get all statuses
        $statuses = PropertyRequestStatus::orderBy('id')->get();
        $this->assertCount(5, $statuses, 'Expected 5 statuses');
        
        echo "\n=== Testing All 5 Statuses for Property Request 151 (User 1430) ===\n\n";
        
        $results = [];
        
        foreach ($statuses as $status) {
            echo "Testing Status {$status->id} ({$status->slug} - {$status->name_ar})...\n";
            
            $response = $this->actingAs($user, 'sanctum')
                ->putJson("/api/v1/property-requests/151/status", [
                    'status_id' => $status->id
                ]);
            
            echo "  HTTP Status: {$response->status()}\n";
            
            if ($response->status() === 200) {
                $data = $response->json();
                
                echo "  ✅ Success: {$data['message']}\n";
                
                // Check response structure
                $this->assertEquals('success', $data['status']);
                $this->assertArrayHasKey('data', $data);
                $this->assertArrayHasKey('status', $data['data']);
                
                $responseStatus = $data['data']['status'];
                echo "  Response status: ID={$responseStatus['id']}, name_ar={$responseStatus['name_ar']}\n";
                
                // Verify in database
                $pr->refresh();
                echo "  Database status_id: {$pr->status_id}\n";
                
                $this->assertEquals($status->id, $pr->status_id, "Status not saved correctly");
                $this->assertEquals($status->id, $responseStatus['id'], "Response status ID mismatch");
                
                echo "  ✅ PASS\n";
                $results[$status->id] = 'PASS';
            } else {
                echo "  ❌ FAIL: " . $response->getContent() . "\n";
                $results[$status->id] = "FAIL (HTTP {$response->status()})";
            }
            
            echo "\n";
        }
        
        // Restore original status
        if ($originalStatus) {
            $pr->update(['status_id' => $originalStatus]);
            echo "Restored original status: {$originalStatus}\n\n";
        }
        
        // Summary
        echo "=== SUMMARY ===\n";
        foreach ($results as $statusId => $result) {
            $status = $statuses->firstWhere('id', $statusId);
            $icon = $result === 'PASS' ? '✅' : '❌';
            echo "{$icon} Status {$statusId} ({$status->slug}): {$result}\n";
        }
        
        $passCount = count(array_filter($results, fn($r) => $r === 'PASS'));
        echo "\nResult: {$passCount}/5 tests passed\n";
        
        $this->assertEquals(5, $passCount, "Not all status updates passed");
    }
}
