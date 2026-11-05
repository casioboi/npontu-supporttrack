<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\SupportIssueReport;
use App\Models\User;
use App\Notifications\PasswordAccessNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Tests\TestCase;

class ProductionFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_reverse_proxy_https_headers_generate_secure_application_urls(): void
    {
        Route::get('/_test/proxy-context', fn (Request $request) => $request->getScheme().'://'.$request->getHost());

        $this->withServerVariables([
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_FORWARDED_HOST' => 'attacker.example',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ])->get('/_test/proxy-context')->assertOk()->assertSeeText('https://localhost');
    }

    private function user(string $role = 'staff', array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => ucfirst($role).' User',
            'email' => uniqid().'-'.$role.'@example.com',
            'password' => 'correct-password-1234',
            'role' => $role,
            'is_active' => true,
        ], $attributes));
    }

    public function test_administrator_can_assign_multiple_staff_and_each_assignee_can_access_the_activity(): void
    {
        $admin = $this->user('admin');
        $staffA = $this->user();
        $staffB = $this->user();
        $outsider = $this->user();

        $this->actingAs($admin)->post('/activities', [
            'title' => 'Joint incident review',
            'activity_date' => now()->toDateString(),
            'assigned_to' => [$staffA->id, $staffB->id],
        ])->assertRedirect();

        $activity = Activity::where('title', 'Joint incident review')->firstOrFail();
        $this->assertSame($staffA->id, $activity->assigned_to);
        $this->assertEqualsCanonicalizing([$staffA->id, $staffB->id], $activity->assignees()->pluck('users.id')->all());
        $this->actingAs($staffA)->get('/my-activities')->assertOk()->assertSee('Joint incident review');
        $this->actingAs($staffB)->get('/my-activities')->assertOk()->assertSee('Joint incident review');
        $this->actingAs($outsider)->get('/my-activities')->assertOk()->assertDontSee('Joint incident review');
        $this->actingAs($staffB)->get('/activities/'.$activity->id)->assertOk();

        $this->actingAs($admin)->put('/activities/'.$activity->id, [
            'title' => $activity->title,
            'activity_date' => $activity->activity_date->toDateString(),
            'assigned_to' => [$staffB->id],
        ])->assertRedirect();
        $this->assertSame([$staffB->id], $activity->fresh()->assignees()->pluck('users.id')->all());
        $this->assertSame($staffB->id, $activity->fresh()->assigned_to);
    }

    public function test_activity_assignment_rejects_inactive_and_non_staff_users(): void
    {
        $admin = $this->user('admin');
        $inactive = $this->user('staff', ['is_active' => false]);
        $anotherAdmin = $this->user('admin');

        $this->actingAs($admin)->post('/activities', [
            'title' => 'Invalid assignment',
            'activity_date' => now()->toDateString(),
            'assigned_to' => [$inactive->id, $anotherAdmin->id],
        ])->assertSessionHasErrors(['assigned_to.0', 'assigned_to.1']);
        $this->assertDatabaseMissing('activities', ['title' => 'Invalid assignment']);
    }

    public function test_activity_edit_keeps_existing_inactive_assignees_visible_and_assigned(): void
    {
        $admin = $this->user('admin');
        $staffA = $this->user();
        $staffB = $this->user();
        $this->actingAs($admin)->post('/activities', [
            'title' => 'Existing team assignment',
            'activity_date' => now()->toDateString(),
            'assigned_to' => [$staffA->id, $staffB->id],
        ])->assertRedirect();
        $activity = Activity::where('title', 'Existing team assignment')->firstOrFail();
        $staffB->update(['is_active' => false]);

        $editResponse = $this->actingAs($admin)->get('/activities/'.$activity->id.'/edit');
        $editResponse->assertOk()->assertSee($staffB->name);
        $this->assertMatchesRegularExpression(
            '/<option value="'.$staffB->id.'" selected>/',
            $editResponse->getContent(),
        );
        $this->actingAs($admin)->put('/activities/'.$activity->id, [
            'title' => $activity->title,
            'activity_date' => $activity->activity_date->toDateString(),
        ])->assertRedirect();

        $this->assertEqualsCanonicalizing(
            [$staffA->id, $staffB->id],
            $activity->fresh()->assignees()->pluck('users.id')->all(),
        );
    }

    public function test_issue_reports_are_private_to_reporter_and_administrators_and_admins_manage_resolution(): void
    {
        $reporter = $this->user();
        $otherStaff = $this->user();
        $admin = $this->user('admin');

        $this->actingAs($reporter)->post('/support-issues', [
            'title' => 'Payment gateway timeout',
            'affected_service' => 'Payment API',
            'description' => 'Requests are timing out during the settlement window.',
            'priority' => 'High',
        ])->assertRedirect();
        $report = SupportIssueReport::firstOrFail();

        $this->assertSame('Open', $report->status);
        $this->assertSame($reporter->id, $report->reported_by);
        $this->actingAs($reporter)->get('/support-issues')->assertOk()->assertSee($report->title);
        $this->actingAs($otherStaff)->get('/support-issues')->assertOk()->assertDontSee($report->title);
        $this->actingAs($otherStaff)->get('/support-issues/'.$report->id)->assertNotFound();
        $this->actingAs($reporter)->patch('/support-issues/'.$report->id.'/status', [
            'status' => 'Resolved',
            'resolution_notes' => 'Unauthorised update.',
        ])->assertForbidden();

        $this->actingAs($admin)->get('/support-issues/'.$report->id)->assertOk()->assertSee($report->title);
        $this->actingAs($admin)->patch('/support-issues/'.$report->id.'/status', [
            'status' => 'Resolved',
        ])->assertSessionHasErrors('resolution_notes');
        $this->actingAs($admin)->patch('/support-issues/'.$report->id.'/status', [
            'status' => 'Resolved',
            'resolution_notes' => 'Restarted the gateway connection pool.',
        ])->assertRedirect();
        $this->assertDatabaseHas('support_issue_reports', [
            'id' => $report->id,
            'status' => 'Resolved',
            'resolution_notes' => 'Restarted the gateway connection pool.',
        ]);
    }

    public function test_personnel_invitation_uses_email_setup_link_without_a_temporary_password(): void
    {
        Notification::fake();
        $admin = $this->user('admin');

        $this->actingAs($admin)->post('/personnel', [
            'name' => 'Invited Staff',
            'email' => 'invited@example.com',
            'role' => 'staff',
        ])->assertRedirect()->assertSessionHas('success');

        $invited = User::where('email', 'invited@example.com')->firstOrFail();
        $this->assertTrue($invited->must_change_password);
        $this->assertNull($invited->email_verified_at);
        Notification::assertSentTo($invited, PasswordAccessNotification::class);
        $this->actingAs($admin)->get('/personnel')->assertOk()->assertSee('Invitation pending');
    }

    public function test_password_reset_link_changes_password_and_cannot_be_reused(): void
    {
        $user = $this->user();
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'replacement-password-5678',
            'password_confirmation' => 'replacement-password-5678',
        ])->assertRedirect(route('login'))->assertSessionHas('status');
        $this->assertFalse($user->fresh()->must_change_password);
        $this->assertNotNull($user->fresh()->email_verified_at);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'another-replacement-8765',
            'password_confirmation' => 'another-replacement-8765',
        ])->assertSessionHasErrors('email');
        $this->assertTrue(password_verify('replacement-password-5678', $user->fresh()->password));
    }

    public function test_first_login_requires_password_change_before_accessing_workspace(): void
    {
        $user = $this->user('staff', ['must_change_password' => true]);
        $this->post('/login', ['email' => $user->email, 'password' => 'correct-password-1234'])
            ->assertRedirect(route('profile'));
        $this->actingAs($user)->get('/reports')->assertRedirect(route('profile'));

        $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'correct-password-1234',
            'password' => 'brand-new-password-9876',
            'password_confirmation' => 'brand-new-password-9876',
        ])->assertRedirect();
        $this->assertFalse($user->fresh()->must_change_password);
        $this->actingAs($user)->get('/reports')->assertOk();
    }

    public function test_administrator_password_reset_marks_password_forced_change(): void
    {
        $admin = $this->user('admin');
        $staff = $this->user();

        $this->actingAs($admin)->put('/personnel/'.$staff->id.'/password', [
            'password' => 'temporary-password-9876',
            'password_confirmation' => 'temporary-password-9876',
        ])->assertRedirect();

        $this->assertTrue($staff->fresh()->must_change_password);
        $this->actingAs($staff->fresh())->get('/handover')->assertRedirect(route('profile'));
    }
}
