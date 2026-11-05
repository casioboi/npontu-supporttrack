<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'SupportTrack' }} · Npontu SupportTrack</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/activity-priority.css') }}">
    <link rel="stylesheet" href="{{ asset('css/feature-workflows.css') }}">
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/readability.css') }}">
    <link rel="stylesheet" href="{{ asset('css/buttons.css') }}">
    <link rel="stylesheet" href="{{ asset('css/interactions.css') }}">
    @stack('after-styles')
</head>
<body>
<div class="shell" id="appShell">
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="{{ route('dashboard') }}"><span class="brand-mark">S</span><span>SupportTrack</span></a>
        <div class="nav-caption">Workspace</div>
        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span class="nav-icon" aria-hidden="true">⌂</span>Dashboard</a>
        @if(auth()->user()->isAdmin())
            <a class="nav-link {{ request()->routeIs('activities.*') ? 'active' : '' }}" href="{{ route('activities.index') }}"><span class="nav-icon" aria-hidden="true">▤</span>Activities</a>
        @else
            <a class="nav-link {{ request()->routeIs('my-activities') || request()->routeIs('activities.*') ? 'active' : '' }}" href="{{ route('my-activities') }}"><span class="nav-icon" aria-hidden="true">▤</span>My Activities</a>
        @endif
        <a class="nav-link {{ request()->routeIs('activity-history') ? 'active' : '' }}" href="{{ route('activity-history') }}"><span class="nav-icon" aria-hidden="true">◷</span>{{ auth()->user()->isAdmin() ? 'Activity History' : 'My Activity History' }}</a>
        <a class="nav-link {{ request()->routeIs('handover') ? 'active' : '' }}" href="{{ route('handover') }}"><span class="nav-icon" aria-hidden="true">⇄</span>Daily Handover</a>
        <a class="nav-link {{ request()->routeIs('support-issues.*') ? 'active' : '' }}" href="{{ route('support-issues.index') }}"><span class="nav-icon" aria-hidden="true">!</span>Support Issues</a>
        <a class="nav-link {{ request()->routeIs('reports') ? 'active' : '' }}" href="{{ route('reports') }}"><span class="nav-icon" aria-hidden="true">▥</span>Reports</a>
        @if(auth()->user()->isAdmin())
            <div class="nav-caption">Administration</div>
            <a class="nav-link {{ request()->routeIs('personnel.*') ? 'active' : '' }}" href="{{ route('personnel.index') }}"><span class="nav-icon" aria-hidden="true">♙</span>Personnel</a>
        @endif
        <div class="side-bottom">
            <a class="nav-link {{ request()->routeIs('profile') ? 'active' : '' }}" href="{{ route('profile') }}"><span class="nav-icon" aria-hidden="true">◎</span>My Profile</a>
            <div class="user-mini"><span class="avatar">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</span><div>{{ auth()->user()->name }}<small>{{ ucfirst(auth()->user()->role) }}</small></div></div>
            <form method="post" action="{{ route('logout') }}">@csrf<button class="nav-link" type="submit" style="width:100%;text-align:left;cursor:pointer"><span class="nav-icon" aria-hidden="true">↪</span>Logout</button></form>
        </div>
    </aside>
    <main class="main">
        <header class="topbar"><div style="display:flex;align-items:center;gap:13px"><button class="button small nav-toggle" type="button" data-nav-toggle aria-controls="sidebar" aria-expanded="true" aria-label="Collapse navigation"><svg class="nav-toggle-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></button><div class="top-title">{{ $title ?? 'Workspace' }}</div></div><div class="top-right"><span class="top-date">{{ now()->format('l, F j, Y') }}</span><a href="{{ route('profile') }}" aria-label="Open profile" class="avatar" style="width:28px;height:28px;text-decoration:none">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</a></div></header>
        <div class="content">
            @if(session('success') || $errors->any())
                <div class="toast-container" aria-label="Notifications" data-toast-container>
                    @if(session('success'))<div class="toast toast-success" role="status" aria-live="polite" aria-atomic="true" data-toast data-autohide-ms="5200"><span class="toast-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m5 12 4.5 4.5L19 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span><div class="toast-copy"><strong>Done</strong><span>{{ session('success') }}</span></div><button class="toast-close" type="button" data-toast-dismiss aria-label="Dismiss notification">×</button><span class="toast-progress" aria-hidden="true"></span></div>@endif
                    @if($errors->any())<div class="toast toast-error" role="alert" aria-live="assertive" aria-atomic="true" data-toast data-autohide-ms="7500"><span class="toast-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 8v5m0 3h.01M10.3 3.9 2.7 17a2 2 0 0 0 1.7 3h15.2a2 2 0 0 0 1.7-3l-7.6-13.1a2 2 0 0 0-3.4 0Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span><div class="toast-copy"><strong>Check the details</strong><span>{{ $errors->first() }}</span></div><button class="toast-close" type="button" data-toast-dismiss aria-label="Dismiss notification">×</button><span class="toast-progress" aria-hidden="true"></span></div>@endif
                </div>
            @endif
            @yield('content')
        </div>
    </main>
</div>
<script>
document.querySelectorAll('form[data-busy]').forEach(form=>form.addEventListener('submit',()=>{if(!form.reportValidity())return;const button=form.querySelector('button[type="submit"],button:not([type])');if(!button)return;button.disabled=true;button.setAttribute('aria-busy','true');button.dataset.originalText=button.textContent.trim();button.textContent='Working…';}));
document.querySelectorAll('details > summary').forEach(summary=>summary.setAttribute('tabindex','0'));
(()=>{
    const shell=document.getElementById('appShell');
    const sidebar=document.getElementById('sidebar');
    const toggle=document.querySelector('[data-nav-toggle]');
    const mobile=()=>window.matchMedia('(max-width: 650px)').matches;
    const setNavigation=(collapsed)=>{
        if(mobile()){
            shell.classList.remove('nav-collapsed');
            sidebar.classList.toggle('open',!collapsed);
            document.body.classList.toggle('nav-drawer-open',!collapsed);
        }else{
            shell.classList.toggle('nav-collapsed',collapsed);
            sidebar.classList.remove('open');
            document.body.classList.remove('nav-drawer-open');
        }
        toggle.setAttribute('aria-expanded',String(!collapsed));
        toggle.setAttribute('aria-label',collapsed?'Expand navigation':'Collapse navigation');
    };
    if(mobile())setNavigation(true);
    toggle.addEventListener('click',()=>setNavigation(mobile()?sidebar.classList.contains('open'):!shell.classList.contains('nav-collapsed')));
    document.addEventListener('click',event=>{if(mobile()&&sidebar.classList.contains('open')&&!sidebar.contains(event.target)&&!toggle.contains(event.target))setNavigation(true)});
    document.addEventListener('keydown',event=>{if(event.key==='Escape'&&mobile()&&sidebar.classList.contains('open')){setNavigation(true);toggle.focus()}});
    window.addEventListener('resize',()=>{if(mobile()){setNavigation(true)}else{sidebar.classList.remove('open');document.body.classList.remove('nav-drawer-open');toggle.setAttribute('aria-expanded','true');toggle.setAttribute('aria-label','Collapse navigation')}});
})();
document.querySelectorAll('[data-toast]').forEach(toast=>{
    const duration=Number(toast.dataset.autohideMs)||5200;
    const progress=toast.querySelector('.toast-progress');
    let remaining=duration;
    let started=0;
    let timer;
    const dismiss=()=>{clearTimeout(timer);toast.classList.add('toast-leave');toast.addEventListener('transitionend',()=>toast.remove(),{once:true});setTimeout(()=>toast.remove(),320)};
    const resume=()=>{if(!toast.isConnected||timer)return;started=Date.now();toast.classList.remove('toast-paused');if(progress)progress.style.animationPlayState='running';timer=setTimeout(dismiss,remaining)};
    const pause=()=>{if(!toast.isConnected||!timer)return;clearTimeout(timer);timer=null;remaining=Math.max(0,remaining-(Date.now()-started));toast.classList.add('toast-paused');if(progress)progress.style.animationPlayState='paused'};
    toast.addEventListener('mouseenter',pause);
    toast.addEventListener('mouseleave',resume);
    toast.addEventListener('focusin',pause);
    toast.addEventListener('focusout',event=>{if(!toast.contains(event.relatedTarget))resume()});
    toast.querySelector('[data-toast-dismiss]')?.addEventListener('click',dismiss);
    if(!window.matchMedia('(prefers-reduced-motion: reduce)').matches&&progress)progress.style.animationDuration=`${duration}ms`;
    resume();
});
</script>
@stack('scripts')
</body>
</html>
