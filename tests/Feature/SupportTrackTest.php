<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityUpdate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SupportTrackTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'staff'): User
    {
        return User::create(['name' => 'Shadrack Anim Amoafo', 'email' => uniqid().'@example.com', 'password' => 'correct-horse-battery', 'role' => $role, 'is_active' => true]);
    }

    public function test_guests_are_redirected_and_users_can_sign_in(): void
    {
        $this->get('/')->assertRedirect('/login');
        $user = $this->user();
        $this->post('/login', ['email' => $user->email, 'password' => 'correct-horse-battery'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_dashboard_shows_database_backed_operational_sections(): void
    {
        $user = $this->user('admin');
        Activity::create(['title' => 'SMS reconciliation', 'activity_date' => now()->toDateString(), 'status' => 'Pending', 'created_by' => $user->id]);
        $this->actingAs($user)->get('/')->assertOk()->assertSee('Total activities')->assertSee('SMS reconciliation')->assertSee('Personnel overview')->assertSee('Pending handover');
    }

    public function test_activity_creation_page_has_guided_operational_context(): void
    {
        $this->actingAs($this->user('admin'))->get('/activities/create')->assertOk()->assertSee('Create an activity')->assertSee('Assigned personnel')->assertSee('Starts as Pending')->assertSee('Ready for handover')->assertSee('10,000');
    }

    public function test_activity_defaults_to_pending_and_updates_keep_authenticated_identity_and_history(): void
    {
        $user = $this->user();
        $admin = $this->user('admin');
        $this->actingAs($admin)->post('/activities', ['title' => 'Compare SMS totals', 'activity_date' => '2026-11-04', 'assigned_to' => $user->id])->assertRedirect();
        $activity = Activity::firstOrFail();
        $this->assertSame('Pending', $activity->status);
        $this->actingAs($user)->post("/activities/{$activity->id}/updates", ['status' => 'Done', 'work_done' => 'Compared against gateway logs.', 'findings' => 'Log reference SMS-104'])->assertRedirect();
        $this->actingAs($user)->post("/activities/{$activity->id}/updates", ['status' => 'Pending', 'work_done' => 'Investigating a 12 message discrepancy.', 'outstanding_issues' => 'A 12 message discrepancy remains.', 'next_steps' => 'Compare the latest gateway report.', 'last_seen_update_id' => 1])->assertRedirect();
        $activity->refresh();
        $this->assertSame('Pending', $activity->status);
        $this->assertSame(2, $activity->updates()->count());
        $this->assertSame('Compared against gateway logs.', $activity->updates()->first()->work_done);
        $this->assertSame('Log reference SMS-104', $activity->updates()->first()->findings);
        $this->assertSame($user->id, $activity->updates()->first()->user_id);
        $this->assertSame($user->name, $activity->updates()->first()->personnel_name);
        $this->assertNotNull($activity->updates()->first()->created_at);
    }

    public function test_pending_update_requires_outstanding_issues_and_recommended_next_steps(): void
    {
        $user = $this->user();
        $activity = Activity::create(['title' => 'Review payment batch', 'activity_date' => now()->toDateString(), 'status' => 'Pending', 'created_by' => $this->user('admin')->id, 'assigned_to' => $user->id]);

        $this->actingAs($user)->post("/activities/{$activity->id}/updates", [
            'status' => 'Pending',
            'work_done' => 'Compared current gateway totals.',
            'next_steps' => 'Request a corrected report.',
        ])->assertSessionHasErrors('outstanding_issues');
        $this->assertDatabaseCount('activity_updates', 0);

        $this->actingAs($user)->post("/activities/{$activity->id}/updates", [
            'status' => 'Pending',
            'work_done' => 'Compared current gateway totals.',
            'outstanding_issues' => 'Gateway totals still differ.',
        ])->assertSessionHasErrors('next_steps');
        $this->assertDatabaseCount('activity_updates', 0);

        $this->actingAs($user)->post("/activities/{$activity->id}/updates", [
            'status' => 'Pending',
            'work_done' => 'Compared current gateway totals.',
            'outstanding_issues' => '   ',
            'next_steps' => 'Request a corrected report.',
        ])->assertSessionHasErrors('outstanding_issues');
        $this->assertDatabaseCount('activity_updates', 0);
    }

    public function test_done_update_allows_empty_outstanding_fields_and_optional_findings(): void
    {
        $user = $this->user();
        $activity = Activity::create(['title' => 'Review payment batch', 'activity_date' => now()->toDateString(), 'status' => 'Pending', 'created_by' => $this->user('admin')->id, 'assigned_to' => $user->id]);

        $this->actingAs($user)->post("/activities/{$activity->id}/updates", [
            'status' => 'Done',
            'work_done' => 'Reconciled the payment batch.',
            'findings' => '',
            'outstanding_issues' => '',
            'next_steps' => '',
        ])->assertRedirect();

        $update = $activity->updates()->firstOrFail();
        $this->assertSame('Reconciled the payment batch.', $update->work_done);
        $this->assertNull($update->findings);
        $this->assertNull($update->outstanding_issues);
        $this->assertNull($update->next_steps);
    }

    public function test_work_done_is_required_and_update_identity_and_time_are_server_generated(): void
    {
        Carbon::setTestNow('2026-11-04 10:15:30');
        $user = $this->user();
        $admin = $this->user('admin');
        $activity = Activity::create(['title' => 'Review payment batch', 'activity_date' => '2026-11-04', 'status' => 'Pending', 'created_by' => $admin->id, 'assigned_to' => $user->id]);

        $this->actingAs($user)->post("/activities/{$activity->id}/updates", [
            'status' => 'Done',
            'findings' => 'No discrepancies found.',
        ])->assertSessionHasErrors('work_done');
        $this->assertDatabaseCount('activity_updates', 0);

        $this->actingAs($user)->post("/activities/{$activity->id}/updates", [
            'status' => 'Pending',
            'work_done' => 'Validated the payment batch.',
            'findings' => 'Three records require confirmation.',
            'outstanding_issues' => 'Confirmation is still pending.',
            'next_steps' => 'Check the processor response.',
            'user_id' => $admin->id,
            'personnel_name' => 'Forged Operator',
            'personnel_email' => 'forged@example.com',
            'personnel_role' => 'admin',
            'created_at' => '2000-01-01 00:00:00',
        ])->assertRedirect();

        $update = $activity->updates()->firstOrFail();
        $this->assertSame($user->id, $update->user_id);
        $this->assertSame($user->name, $update->personnel_name);
        $this->assertSame($user->email, $update->personnel_email);
        $this->assertSame($user->role, $update->personnel_role);
        $this->assertSame('2026-11-04 10:15:30', $update->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('Validated the payment batch.', $update->work_done);
        Carbon::setTestNow();
    }

    public function test_authorized_staff_can_reopen_completed_activity_with_a_new_pending_update(): void
    {
        $user = $this->user();
        $activity = Activity::create(['title' => 'Review payment batch', 'activity_date' => now()->toDateString(), 'status' => 'Pending', 'created_by' => $this->user('admin')->id, 'assigned_to' => $user->id]);

        $this->actingAs($user)->post("/activities/{$activity->id}/updates", [
            'status' => 'Done',
            'work_done' => 'Completed the reconciliation.',
        ])->assertRedirect();
        $doneUpdateId = $activity->updates()->max('id');

        $this->actingAs($user)->post("/activities/{$activity->id}/updates", [
            'status' => 'Pending',
            'work_done' => 'Reopened after a new discrepancy was reported.',
            'outstanding_issues' => 'The new variance needs investigation.',
            'next_steps' => 'Compare the latest processor file.',
            'last_seen_update_id' => $doneUpdateId,
        ])->assertRedirect();

        $this->assertSame('Pending', $activity->fresh()->status);
        $this->assertSame(2, $activity->updates()->count());
    }

    public function test_activity_detail_surfaces_latest_remark_and_guided_update_form(): void
    {
        $user = $this->user();
        $activity = Activity::create(['title' => 'SMS reconciliation', 'activity_date' => now()->toDateString(), 'status' => 'Pending', 'created_by' => $this->user('admin')->id, 'assigned_to' => $user->id]);
        $activity->updates()->create(['user_id' => $user->id, 'personnel_name' => $user->name, 'personnel_email' => $user->email, 'personnel_role' => $user->role, 'status' => 'Pending', 'remark' => 'Waiting for gateway confirmation.', 'supporting_details' => 'Ticket INF-204']);
        $this->actingAs($user)->get("/activities/{$activity->id}")->assertOk()->assertSee('Waiting for gateway confirmation.')->assertSee('Ticket INF-204')->assertSee('LATEST UPDATE')->assertSee('Activity name')->assertSee('Findings')->assertSee('Activity status')->assertSee('outstanding_issues')->assertSee('data-required-when-pending required aria-required="true"', false)->assertSee('5000');
    }

    public function test_handover_shows_only_selected_day_with_pending_update_context(): void
    {
        $user = $this->user();
        $admin = $this->user('admin');
        $a = Activity::create(['title' => 'Today check', 'activity_date' => '2026-11-04', 'status' => 'Pending', 'created_by' => $admin->id, 'assigned_to' => $user->id]);
        Activity::create(['title' => 'Yesterday check', 'activity_date' => '2026-11-03', 'status' => 'Done', 'created_by' => $admin->id, 'assigned_to' => $user->id]);
        $a->updates()->create(['user_id' => $user->id, 'personnel_name' => $user->name, 'personnel_email' => $user->email, 'personnel_role' => $user->role, 'status' => 'Pending', 'remark' => 'Follow up with gateway team.']);
        $this->actingAs($user)->get('/handover?date=2026-11-04')->assertOk()->assertSee('Today check')->assertSee('Follow up with gateway team.')->assertSee('Yesterday check')->assertSee('Awaiting Update');
    }

    public function test_report_filters_by_inclusive_dates_personnel_and_status(): void
    {
        $user = $this->user();
        $admin = $this->user('admin');
        $included = Activity::create(['title' => 'Included', 'activity_date' => '2026-11-04', 'status' => 'Done', 'created_by' => $admin->id, 'assigned_to' => $user->id]);
        Activity::create(['title' => 'Excluded', 'activity_date' => '2026-11-05', 'status' => 'Pending', 'created_by' => $admin->id, 'assigned_to' => $user->id]);
        $included->updates()->create(['user_id' => $user->id, 'personnel_name' => $user->name, 'personnel_email' => $user->email, 'personnel_role' => $user->role, 'status' => 'Done', 'remark' => 'Complete']);
        $this->actingAs($user)->get('/reports?start_date=2026-11-04&end_date=2026-11-04&personnel='.$user->id.'&status=Done')->assertOk()->assertSee('Included')->assertSee('Nov 4, 2026')->assertDontSee('Nov 5, 2026');
        $this->actingAs($user)->get('/reports/export?start_date=2026-11-04&end_date=2026-11-04&status=Done')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_staff_cannot_access_personnel_management(): void
    {
        $this->actingAs($this->user())->get('/personnel')->assertForbidden();
    }

    public function test_admin_can_manage_personnel(): void
    {
        $admin = $this->user('admin');
        $this->user();
        $this->actingAs($admin)->get('/personnel')->assertOk()->assertSee('Add a teammate')->assertSee('Create personnel account')->assertSee('Team directory')->assertSee('Edit details')->assertSee('Reset password')->assertSee('Deactivate account');
    }

    public function test_admin_can_edit_personnel_but_cannot_demote_themselves(): void
    {
        $admin = $this->user('admin');
        $staff = $this->user();
        $this->actingAs($admin)->put("/personnel/{$staff->id}", ['name' => 'Updated Staff', 'email' => $staff->email, 'role' => 'admin'])->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $staff->id, 'name' => 'Updated Staff', 'role' => 'admin']);
        $this->actingAs($admin)->put("/personnel/{$admin->id}", ['name' => $admin->name, 'email' => $admin->email, 'role' => 'staff'])->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'admin']);
    }

    public function test_last_active_administrator_cannot_be_deactivated(): void
    {
        $admin = $this->user('admin');
        $this->actingAs($admin)->patch("/personnel/{$admin->id}/toggle")->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'is_active' => true]);
    }
}
