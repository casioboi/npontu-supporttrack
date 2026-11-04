<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StaffModuleTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $name, string $role = 'staff'): User
    {
        return User::create(['name' => $name, 'email' => str_replace(' ', '.', strtolower($name)).uniqid().'@example.com', 'password' => 'old-password-1234', 'role' => $role, 'is_active' => true]);
    }

    private function activity(User $creator, ?int $assignee, string $title, string $date, string $status = 'Pending'): Activity
    {
        return Activity::create(['title' => $title, 'activity_date' => $date, 'status' => $status, 'created_by' => $creator->id, 'assigned_to' => $assignee]);
    }

    public function test_guest_cannot_access_staff_workspace_pages(): void
    {
        $this->get('/my-activities')->assertRedirect('/login');
        $this->get('/activity-history')->assertRedirect('/login');
        $this->get('/profile')->assertRedirect('/login');
    }

    public function test_workspace_navigation_collapses_and_feedback_uses_dismissible_toasts(): void
    {
        $staff = $this->user('Staff One');

        $this->actingAs($staff)->withSession(['success' => 'Update recorded.'])->get('/')
            ->assertOk()
            ->assertSee('data-nav-toggle', false)
            ->assertSee('aria-expanded="true"', false)
            ->assertSee('toast-container', false)
            ->assertSee('data-toast-dismiss', false)
            ->assertSee('role="status"', false);

        $this->actingAs($staff)->from('/')->put('/profile/password', ['current_password' => 'wrong', 'password' => 'new-password-1234', 'password_confirmation' => 'new-password-1234'])->assertSessionHasErrors('current_password');
        $this->get('/')
            ->assertOk()
            ->assertSee('toast-container', false)
            ->assertSee('role="alert"', false)
            ->assertSee('Check the details');
    }

    public function test_staff_dashboard_is_personalized_and_counts_only_assigned_activities(): void
    {
        Carbon::setTestNow('2026-11-04 10:00:00');
        $staff = $this->user('Staff One');
        $other = $this->user('Staff Two');
        $this->activity($staff, $staff->id, 'Assigned pending', '2026-11-04');
        $done = $this->activity($staff, $staff->id, 'Done today', '2026-11-04', 'Done');
        $done->updates()->create(['user_id' => $staff->id, 'personnel_name' => $staff->name, 'personnel_email' => $staff->email, 'personnel_role' => $staff->role, 'status' => 'Done', 'remark' => 'Completed today.']);
        $this->activity($other, $other->id, 'Other person task', '2026-11-04');
        $this->activity($staff, $staff->id, 'Overdue task', '2026-11-03');

        $this->actingAs($staff)->get('/')->assertOk()->assertViewHas('totalAssigned', 3)->assertViewHas('pendingAssigned', 2)->assertViewHas('doneToday', 1)->assertViewHas('overdue', 1)->assertSee('Staff One')->assertSee('View today’s activities')->assertSee('Assigned pending')->assertDontSee('Other person task');
        Carbon::setTestNow();
    }

    public function test_staff_activity_page_only_shows_activities_assigned_to_them_and_cannot_create(): void
    {
        Carbon::setTestNow('2026-11-04 10:00:00');
        $staff = $this->user('Staff One');
        $other = $this->user('Staff Two');
        $this->activity($staff, $staff->id, 'My assigned task', '2026-11-04');
        $this->activity($other, null, 'Unassigned task', '2026-11-04');
        $this->activity($other, $other->id, 'Private other task', '2026-11-04');
        $this->activity($other, null, 'Old unassigned task', '2026-11-03');

        $this->actingAs($staff)->get('/my-activities?date=2026-11-04')->assertOk()->assertSee('My assigned task')->assertDontSee('Unassigned task')->assertDontSee('Private other task')->assertDontSee('Old unassigned task')->assertDontSee('New activity')->assertDontSee('create the first activity');
        $this->actingAs($staff)->get('/activities/create')->assertForbidden();
        $this->actingAs($staff)->post('/activities', ['title' => 'Staff-created activity', 'activity_date' => '2026-11-04'])->assertForbidden();
        $this->assertDatabaseMissing('activities', ['title' => 'Staff-created activity']);
        Carbon::setTestNow();
    }

    public function test_staff_can_review_and_continue_pending_work_assigned_to_another_person(): void
    {
        $staff = $this->user('Staff One');
        $other = $this->user('Staff Two');
        $activity = $this->activity($other, $other->id, 'Restricted task', '2026-11-04');

        $this->actingAs($staff)->get("/activities/{$activity->id}")->assertOk()->assertSee('Restricted task');
        $this->actingAs($staff)->post("/activities/{$activity->id}/updates", [
            'status' => 'Pending',
            'work_done' => 'Reviewed the activity handover.',
            'outstanding_issues' => 'The assigned colleague has not resolved the issue.',
            'next_steps' => 'Continue investigating the pending work.',
        ])->assertRedirect();
        $this->assertDatabaseHas('activity_updates', ['activity_id' => $activity->id, 'user_id' => $staff->id]);
    }

    public function test_staff_handover_filters_status_and_personnel_inside_authorized_scope(): void
    {
        $staff = $this->user('Staff One');
        $other = $this->user('Staff Two');
        $this->activity($other, null, 'Unassigned pending', '2026-11-04');
        $pending = $this->activity($other, $staff->id, 'My pending', '2026-11-04');
        $this->activity($staff, $staff->id, 'My completed', '2026-11-04', 'Done');
        $this->activity($other, $other->id, 'Other assigned', '2026-11-04');
        $pending->updates()->create(['user_id' => $staff->id, 'personnel_name' => $staff->name, 'personnel_email' => $staff->email, 'personnel_role' => $staff->role, 'status' => 'Pending', 'remark' => 'Waiting on payment gateway logs.']);

        $this->actingAs($staff)->get('/handover?date=2026-11-04&status=Pending&personnel='.$staff->id)->assertOk()->assertSee('My pending')->assertSee('Waiting on payment gateway logs.')->assertDontSee('Unassigned pending')->assertDontSee('My completed')->assertDontSee('Other assigned');
    }

    public function test_staff_dashboard_stays_scoped_while_handover_includes_awaiting_team_work(): void
    {
        $staff = $this->user('Staff One');
        $other = $this->user('Staff Two');
        $assigned = $this->activity($staff, $staff->id, 'Assigned without update', '2026-11-04');
        $this->activity($other, $other->id, 'Other staff only', '2026-11-04');
        $this->activity($other, null, 'Unassigned team task', '2026-11-04');

        $this->actingAs($staff)->get('/')->assertOk()->assertSee('Assigned without update')->assertDontSee('Other staff only')->assertDontSee('Unassigned team task');
        $this->actingAs($staff)->get('/handover?date=2026-11-04')->assertOk()->assertSee('Assigned without update')->assertSee('Other staff only')->assertSee('Unassigned team task')->assertSee('Awaiting Update');
    }

    public function test_activity_history_and_reports_are_scoped_and_filterable_for_staff(): void
    {
        $staff = $this->user('Staff One');
        $other = $this->user('Staff Two');
        $mine = $this->activity($other, $staff->id, 'Assigned history', '2026-11-04', 'Done');
        $hidden = $this->activity($other, $other->id, 'Hidden history', '2026-11-04', 'Done');
        $mine->updates()->create(['user_id' => $staff->id, 'personnel_name' => $staff->name, 'personnel_email' => $staff->email, 'personnel_role' => $staff->role, 'status' => 'Done', 'remark' => 'Verified transaction batch.']);
        $hidden->updates()->create(['user_id' => $other->id, 'personnel_name' => $other->name, 'personnel_email' => $other->email, 'personnel_role' => $other->role, 'status' => 'Done', 'remark' => 'Private note.']);

        $query = '?start_date=2026-11-04&end_date=2026-11-04&status=Done';
        $this->actingAs($staff)->get('/activity-history'.$query)->assertOk()->assertSee('Verified transaction batch.')->assertDontSee('Private note.');
        $this->actingAs($staff)->get('/reports'.$query)->assertOk()->assertSee('Assigned history')->assertDontSee('Hidden history');
        $this->actingAs($staff)->get('/reports/export'.$query)->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->actingAs($staff)->get('/activity-history')->assertOk()->assertDontSee('Hidden history');
        $this->actingAs($staff)->get('/reports')->assertOk()->assertDontSee('Hidden history');
    }

    public function test_staff_reports_only_include_updates_the_staff_member_submitted(): void
    {
        $staff = $this->user('Staff One');
        $other = $this->user('Staff Two');
        $assigned = $this->activity($other, $staff->id, 'Assigned report task', '2026-11-04');
        $assigned->updates()->create(['user_id' => $staff->id, 'personnel_name' => $staff->name, 'personnel_email' => $staff->email, 'personnel_role' => $staff->role, 'status' => 'Pending', 'remark' => 'My private update.']);
        $assigned->updates()->create(['user_id' => $other->id, 'personnel_name' => $other->name, 'personnel_email' => $other->email, 'personnel_role' => $other->role, 'status' => 'Done', 'remark' => 'Colleague private update.']);

        $this->actingAs($staff)->get('/reports?status=Done')->assertOk()->assertViewHas('total', 1);
        $this->actingAs($staff)->get('/reports')->assertOk()->assertSee('My private update.')->assertDontSee('Colleague private update.');
        $this->actingAs($staff)->get('/reports/export')->assertOk()->assertDontSee('Colleague private update.');
        $this->actingAs($staff)->get('/activity-history')->assertOk()->assertSee('My private update.')->assertDontSee('Colleague private update.');
    }

    public function test_staff_profile_can_change_password_without_changing_role(): void
    {
        $staff = $this->user('Staff One');
        $this->actingAs($staff)->get('/profile')->assertOk()->assertSee('Staff One')->assertSee('Change password');
        $this->actingAs($staff)->put('/profile/password', ['current_password' => 'old-password-1234', 'password' => 'new-password-12345', 'password_confirmation' => 'new-password-12345', 'role' => 'admin'])->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $staff->id, 'role' => 'staff']);
        $this->assertTrue(password_verify('new-password-12345', $staff->fresh()->password));
        $this->actingAs($staff)->put('/profile/password', ['current_password' => 'wrong-password', 'password' => 'another-password-123', 'password_confirmation' => 'another-password-123'])->assertSessionHasErrors('current_password');
    }

    public function test_stale_activity_form_warns_after_another_person_updates_it(): void
    {
        $staff = $this->user('Staff One');
        $other = $this->user('Staff Two');
        $activity = $this->activity($staff, $staff->id, 'Shared task', '2026-11-04');
        $this->actingAs($staff)->get("/activities/{$activity->id}")->assertOk()->assertDontSee('Another staff member has updated this activity');
        $activity->updates()->create(['user_id' => $other->id, 'personnel_name' => $other->name, 'personnel_email' => $other->email, 'personnel_role' => $other->role, 'status' => 'Pending', 'remark' => 'New finding from colleague.']);
        $this->actingAs($staff)->get("/activities/{$activity->id}")->assertOk()->assertSee('Another staff member has updated this activity')->assertSee('New finding from colleague.');
        $this->actingAs($staff)->post("/activities/{$activity->id}/updates", [
            'status' => 'Done',
            'work_done' => 'Stale form submission.',
            'last_seen_update_id' => 0,
        ])->assertSessionHasErrors('remark');
        $this->assertDatabaseCount('activity_updates', 1);
    }

    public function test_administrator_dashboard_and_activity_access_remain_unrestricted(): void
    {
        $admin = $this->user('Admin User', 'admin');
        $other = $this->user('Staff Two');
        $activity = $this->activity($other, $other->id, 'Staff assigned task', '2026-11-04');
        $this->actingAs($admin)->get('/')->assertOk()->assertSee('Personnel overview');
        $this->actingAs($admin)->get("/activities/{$activity->id}")->assertOk()->assertDontSee('Add an update')->assertSee('Activity history is read-only for administrators.');
        $this->actingAs($admin)->post("/activities/{$activity->id}/updates", ['status' => 'Done', 'remark' => 'Administrator update'])->assertForbidden();
        $this->assertDatabaseCount('activity_updates', 0);
        $this->actingAs($admin)->get('/activities/create')->assertOk();
    }
}
