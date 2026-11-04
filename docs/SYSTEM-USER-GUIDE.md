# System User Guide

This guide covers the current application. An administrator provides the correct hosted address and account credentials. For local setup, use `http://127.0.0.1:8000/login` after starting the development server. There is no public demo or deployed instance at this time.

## Part A: Administrator guide

### 1. Accessing and logging in

1. Open the SupportTrack address supplied by your system administrator.
2. Enter the email address and password for your active account on the Login page.
3. Select **Sign in**.

**Expected result:** Successful sign-in opens the administrator dashboard. Public self-registration is not available. Inactive accounts cannot sign in.

### 2. Understanding the administrator dashboard

Use the dashboard to review activity totals, today's activity, Done and Pending counts, updates submitted today, active personnel, recent updates, and pending work. Select an activity or use the navigation to open the full activity list, handover, history, or reports.

**Expected result:** Dashboard figures and lists reflect current database records; historical status should be checked in a date-filtered handover or report.

### 3. Creating a staff account

1. Open **Personnel**.
2. Complete the new account form with the staff member's name, work email, password and confirmation, and role.
3. Use a password of at least 12 characters and share initial credentials through an approved secure channel.
4. Submit the form.

**Expected result:** The account is created and active. The application does not send an invitation email or force a password change at first sign-in. Ask the staff member to change the password from **My Profile** after signing in.

### 4. Activating, deactivating, and managing accounts

In **Personnel**, use the account controls to change activation state, edit name/email/role, or set a replacement password. A replacement password must be at least 12 characters and confirmed. An administrator cannot deactivate their own account, demote their own administrator role, or deactivate/demote the last active administrator.

**Expected result:** An inactive account is blocked from the workspace; an already authenticated inactive account is logged out on a subsequent web request. Existing activity history remains.

### 5. Creating an official activity

1. Open **Activities**, then select **New activity**.
2. Enter a clear title and activity date.
3. Add a description if the assignee needs context.
4. Optionally select one active staff member as the assignee, or leave it unassigned.
5. Select **Create activity**.

**Expected result:** A new activity is created with Pending status and appears in activity lists and handover views according to its date. The creator is recorded as the administrator. The application has no priority field, team assignment, activity edit, or delete workflow.

### 6. Managing scheduled activities

Use **Activities** to search and filter the activity list by title/description, activity date, status, and (for administrators) assignee. Open a row to view its current details and history.

**Expected result:** You can inspect and create activities. Existing activity date, title, description, or assignment cannot currently be edited through the application; there is no activity edit/delete route. Create the activity with the intended date and assignee.

### 7. Reviewing staff updates and activity history

Open an activity to review its update timeline and latest structured details. **Activity History** provides date, activity title, status, and personnel filters. Administrator history can include updates across the team.

**Expected result:** Each submitted update appears as a separate record with its status, structured details, submitter snapshot, and recorded time. Submitted updates cannot be edited or deleted through the normal application interface.

### 8. Monitoring pending work and daily handover

Open **Daily Handover** and select a date. Optionally filter by status or personnel. Review the latest status and update at the end of that date, the last staff member who updated the item, and any pending work carried from an earlier date.

**Expected result:** Pending work and activities awaiting their first update remain visible on later handovers. Done work stops carrying forward unless a later authorised Pending update reopens it. Handover is calculated from history; it does not create duplicate activities.

### 9. Reviewing support-issue reports

The current application has no separate unplanned support-issue reporting feature or issue-report list. Do not expect reports of incidents outside official activities to appear in SupportTrack. Follow the organisation's approved incident channel; an administrator may create an official activity if follow-up work should be tracked here.

**Expected result:** Only activities and their updates are visible in the current system.

### 10. Running, filtering, and exporting reports

1. Open **Reports**.
2. Set optional **From activity date** and **To activity date / status as of** dates. The end date is inclusive and defines the historical cutoff.
3. Optionally choose personnel (administrator only), an exact activity, title text, or status.
4. Select **Run report**.
5. Select **Export CSV** to download the matching results.

**Expected result:** The report shows activity totals, Done/Pending counts, matching update counts, a personnel update breakdown, and matching activity history. A staff report is scoped to activities available to that staff member and only their own update records; administrators can review the team.

### 11. Logging out

Select **Log out** from the application navigation.

**Expected result:** The session is invalidated and the browser returns to Login.

## Part B: Staff user guide

### 1. Receiving credentials and signing in

An administrator creates your account and provides the application address, email, and initial password. Open the Login page, enter your credentials, and select **Sign in**.

**Expected result:** You enter the staff workspace if the account is active. There is no self-registration or email invitation flow.

### 2. Changing an initial password

The application does not force a password change at first login. To change it, open **My Profile**, enter your current password, enter a new password of at least 12 characters, confirm it, and save.

**Expected result:** The new password is required at the next login. If you have lost access, contact an administrator; self-service password reset is not implemented.

### 3. Understanding the staff dashboard

The dashboard highlights your assigned work, pending assigned work, items due today, overdue or carried-forward work, and recent updates. Open **My Activities** for the activities visible to your account.

**Expected result:** The dashboard is scoped to your account for assigned work. The activity list can also include activities you created. Pending team work and awaiting work are available through **Daily Handover**, subject to its access rules.

### 4. Viewing daily activities and opening an activity

1. Open **My Activities**.
2. Filter by date or status, or search for an activity.
3. Select the activity title or **View**.
4. For pending work assigned to another staff member, open it from **Daily Handover**.

**Expected result:** The activity page shows its description, current status, assignment, update history, and (if authorised) the update form. Staff cannot create official activities.

### 5. Performing work and submitting a Done update

After completing the operational task, open the activity update form, select **Done**, enter **Work Done**, optionally enter **Findings**, and submit.

**Expected result:** A new Done update is appended with your authenticated identity and the server timestamp. Outstanding Issues and Recommended Next Steps can be empty for Done.

### 6. Submitting a Pending update

If work remains, select **Pending**, enter **Work Done**, **Outstanding Issues**, and **Recommended Next Steps**, optionally add **Findings**, and submit.

**Expected result:** All three required fields are validated by the server. Missing required details prevent the update from being saved. The activity remains available for handover.

### 7. Writing useful remarks

Be specific about the action performed, evidence checked, result, and follow-up. For example:

> Compared the daily SMS dashboard count with the application logs. Both records match.

For unfinished work, explain the blocker and the next action:

> Unable to complete SMS verification because the reporting dashboard is currently unavailable. Follow-up required.

For Pending, also complete the separate Outstanding Issues and Recommended Next Steps fields with concise, actionable details.

### 8. Viewing previous updates and searching history

Use the activity detail page to review that activity's timeline. **Activity History** lets you filter your updates by date range, activity title, and status.

**Expected result:** The timeline is chronological. Staff history is limited to the signed-in staff member's updates within authorised activity scope.

### 9. Using Daily Handover

Open **Daily Handover**, select the relevant date, and optionally filter by status. Review the latest update as of that day, including the previous staff member and recorded time. Open an authorised Pending activity to continue it.

**Expected result:** Activities with no update appear as **Awaiting Update**. Pending/awaiting activities carry forward; a Done update stops carryover unless the activity is later reopened.

### 10. Reporting an unplanned support issue

There is no dedicated support-issue report form in this version. Do not treat a separate incident-reporting workflow as available. Follow your organisation's approved issue channel; ask an administrator to create an official activity if follow-up work needs to be tracked in SupportTrack.

### 11. Viewing reports and exporting

Open **Reports** and choose activity date range, title text, exact activity, or status as of the selected end date. Select **Run report** to refresh results, or **Export CSV** to download them.

**Expected result:** You see activities available to you and your own update records, not colleagues' private update content.

### 12. Updating your profile and logging out

The profile displays your name, email, role, and account status. These details are managed by an administrator; the staff self-service control changes only your password. To finish a session, select **Log out**.

**Expected result:** Profile changes to your password take effect immediately; logging out invalidates the current session.

## Official activities and unplanned issues

An **official activity** is a dated work record created by an administrator and updated by authorised staff. An **unplanned support issue** would be a separate issue/incident record. That second workflow is not implemented; use the approved external issue channel and ask an administrator to create an activity when the resulting work needs to be tracked here.
