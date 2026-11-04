<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class HandoverTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $name, string $role = 'staff', bool $active = true): User
    {
        return User::create([
            'name' => $name,
            'email' => str_replace(' ', '.', strtolower($name)).uniqid().'@example.com',
            'password' => 'secure-password-1234',
            'role' => $role,
            'is_active' => $active,
        ]);
    }

    private function activity(User $creator, ?int $assignee, string $title, string $date, string $status = 'Pending'): Activity
    {
        return Activity::create([
            'title' => $title,
            'activity_date' => $date,
            'status' => $status,
            'created_by' => $creator->id,
            'assigned_to' => $assignee,
        ]);
    }

    private function update(Activity $activity, User $user, string $status, string $workDone, array $details = []): void
    {
        $activity->updates()->create(array_merge([
            'user_id' => $user->id,
            'personnel_name' => $user->name,
            'personnel_email' => $user->email,
            'personnel_role' => $user->role,
            'status' => $status,
            'remark' => $workDone,
            'work_done' => $workDone,
        ], $details));
        $activity->update(['status' => $status]);
    }

    public function test_daily_handover_includes_date_updates_and_older_pending_carryover(): void
    {
        Carbon::setTestNow('2026-11-04 15:30:00');
        $admin = $this->user('Admin User', 'admin');
        $staff = $this->user('Assigned Staff');
        $pending = $this->activity($admin, $staff->id, 'Payment reconciliation', '2026-11-04');
        $done = $this->activity($admin, null, 'Server availability', '2026-11-04');
        Carbon::setTestNow('2026-11-03 15:00:00');
        $carried = $this->activity($admin, $staff->id, 'Previous day task', '2026-11-03');
        $this->update($carried, $staff, 'Pending', 'Initial review completed.', [
            'outstanding_issues' => 'Need a second validation.',
            'next_steps' => 'Compare with the overnight logs.',
        ]);
        Carbon::setTestNow('2026-11-04 15:30:00');
        $this->activity($admin, null, 'Future task', '2026-11-05');
        $this->update($pending, $staff, 'Pending', 'Reviewed gateway report.', [
            'findings' => 'Totals differ by twelve records.',
            'outstanding_issues' => 'Provider has not supplied the corrected export.',
            'next_steps' => 'Request a fresh report from the provider.',
        ]);
        $this->update($done, $staff, 'Done', 'All application nodes responded normally.');

        $response = $this->actingAs($admin)->get('/handover?date=2026-11-04');
        $response->assertOk()
            ->assertViewHas('activities', fn ($activities) => $activities->count() === 3)
            ->assertViewHas('carriedForwardCount', 1)
        ->assertSee('css/handover.css')
        ->assertSee('Daily Handover')
            ->assertSee('Pending')
            ->assertSee('Assigned Staff')
            ->assertSee('Unassigned team activity')
            ->assertSee('Totals differ by twelve records.')
            ->assertSee('Request a fresh report from the provider.')
            ->assertSee('Carried forward')
            ->assertDontSee('Future task');

        Carbon::setTestNow();
    }

    public function test_handover_filters_by_historical_status_and_personnel(): void
    {
        Carbon::setTestNow('2026-11-04 12:00:00');
        $admin = $this->user('Admin User', 'admin');
        $inactive = $this->user('Former Staff', 'staff', false);
        $other = $this->user('Other Staff');
        $assigned = $this->activity($admin, $inactive->id, 'Former staff backup check', '2026-11-04');
        $otherActivity = $this->activity($admin, $other->id, 'Other staff payment check', '2026-11-04');
        $this->update($assigned, $inactive, 'Pending', 'Waiting for old system credentials.', [
            'outstanding_issues' => 'Credentials have expired.',
            'next_steps' => 'Contact the former system owner.',
        ]);
        $this->update($otherActivity, $other, 'Pending', 'Waiting for payment confirmation.', [
            'outstanding_issues' => 'Payment has not settled.',
            'next_steps' => 'Check the settlement queue.',
        ]);

        $this->actingAs($admin)->get('/handover?date=2026-11-04&status=Pending&personnel='.$inactive->id)
            ->assertOk()
            ->assertSee('Former staff backup check')
            ->assertSee('Waiting for old system credentials.')
            ->assertSee('Former Staff (inactive)')
            ->assertDontSee('Other staff payment check');

        $this->actingAs($admin)->get('/handover?date=2026-11-04&status=Done')
            ->assertOk()
            ->assertSee('No activities for this date');
        Carbon::setTestNow();
    }

    public function test_pending_activity_carries_forward_and_another_staff_member_can_complete_it(): void
    {
        Carbon::setTestNow('2026-11-02 18:20:00');
        $admin = $this->user('Admin User', 'admin');
        $staffA = $this->user('Staff A');
        $staffB = $this->user('Staff B');
        $activity = $this->activity($admin, $staffA->id, 'Gateway reconciliation', '2026-11-02');
        $this->update($activity, $staffA, 'Pending', 'Compared transaction totals.', [
            'findings' => 'A twelve-transaction discrepancy remains.',
            'outstanding_issues' => 'The provider has not confirmed the discrepancy.',
            'next_steps' => 'Contact the provider and re-run the comparison.',
        ]);

        Carbon::setTestNow('2026-11-03 09:00:00');
        $this->actingAs($staffB)->get('/handover?date=2026-11-03')
            ->assertOk()
            ->assertSee('Gateway reconciliation')
            ->assertSee('Carried forward')
            ->assertSee('Staff A')
            ->assertSee('A twelve-transaction discrepancy remains.')
            ->assertSee('Contact the provider and re-run the comparison.');
        $this->actingAs($staffB)->get("/activities/{$activity->id}")
            ->assertOk()
            ->assertSee('Staff A')
            ->assertSee('Add an update');

        Carbon::setTestNow('2026-11-04 10:15:00');
        $this->actingAs($staffB)->post("/activities/{$activity->id}/updates", [
            'status' => 'Done',
            'work_done' => 'Confirmed the discrepancy with the provider and reconciled all transactions.',
            'findings' => 'The provider had delayed two records.',
            'last_seen_update_id' => $activity->updates()->max('id'),
        ])->assertRedirect();

        $this->assertSame(1, Activity::count());
        $this->assertSame(2, $activity->updates()->count());
        $this->assertSame('Done', $activity->fresh()->status);
        $this->actingAs($staffB)->get('/handover?date=2026-11-02')->assertOk()->assertSee('Pending')->assertSee('Staff A');
        $this->actingAs($staffB)->get('/handover?date=2026-11-03')->assertOk()->assertSee('Pending')->assertSee('Carried forward');
        $this->actingAs($staffB)->get('/handover?date=2026-11-04')->assertOk()->assertSee('Done')->assertSee('Staff B')->assertSee('Confirmed the discrepancy with the provider');
        $this->actingAs($staffB)->get('/handover?date=2026-11-05&status=Pending')->assertOk()->assertSee('No activities for this date');
        $this->actingAs($admin)->get('/reports?end_date=2026-11-03&status=Pending')->assertOk()->assertSee('Gateway reconciliation');
        $this->actingAs($admin)->get('/reports?end_date=2026-11-04&status=Done')->assertOk()->assertSee('Gateway reconciliation');

        Carbon::setTestNow();
    }

    public function test_activities_without_updates_are_awaiting_and_invalid_filters_are_rejected(): void
    {
        Carbon::setTestNow('2026-11-04 12:00:00');
        $admin = $this->user('Admin User', 'admin');
        $this->activity($admin, null, 'Awaiting first update', '2026-11-04');

        $this->actingAs($admin)->get('/handover?date=2026-11-04&status=Awaiting%20Update')
            ->assertOk()
            ->assertSee('Awaiting first update')
            ->assertSee('Awaiting Update')
            ->assertSee('Awaiting first update');
        $this->actingAs($admin)->get('/handover?date=not-a-date')->assertRedirect()->assertSessionHasErrors('date');
        $this->actingAs($admin)->get('/handover?date=2026-11-04&status=Archived')->assertRedirect()->assertSessionHasErrors('status');
        Carbon::setTestNow();
    }

    public function test_handover_respects_when_the_activity_record_was_created(): void
    {
        Carbon::setTestNow('2026-11-04 10:00:00');
        $admin = $this->user('Admin User', 'admin');
        $activity = $this->activity($admin, null, 'Backdated activity entered today', '2026-11-02');

        $this->actingAs($admin)->get('/handover?date=2026-11-03')->assertOk()->assertDontSee('Backdated activity entered today');
        $this->actingAs($admin)->get('/handover?date=2026-11-04')->assertOk()->assertSee('Backdated activity entered today')->assertSee('New activity');
        $this->assertSame(1, Activity::count());
        Carbon::setTestNow();
    }

    public function test_daily_status_uses_the_configured_date_boundary(): void
    {
        Carbon::setTestNow('2026-11-02 12:00:00');
        $admin = $this->user('Admin User', 'admin');
        $staff = $this->user('Assigned Staff');
        $activity = $this->activity($admin, $staff->id, 'Midnight status change', '2026-11-02');

        Carbon::setTestNow('2026-11-02 23:59:59');
        $this->update($activity, $staff, 'Pending', 'The check is still in progress.', [
            'outstanding_issues' => 'The final response is not available.',
            'next_steps' => 'Review the response when it arrives.',
        ]);
        Carbon::setTestNow('2026-11-03 00:00:01');
        $this->update($activity, $staff, 'Done', 'The final response was reviewed.');

        $this->actingAs($admin)->get('/handover?date=2026-11-02')->assertOk()->assertSee('Pending')->assertSee('The check is still in progress.')->assertDontSee('The final response was reviewed.');
        $this->actingAs($admin)->get('/handover?date=2026-11-03')->assertOk()->assertSee('Done')->assertSee('The final response was reviewed.');
        Carbon::setTestNow();
    }
}
