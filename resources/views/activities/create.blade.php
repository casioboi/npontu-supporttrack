@extends('layouts.app')
@section('content')
@php($title = 'New activity')
@push('styles')<link rel="stylesheet" href="{{ asset('css/activity-create.css') }}">@endpush
<div class="create-activity-stage">
    <div class="create-activity-orb orb-main" aria-hidden="true"></div>
    <div class="create-activity-orb orb-side" aria-hidden="true"></div>
    <div class="create-activity-wrap">
        <div class="create-page-top"><a class="create-back-link" href="{{ route('activities.index') }}"><span aria-hidden="true">←</span> Back to activities</a><span class="create-top-note"><span class="create-dot"></span> SUPPORT OPERATIONS</span></div>
        <section class="create-activity-card">
            <header class="create-card-header"><div class="create-header-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></div><div><div class="create-kicker">ACTIVITY LOG · NEW RECORD</div><h1>Create an activity</h1><p>Add a support task for your team to track and hand over.</p></div><span class="create-record-tag">NEW ENTRY</span></header>
            <div class="create-form-progress" aria-label="Activity creation overview"><div class="progress-item active"><span>01</span><b>Activity details</b></div><i></i><div class="progress-item"><span>02</span><b>Owner &amp; timing</b></div><i></i><div class="progress-item"><span class="progress-check">✓</span><b>Ready for handover</b></div></div>
            <form method="post" action="{{ route('activities.store') }}" data-busy class="create-activity-form">
                @csrf
                <div class="create-form-grid">
                    <div class="create-field create-field-wide"><label for="title">Activity title <span>*</span></label><input class="create-input" id="title" name="title" required maxlength="255" value="{{ old('title') }}" placeholder="e.g. Compare daily SMS count with gateway logs"><small>A clear title helps the next person scan the handover quickly.</small></div>
                    <div class="create-field create-field-wide"><div class="create-label-line"><label for="description">Description <em>OPTIONAL</em></label><span class="create-counter" id="descriptionCount">0 / 10,000</span></div><textarea class="create-input create-textarea" id="description" name="description" maxlength="10000" placeholder="Add context, systems involved, expected outcome, or useful links...">{{ old('description') }}</textarea><small>Include the context someone needs before they begin or continue this work.</small></div>
                    <div class="create-field"><label for="activity_date">Activity date <span>*</span></label><div class="create-input-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2v2H5a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-2V2h-2v2H9V2H7Zm12 7v10H5V9h14Z" fill="currentColor"/></svg><input class="create-input" type="date" id="activity_date" name="activity_date" required value="{{ old('activity_date',$activityDate ?? now()->toDateString()) }}"></div><small>Choose the day this support activity belongs to.</small></div>
                    <div class="create-field"><label for="assigned_to">Assigned personnel <em>OPTIONAL</em></label><div class="create-input-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2c-4.4 0-8 2.2-8 5v1h16v-1c0-2.8-3.6-5-8-5Z" fill="currentColor"/></svg><select class="create-input" id="assigned_to" name="assigned_to"><option value="">Leave unassigned</option>@foreach($personnel as $person)<option value="{{ $person->id }}" @selected(old('assigned_to')==$person->id)>{{ $person->name }}</option>@endforeach</select></div><small>You can assign or change ownership later.</small></div>
                </div>
                <aside class="create-status-note"><span class="status-note-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9 9 9 0 0 0-9-9Zm1 9.4 3.1 1.8-1 1.7-4.1-2.5V7h2Z" fill="currentColor"/></svg></span><span><b>Starts as Pending</b><i>Activities stay visible in the daily handover until the team marks them Done.</i></span><span class="badge pending">Pending</span></aside>
                <div class="create-form-actions"><a class="create-cancel" href="{{ route('activities.index') }}">Cancel</a><button class="create-submit" type="submit"><span>Create activity</span><span aria-hidden="true">→</span></button></div>
            </form>
            <footer class="create-card-footer"><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2 4 5v6c0 5.2 3.4 9.4 8 11 4.6-1.6 8-5.8 8-11V5l-8-3Zm0 3 5 1.9V11c0 3.8-2.2 7-5 8.4-2.8-1.4-5-4.6-5-8.4V6.9L12 5Z" fill="currentColor"/></svg>Created by {{ auth()->user()->name }}</span><span>Activity history is recorded with each update</span></footer>
        </section>
        <div class="create-page-foot"><span>NPONTU SUPPORTTRACK</span><span>Clear records. Better handovers.</span></div>
    </div>
</div>
@push('scripts')<script>const descriptionField=document.getElementById('description');const descriptionCount=document.getElementById('descriptionCount');const updateDescriptionCount=()=>{descriptionCount.textContent=`${descriptionField.value.length.toLocaleString()} / 10,000`;descriptionCount.classList.toggle('near-limit',descriptionField.value.length>9000)};descriptionField.addEventListener('input',updateDescriptionCount);updateDescriptionCount();</script>@endpush
@endsection
