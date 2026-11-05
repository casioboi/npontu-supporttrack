@extends('layouts.app')

@section('content')
@php($title = 'Edit activity')
<div class="page-heading">
    <div>
        <div class="eyebrow"><a href="{{ route('activities.index') }}" class="crumb-link">ACTIVITIES</a><span class="crumb-separator">/</span> EDIT RECORD</div>
        <h1>Edit activity</h1>
        <div class="subtle">Update the activity details, schedule, or assigned staff member.</div>
    </div>
    <a class="button" href="{{ route('activities.show', $activity) }}">Cancel</a>
</div>

<section class="card section" style="max-width: 760px">
    <form method="post" action="{{ route('activities.update', $activity) }}" data-busy>
        @csrf
        @method('PUT')

        <div class="field">
            <label for="title">Activity title <span class="required-star">*</span></label>
            <input class="input" id="title" name="title" value="{{ old('title', $activity->title) }}" required maxlength="255" aria-describedby="title-error">
            @error('title')<p id="title-error" class="field-error" role="alert">{{ $message }}</p>@enderror
        </div>

        <div class="field">
            <label for="description">Description <span class="muted">(optional)</span></label>
            <textarea class="input" id="description" name="description" rows="5" maxlength="10000" aria-describedby="description-error">{{ old('description', $activity->description) }}</textarea>
            @error('description')<p id="description-error" class="field-error" role="alert">{{ $message }}</p>@enderror
        </div>

        <div class="field">
            <label for="activity_date">Activity date <span class="required-star">*</span></label>
            <input class="input" type="date" id="activity_date" name="activity_date" value="{{ old('activity_date', $activity->activity_date->toDateString()) }}" required aria-describedby="activity-date-help activity-date-error">
            <small id="activity-date-help" class="field-hint">Changing this date affects the activity-date filters used by handover and reports. Existing update timestamps are unchanged.</small>
            @error('activity_date')<p id="activity-date-error" class="field-error" role="alert">{{ $message }}</p>@enderror
        </div>

        <div class="field">
            <label for="priority">Priority <span class="required-star">*</span></label>
            <select class="input" id="priority" name="priority" required aria-describedby="priority-help priority-error">
                @foreach($priorities as $priority)
                    <option value="{{ $priority }}" @selected(old('priority', $activity->priority) === $priority)>{{ $priority }}</option>
                @endforeach
            </select>
            <small id="priority-help" class="field-hint">High-priority activities are shown first within their handover status.</small>
            @error('priority')<p id="priority-error" class="field-error" role="alert">{{ $message }}</p>@enderror
        </div>

        <div class="field">
            <label for="assigned_to">Assigned personnel <span class="muted">(optional · select multiple)</span></label>
            <input type="hidden" name="assigned_to[]" value="">
            <select class="input" id="assigned_to" name="assigned_to[]" multiple size="5" aria-describedby="assigned-to-help assigned-to-error">
                @foreach($personnel as $person)
                    <option value="{{ $person->id }}" @selected(in_array((string) $person->id, array_map('strval', (array) old('assigned_to', $assigneeIds)), true))>
                        {{ $person->name }}{{ $person->is_active ? '' : ' (inactive — existing assignment)' }}
                    </option>
                @endforeach
            </select>
            <small id="assigned-to-help" class="field-hint">Select one or more active staff members. Existing inactive or administrator assignments are retained unless changed.</small>
            @error('assigned_to')<p id="assigned-to-error" class="field-error" role="alert">{{ $message }}</p>@enderror
        </div>

        <div class="field">
            <p class="field-hint">Current status: <strong>{{ $activity->status }}</strong>. Status and submitted activity updates are not changed by editing these details.</p>
        </div>

        <div class="form-actions">
            <a class="button" href="{{ route('activities.show', $activity) }}">Cancel</a>
            <button class="button primary" type="submit">Save changes</button>
        </div>
    </form>
</section>
@endsection
