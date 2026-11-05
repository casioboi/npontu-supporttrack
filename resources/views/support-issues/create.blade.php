@extends('layouts.app')

@section('content')
@php($title = 'Report a support issue')
<div class="page-heading"><div><div class="eyebrow">UNPLANNED WORK · NEW REPORT</div><h1>Report a support issue</h1><div class="subtle">Record an unplanned operational issue so administrators can track its resolution.</div></div><a class="button" href="{{ route('support-issues.index') }}">Cancel</a></div>
<section class="card section" style="max-width:760px">
    <form method="post" action="{{ route('support-issues.store') }}" data-busy>
        @csrf
        <div class="field"><label for="title">Issue summary <span class="required-star">*</span></label><input class="input" id="title" name="title" value="{{ old('title') }}" maxlength="255" required aria-describedby="title-error">@error('title')<p id="title-error" class="field-error" role="alert">{{ $message }}</p>@enderror</div>
        <div class="field"><label for="affected_service">Affected system or service <span class="muted">(optional)</span></label><input class="input" id="affected_service" name="affected_service" value="{{ old('affected_service') }}" maxlength="255" aria-describedby="service-error">@error('affected_service')<p id="service-error" class="field-error" role="alert">{{ $message }}</p>@enderror</div>
        <div class="field"><label for="priority">Impact priority <span class="required-star">*</span></label><select class="input" id="priority" name="priority" required>@foreach($priorities as $priority)<option value="{{ $priority }}" @selected(old('priority', 'Medium') === $priority)>{{ $priority }}</option>@endforeach</select>@error('priority')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
        <div class="field"><label for="description">What happened and what is affected? <span class="required-star">*</span></label><textarea class="input" id="description" name="description" rows="7" minlength="10" maxlength="10000" required aria-describedby="description-help description-error">{{ old('description') }}</textarea><small id="description-help" class="field-hint">Include observed symptoms, impact, when it began, and any safe troubleshooting already attempted. Do not include passwords, tokens, or sensitive customer data.</small>@error('description')<p id="description-error" class="field-error" role="alert">{{ $message }}</p>@enderror</div>
        <div class="form-actions"><a class="button" href="{{ route('support-issues.index') }}">Cancel</a><button class="button primary" type="submit">Submit issue report</button></div>
    </form>
</section>
@endsection
