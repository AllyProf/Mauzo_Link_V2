@extends('layouts.dashboard')

@section('title', 'System Monitor - Sessions')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-users"></i> Session Control Center</h1>
    <p>Real-time tracking of active logins and browser connections</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item">System Monitor</li>
    <li class="breadcrumb-item">Sessions</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="tile-title-w-btn">
        <h3 class="title">Active Connection List</h3>
        <div class="btn-group">
            <a class="btn btn-primary icon-btn" href="{{ url()->current() }}"><i class="fa fa-refresh"></i> Refresh</a>
            <form action="{{ route('system.sessions.clear-guests') }}" method="POST" onsubmit="return confirm('This will clear all unidentified browser sessions. Active staff will remain. Proceed?')">
                @csrf
                <button type="submit" class="btn btn-warning icon-btn ml-1"><i class="fa fa-trash"></i> Clean Guests</button>
            </form>
        </div>
      </div>
      
      <div class="tile-body">
        <div class="table-responsive">
          <table class="table table-hover table-bordered" id="sessionTable">
            <thead>
              <tr class="bg-light">
                <th>User / Device</th>
                <th width="150">Access Level</th>
                <th width="150">Network (IP)</th>
                <th>Browser Detail</th>
                <th width="150">Status</th>
                <th width="80">Action</th>
              </tr>
            </thead>
            <tbody>
              @foreach($sessions as $session)
                @php 
                    $lastSeen = \Carbon\Carbon::createFromTimestamp($session->last_activity);
                    $minutesAgo = $lastSeen->diffInMinutes(now(), false);
                    $isYou = ($session->id === $currentSessionId);
                    $isGuest = ($session->role == 'Guest');
                @endphp
                <tr class="{{ $isYou ? 'table-info' : ($isGuest ? 'text-muted opacity-guest' : '') }}">
                  <td>
                    @if($isYou)
                        <span class="badge badge-primary mb-1"><i class="fa fa-star"></i> THIS IS YOU</span><br>
                    @endif
                    <strong class="{{ $isGuest ? 'text-secondary font-italic' : 'text-dark' }}">
                        {{ $session->identity }}
                    </strong>
                    <br><small class="text-muted">ID: {{ Str::limit($session->id, 12, '...') }}</small>
                  </td>
                  <td>
                    @if(str_contains(strtolower($session->role), 'admin'))
                        <span class="badge badge-success"><i class="fa fa-shield"></i> {{ $session->role }}</span>
                    @elseif($isGuest)
                        <span class="text-muted"><i class="fa fa-user-secret"></i> {{ $session->role }}</span>
                    @else
                        <span class="badge badge-secondary">{{ $session->role }}</span>
                    @endif
                  </td>
                  <td>
                    <i class="fa fa-laptop small text-muted mr-1"></i>
                    <code>{{ $session->ip_address }}</code>
                  </td>
                  <td>
                    <span class="text-muted small" title="{{ $session->user_agent }}">
                        {{ Str::limit($session->user_agent, 45) }}
                    </span>
                  </td>
                  <td>
                    @if($minutesAgo < 2)
                        <span class="text-success font-weight-bold animated-dot"><i class="fa fa-circle"></i> Online Now</span>
                    @else
                        <span class="text-muted">{{ round(abs($minutesAgo)) }} mins ago</span>
                    @endif
                  </td>
                  <td class="text-center">
                    @if(!$isYou)
                    <form action="{{ route('system.sessions.kill', $session->id) }}" method="POST" id="kill-form-{{ $session->id }}">
                        @csrf
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmKill('{{ $session->id }}', '{{ $session->identity }}')" title="Force Logout">
                            <i class="fa fa-power-off"></i>
                        </button>
                    </form>
                    @else
                        <i class="fa fa-lock text-muted" title="Your current session is protected"></i>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.opacity-guest { opacity: 0.7; }
.animated-dot i {
    animation: blink 1s infinite alternate;
}
@keyframes blink {
    from { opacity: 1; }
    to { opacity: 0.3; }
}
.bg-light th { color: #555; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
.table-info { border-left: 5px solid #009688 !important; }
</style>

@section('scripts')
<script>
function confirmKill(sessionId, identity) {
    showConfirm(
        'Terminate session for ' + identity + '? They will be logged out globally.',
        'Security Action',
        function() {
            document.getElementById('kill-form-' + sessionId).submit();
        }
    );
}
</script>
@endsection
@endsection
