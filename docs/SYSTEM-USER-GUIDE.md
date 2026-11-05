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
2. Complete the new account form with the staff member's name, work email, and role.
3. Select **Send invitation**.

**Expected result:** The account is created and an email with a single-use password-setup link is sent. The staff member chooses their own password before signing in. The deployment must have a working mail transport and public `APP_URL`.

### 4. Activating, deactivating, and managing accounts

In **Personnel**, use the account controls to change activation state, edit name/email/role, resend an invitation, or set a temporary password. A temporary password must be at least 12 characters and confirmed. The user must change it at the next sign-in. An administrator cannot deactivate their own account, demote their own administrator role, or deactivate/demote the last active administrator.

**Expected result:** An inactive account is blocked from the workspace; an already authenticated inactive account is logged out on a subsequent web request. Existing activity history remains.

### 5. Creating an official activity

1. Open **Activities**, then select **New activity**.
2. Enter a clear title and activity date.
3. Choose **High**, **Medium**, or **Low** priority. Medium is the default.
4. Add a description if the assignee needs context.
5. Optionally select one or more active staff members as assignees, or leave it unassigned.
6. Select **Create activity**.

**Expected result:** A new activity is created with Pending status and the selected priority and appears in activity lists and handover views according to its date. The creator is recorded as the administrator. Every selected assignee can access the activity. Activity deletion is not available.

### 6. Managing scheduled activities

Use **Activities** to search and filter the activity list by title/description, activity date, status, priority, and assignee. Open a row and select **Edit activity** to change its title, description, activity date, priority, or assignees. Save the changes to return to the activity record. New assignees must be active staff; an existing inactive or administrator assignment may remain or be replaced.

**Expected result:** The activity details are updated without changing its current Pending/Done status or altering submitted update history. Changing the activity date affects date-based handover/report selection, while existing update timestamps remain unchanged. Activity deletion is not available.

### 7. Reviewing staff updates and activity history

Open an activity to review its update timeline and latest structured details. **Activity History** provides date, activity title, status, and personnel filters. Administrator history can include updates across the team.

**Expected result:** Each submitted update appears as a separate record with its status, structured details, submitter snapshot, and recorded time. Submitted updates cannot be edited or deleted through the normal application interface.

### 8. Monitoring pending work and daily handover

Open **Daily Handover** and select a date. Optionally filter by status, priority, or personnel. Within each status group, High-priority items appear before Medium and Low items, then titles are sorted. Review the latest status and update at the end of that date, the last staff member who updated the item, and any pending work carried from an earlier date.

**Expected result:** Pending work and activities awaiting their first update remain visible on later handovers. Done work stops carrying forward unless a later authorised Pending update reopens it. Handover is calculated from history; it does not create duplicate activities.

### 9. Reviewing support-issue reports

Open **Support Issues** and choose **Report an issue**. Enter a concise title and description, choose a priority, and optionally identify the affected service. Submit the report.

**Expected result:** The report is visible only to you and administrators. Administrators can move it between Open, In Progress, and Resolved. Resolution requires resolution notes. Issue reports are separate from scheduled activities and do not create an activity automatically.

### 10. Running, filtering, and exporting reports

1. Open **Reports**.
2. Set optional **From activity date** and **To activity date / status as of** dates. The end date is inclusive and defines the historical cutoff.
3. Optionally choose priority, personnel (administrator only), an exact activity, title text, or status.
4. Select **Run report**.
5. Select **Export CSV** to download the matching results.

**Expected result:** The report shows activity totals, Done/Pending counts, matching update counts, a personnel update breakdown, and matching activity history. A staff report is scoped to activities available to that staff member and only their own update records; administrators can review the team.

### 11. Logging out

Select **Log out** from the application navigation.

**Expected result:** The session is invalidated and the browser returns to Login.

## Part B: Staff user guide

### 1. Receiving credentials and signing in

An administrator sends an invitation to your work email. Open the single-use password-setup link, choose a password of at least 12 characters, then sign in using the application address and your email.

**Expected result:** Your password is set and your email is marked verified. Public self-registration is not available.

### 2. Changing an initial password

If an administrator gave you a temporary password, sign in and follow the forced password-change prompt. Otherwise, open **My Profile**, enter your current password, enter a new password of at least 12 characters, confirm it, and save.

**Expected result:** A temporary-password account cannot use the rest of the workspace until the password has been changed. If you have lost access, select **Forgot your password?** on Login, enter your email, and follow the link if one is sent. The confirmation message does not reveal whether the account exists.

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

Open **Support Issues**, select **Report an issue**, and provide a title, description of at least 10 characters, priority, and optional affected service. Submit the form to create the report.

**Expected result:** You can view the reports you submitted; administrators can view and manage all reports. Other staff cannot view your report. Administrators must enter resolution notes when marking an issue Resolved.

### 11. Viewing reports and exporting

Open **Reports** and choose activity date range, priority, title text, exact activity, or status as of the selected end date. Select **Run report** to refresh results, or **Export CSV** to download them. The CSV includes each activity's current priority.

**Expected result:** You see activities available to you and your own update records, not colleagues' private update content.

### 12. Updating your profile and logging out

The profile displays your name, email, role, and account status. These details are managed by an administrator; the staff self-service control changes only your password. To finish a session, select **Log out**.

**Expected result:** Profile changes to your password take effect immediately; logging out invalidates the current session.

## Official activities and unplanned issues

An **official activity** is a dated work record created by an administrator and updated by assigned or otherwise authorised staff. An **unplanned support issue** is a separate issue report submitted by a staff member or administrator. Issue reports are private to the reporter and administrators; they do not automatically become activities.
